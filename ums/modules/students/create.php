<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$s = [];
// Optional prefill from an approved/enrolled admission application
$fromAdm = (int)($_GET['from_admission'] ?? 0);
if ($fromAdm > 0) {
    try {
        $st = ums_db()->prepare('SELECT * FROM ' . tbl('admissions') . ' WHERE id = ? AND campus_id = ? LIMIT 1');
        $cam = (int)$user['campus_id'];
        $st->bind_param('ii', $fromAdm, $cam); $st->execute();
        $adm = $st->get_result()->fetch_assoc(); $st->close();
        if ($adm) {
            $s = [
                'admission_id' => (int)$adm['id'],
                'name' => $adm['student_name'], 'father_name' => $adm['father_name'],
                'gender' => $adm['gender'], 'dob' => $adm['dob'], 'cnic' => $adm['cnic'],
                'email' => $adm['email'], 'phone' => $adm['phone'], 'address' => $adm['address'],
                'program' => $adm['program'], 'session' => $adm['session'],
            ];
        }
    } catch (Throwable $t) { /* admissions table unavailable */ }
}

$page_title = 'Enroll Student'; $active = 'students';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Enroll Student</h1>
  <p><a href="<?= stu_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Students</a></p></div></div>
<?php $isEdit = false; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
