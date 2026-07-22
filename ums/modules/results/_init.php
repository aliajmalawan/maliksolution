<?php
declare(strict_types=1);

/**
 * Results & GPA module — shared init + grading engine.
 * Aggregates a student's exam marks per course (weighted by each exam's
 * weightage), converts to grade points on a 4.0 scale, and rolls up to
 * semester GPA and CGPA. No table of its own — it computes live from the
 * Examination + Courses + Sections data. (Promotion later can snapshot it.)
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();

/**
 * Absolute grading scale (4.0). [minPercent, grade, gradePoint], desc.
 * Edit here to match your institution's policy.
 */
const RESULTS_SCALE = [
    [85, 'A',  4.00], [80, 'A-', 3.70], [75, 'B+', 3.30], [70, 'B',  3.00],
    [65, 'B-', 2.70], [60, 'C+', 2.30], [55, 'C',  2.00], [50, 'C-', 1.70],
    [40, 'D',  1.00], [0,  'F',  0.00],
];

/** Percentage → [grade, gradePoint]. */
function results_grade(float $pct): array
{
    foreach (RESULTS_SCALE as [$min, $grade, $point]) {
        if ($pct >= $min) return [$grade, $point];
    }
    return ['F', 0.00];
}

/** Colour-coded grade pill. */
function grade_badge(string $grade, float $point): string
{
    $cls = $point >= 2 ? 'st-approved' : ($point >= 1 ? 'st-pending' : 'st-rejected');
    return '<span class="st ' . $cls . '">' . e($grade) . '</span>';
}

/** Classification label from a GPA. */
function results_standing(float $gpa): string
{
    return match (true) {
        $gpa >= 3.60 => 'Distinction',
        $gpa >= 3.00 => 'Very Good',
        $gpa >= 2.50 => 'Good',
        $gpa >= 2.00 => 'Satisfactory',
        $gpa >  0.00 => 'Probation',
        default      => '—',
    };
}

/**
 * Compute a full result set for one section.
 * Returns ['courses' => [id => [code,title,credits]], 'students' => [ ... ]]
 * where each student has ['id','name','reg','results'=>[courseId=>[pct,grade,point]],'gpa','credits'].
 */
function results_section(mysqli $db, int $campus, int $sectionId): array
{
    // Courses that actually have exams in this section
    $courses = [];
    $cs = $db->prepare('SELECT DISTINCT c.id, c.code, c.title, c.credit_hours
        FROM ' . tbl('courses') . ' c JOIN ' . tbl('exams') . ' e ON e.course_id = c.id
        WHERE e.section_id = ? AND e.campus_id = ? ORDER BY c.title');
    $cs->bind_param('ii', $sectionId, $campus); $cs->execute();
    $r = $cs->get_result();
    while ($x = $r->fetch_assoc()) $courses[(int)$x['id']] = ['code' => $x['code'], 'title' => $x['title'], 'credits' => (int)$x['credit_hours']];
    $cs->close();

    // Students in the section
    $students = [];
    $ss = $db->prepare('SELECT id, name, registration_no FROM ' . tbl('students') . ' WHERE section_id = ? AND campus_id = ? ORDER BY name');
    $ss->bind_param('ii', $sectionId, $campus); $ss->execute();
    $sr = $ss->get_result();
    while ($x = $sr->fetch_assoc()) $students[(int)$x['id']] = ['id' => (int)$x['id'], 'name' => $x['name'], 'reg' => $x['registration_no'], 'results' => [], 'gpa' => 0.0, 'credits' => 0];
    $ss->close();

    if (!$courses || !$students) return ['courses' => $courses, 'students' => array_values($students)];

    // One pass over all marks in this section, accumulate weighted contributions
    $acc = []; // [studentId][courseId] => ['c'=>contrib, 'w'=>weightSum]
    $ms = $db->prepare('SELECT m.student_id, e.course_id, e.weightage, e.total_marks, m.obtained_marks
        FROM ' . tbl('exams') . ' e JOIN ' . tbl('exam_marks') . ' m ON m.exam_id = e.id
        WHERE e.section_id = ? AND e.campus_id = ? AND e.course_id > 0');
    $ms->bind_param('ii', $sectionId, $campus); $ms->execute();
    $mr = $ms->get_result();
    while ($x = $mr->fetch_assoc()) {
        $tot = (float)$x['total_marks']; if ($tot <= 0) continue;
        $sid = (int)$x['student_id']; $cid = (int)$x['course_id']; $w = (float)$x['weightage'];
        $acc[$sid][$cid]['c'] = ($acc[$sid][$cid]['c'] ?? 0) + ((float)$x['obtained_marks'] / $tot) * $w;
        $acc[$sid][$cid]['w'] = ($acc[$sid][$cid]['w'] ?? 0) + $w;
    }
    $ms->close();

    // Build per-student results + GPA
    foreach ($students as $sid => &$stu) {
        $qp = 0.0; $cr = 0;
        foreach ($courses as $cid => $c) {
            if (empty($acc[$sid][$cid]) || ($acc[$sid][$cid]['w'] ?? 0) <= 0) continue;
            $pct = $acc[$sid][$cid]['c'] / $acc[$sid][$cid]['w'] * 100;
            [$grade, $point] = results_grade($pct);
            $stu['results'][$cid] = ['pct' => round($pct, 1), 'grade' => $grade, 'point' => $point];
            $qp += $point * $c['credits'];
            $cr += $c['credits'];
        }
        $stu['credits'] = $cr;
        $stu['gpa'] = $cr > 0 ? round($qp / $cr, 2) : 0.0;
    }
    unset($stu);

    return ['courses' => $courses, 'students' => array_values($students)];
}

/**
 * Full transcript for one student across every semester (section) they
 * have exam marks in. Returns ['semesters'=>[...], 'cgpa', 'total_credits'].
 * Each semester: ['program','semester','session','courses'=>[...],'gpa','credits'].
 */
function results_student(mysqli $db, int $campus, int $studentId): array
{
    // Sections (= semesters) where this student has any marks
    $secs = [];
    $q = $db->prepare('SELECT DISTINCT e.section_id, s.program, s.semester, s.session
        FROM ' . tbl('exams') . ' e
        JOIN ' . tbl('exam_marks') . ' m ON m.exam_id = e.id AND m.student_id = ?
        JOIN ' . tbl('sections') . ' s ON s.id = e.section_id
        WHERE e.campus_id = ? ORDER BY s.semester, s.id');
    $q->bind_param('ii', $studentId, $campus); $q->execute();
    $r = $q->get_result();
    while ($x = $r->fetch_assoc()) $secs[] = $x;
    $q->close();

    $semesters = []; $totQp = 0.0; $totCr = 0;
    foreach ($secs as $sec) {
        $sectionId = (int)$sec['section_id'];
        // accumulate weighted contributions per course for this student
        $acc = [];
        $mq = $db->prepare('SELECT e.course_id, e.weightage, e.total_marks, m.obtained_marks
            FROM ' . tbl('exams') . ' e
            JOIN ' . tbl('exam_marks') . ' m ON m.exam_id = e.id AND m.student_id = ?
            WHERE e.section_id = ? AND e.campus_id = ? AND e.course_id > 0');
        $mq->bind_param('iii', $studentId, $sectionId, $campus); $mq->execute();
        $mr = $mq->get_result();
        while ($x = $mr->fetch_assoc()) {
            $tot = (float)$x['total_marks']; if ($tot <= 0) continue;
            $cid = (int)$x['course_id']; $w = (float)$x['weightage'];
            $acc[$cid]['c'] = ($acc[$cid]['c'] ?? 0) + ((float)$x['obtained_marks'] / $tot) * $w;
            $acc[$cid]['w'] = ($acc[$cid]['w'] ?? 0) + $w;
        }
        $mq->close();
        if (!$acc) continue;

        // course meta
        $ids = implode(',', array_map('intval', array_keys($acc)));
        $meta = [];
        $cm = $db->query('SELECT id, code, title, credit_hours FROM ' . tbl('courses') . ' WHERE id IN (' . $ids . ')');
        while ($c = $cm->fetch_assoc()) $meta[(int)$c['id']] = $c;

        $courses = []; $qp = 0.0; $cr = 0;
        foreach ($acc as $cid => $a) {
            if (($a['w'] ?? 0) <= 0 || !isset($meta[$cid])) continue;
            $pct = $a['c'] / $a['w'] * 100;
            [$grade, $point] = results_grade($pct);
            $credits = (int)$meta[$cid]['credit_hours'];
            $courses[] = [
                'code' => $meta[$cid]['code'], 'title' => $meta[$cid]['title'], 'credits' => $credits,
                'pct' => round($pct, 1), 'grade' => $grade, 'point' => $point, 'qp' => round($point * $credits, 2),
            ];
            $qp += $point * $credits; $cr += $credits;
        }
        $semesters[] = [
            'program' => $sec['program'], 'semester' => (int)$sec['semester'], 'session' => $sec['session'],
            'courses' => $courses, 'gpa' => $cr > 0 ? round($qp / $cr, 2) : 0.0, 'credits' => $cr,
        ];
        $totQp += $qp; $totCr += $cr;
    }

    return ['semesters' => $semesters, 'cgpa' => $totCr > 0 ? round($totQp / $totCr, 2) : 0.0, 'total_credits' => $totCr];
}

/** Active sections [id => label]. */
function results_section_options(int $campus): array
{
    $out = [];
    try {
        $stmt = ums_db()->prepare('SELECT id, program, semester, name FROM ' . tbl('sections') . ' WHERE campus_id = ? AND status = "active" ORDER BY program, semester, name');
        $stmt->bind_param('i', $campus); $stmt->execute();
        $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['program'] . ' · Sem ' . (int)$x['semester'] . ' · ' . $x['name'];
        $stmt->close();
    } catch (Throwable $t) {}
    return $out;
}

/** A section row or null. */
function results_section_row(int $id): ?array
{
    try {
        $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('sections') . ' WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        return $row ?: null;
    } catch (Throwable $t) { return null; }
}

function results_url(string $path = ''): string
{
    return UMS_URL . '/modules/results/' . $path;
}
