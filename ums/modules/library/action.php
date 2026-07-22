<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Library — controller (book CRUD + issue + return). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(lib_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

function book_formdata(): array
{
    $cat = in_array($_POST['category'] ?? '', LIB_CATEGORIES, true) ? $_POST['category'] : 'General';
    $total = max(1, (int)($_POST['total_copies'] ?? 1));
    return [
        'title' => trim((string)($_POST['title'] ?? '')),
        'author' => trim((string)($_POST['author'] ?? '')),
        'isbn' => trim((string)($_POST['isbn'] ?? '')),
        'category' => $cat,
        'publisher' => trim((string)($_POST['publisher'] ?? '')),
        'total_copies' => $total,
        'shelf' => trim((string)($_POST['shelf'] ?? '')),
        'status' => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
    ];
}

if ($action === 'create') {
    $f = book_formdata();
    if ($f['title'] === '') { flash_set('error', 'Book title is required.'); redirect(lib_url('create.php')); }
    $avail = $f['total_copies'];
    $stmt = $db->prepare('INSERT INTO ' . tbl('books') . '
        (campus_id, title, author, isbn, category, publisher, total_copies, available_copies, shelf, status)
        VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('isssssiiss', $campus, $f['title'], $f['author'], $f['isbn'], $f['category'], $f['publisher'], $f['total_copies'], $avail, $f['shelf'], $f['status']);
    $stmt->execute(); $stmt->close();
    ums_log('book_create', 'Added book ' . $f['title']);
    flash_set('success', 'Book added to the catalog.');
    redirect(lib_url('index.php'));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = book_find($id);
    if (!$cur) { flash_set('error', 'Book not found.'); redirect(lib_url('index.php')); }
    $f = book_formdata();
    if ($f['title'] === '') { flash_set('error', 'Book title is required.'); redirect(lib_url('edit.php?id=' . $id)); }
    // Adjust availability by the change in total copies (never below issued-out count)
    $issuedOut = (int)$cur['total_copies'] - (int)$cur['available_copies'];
    $avail = max(0, $f['total_copies'] - $issuedOut);
    $stmt = $db->prepare('UPDATE ' . tbl('books') . '
        SET title=?, author=?, isbn=?, category=?, publisher=?, total_copies=?, available_copies=?, shelf=?, status=? WHERE id=?');
    $stmt->bind_param('sssssiissi', $f['title'], $f['author'], $f['isbn'], $f['category'], $f['publisher'], $f['total_copies'], $avail, $f['shelf'], $f['status'], $id);
    $stmt->execute(); $stmt->close();
    ums_log('book_update', 'Updated book #' . $id);
    flash_set('success', 'Book updated.');
    redirect(lib_url('index.php'));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = book_find($id);
    if ($cur) {
        $out = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('book_issues') . ' WHERE book_id = ' . $id . ' AND status = "issued"')->fetch_assoc()['c'];
        if ($out > 0) { flash_set('error', "Cannot delete — $out copy(ies) are currently issued out."); redirect(lib_url('index.php')); }
        $db->query('DELETE FROM ' . tbl('book_issues') . ' WHERE book_id = ' . $id);
        $stmt = $db->prepare('DELETE FROM ' . tbl('books') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('book_delete', 'Deleted book ' . $cur['title']);
        flash_set('success', 'Book deleted.');
    }
    redirect(lib_url('index.php'));
}

if ($action === 'issue') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $stuId  = (int)($_POST['student_id'] ?? 0);
    $book = book_find($bookId);
    if (!$book || (int)$book['available_copies'] <= 0) { flash_set('error', 'That book is not available.'); redirect(lib_url('issue.php')); }
    if ($stuId <= 0 || !array_key_exists($stuId, lib_student_options($campus))) { flash_set('error', 'Select a valid student.'); redirect(lib_url('issue.php')); }
    $issueDate = date('Y-m-d');
    $days = max(1, (int)($_POST['days'] ?? 14));
    $dueDate = date('Y-m-d', strtotime("+$days days"));

    $stmt = $db->prepare('INSERT INTO ' . tbl('book_issues') . ' (campus_id, book_id, student_id, issue_date, due_date) VALUES (?,?,?,?,?)');
    $stmt->bind_param('iiiss', $campus, $bookId, $stuId, $issueDate, $dueDate);
    $stmt->execute(); $stmt->close();
    $db->query('UPDATE ' . tbl('books') . ' SET available_copies = available_copies - 1 WHERE id = ' . $bookId . ' AND available_copies > 0');

    ums_log('book_issue', "Issued '{$book['title']}' · due $dueDate");
    flash_set('success', "Book issued. Due " . date('d M Y', strtotime($dueDate)) . '.');
    redirect(lib_url('issue.php'));
}

if ($action === 'return') {
    $id = (int)($_POST['id'] ?? 0);
    $iss = issue_find($id);
    if (!$iss || $iss['status'] !== 'issued') { flash_set('error', 'Issue record not found.'); redirect(lib_url('issue.php')); }
    $returnDate = date('Y-m-d');
    $fine = lib_fine($iss['due_date'], $returnDate);
    $finePaid = ($fine > 0 && !empty($_POST['fine_paid'])) ? 1 : ($fine <= 0 ? 1 : 0);
    $status = 'returned';

    $stmt = $db->prepare('UPDATE ' . tbl('book_issues') . ' SET return_date=?, fine=?, fine_paid=?, status=? WHERE id=?');
    $stmt->bind_param('sdisi', $returnDate, $fine, $finePaid, $status, $id);
    $stmt->execute(); $stmt->close();
    $db->query('UPDATE ' . tbl('books') . ' SET available_copies = available_copies + 1 WHERE id = ' . (int)$iss['book_id']);

    ums_log('book_return', 'Returned issue #' . $id . ($fine > 0 ? ' · fine ' . money($fine) : ''));
    flash_set('success', $fine > 0 ? 'Returned with a fine of ' . money($fine) . ($finePaid ? ' (paid).' : ' (unpaid).') : 'Book returned.');
    redirect(lib_url('issue.php'));
}

redirect(lib_url('index.php'));
