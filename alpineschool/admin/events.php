<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/events.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
        flash_set('success', 'Event deleted.');
        redirect(BASE_URL . '/admin/events.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $event_date = (string)($_POST['event_date'] ?? '');
    $event_time = trim((string)($_POST['event_time'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploaded = upload_image($_FILES['image'], 'events');
        if ($uploaded) {
            $imagePath = 'uploads/' . $uploaded;
        }
    }

    if ($id > 0) {
        if ($imagePath) {
            $stmt = $pdo->prepare('UPDATE events SET title=?, description=?, event_date=?, event_time=?, location=?, image_path=? WHERE id=?');
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $imagePath, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE events SET title=?, description=?, event_date=?, event_time=?, location=? WHERE id=?');
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $id]);
        }
        flash_set('success', 'Event updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, event_time, location, image_path) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $description, $event_date, $event_time, $location, $imagePath ?? 'assets/images/logo.jpg']);
        flash_set('success', 'Event added.');
    }
    redirect(BASE_URL . '/admin/events.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$eventsList = $pdo->query('SELECT * FROM events ORDER BY event_date DESC')->fetchAll();

$pageTitle = 'Events';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Event' : 'Add New Event' ?></h2></div>
  <form method="post" action="events.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <?php if ($editing && $editing['image_path']): ?>
      <img src="<?= BASE_URL ?>/<?= e($editing['image_path']) ?>" class="current-image" alt="Current event image">
    <?php endif; ?>
    <div class="form-group">
      <label for="image">Image</label>
      <input type="file" id="image" name="image" accept="image/*">
    </div>
    <div class="form-group">
      <label for="title">Event Title</label>
      <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"><?= e($editing['description'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="event_date">Date</label>
        <input type="date" id="event_date" name="event_date" value="<?= e($editing['event_date'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="event_time">Time</label>
        <input type="text" id="event_time" name="event_time" value="<?= e($editing['event_time'] ?? '') ?>" placeholder="e.g. 9:00 AM - 2:00 PM">
      </div>
    </div>
    <div class="form-group">
      <label for="location">Location</label>
      <input type="text" id="location" name="location" value="<?= e($editing['location'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Event' : 'Add Event' ?></button>
    <?php if ($editing): ?><a href="events.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Events</h2></div>
  <div class="table-wrap">
    <?php if (empty($eventsList)): ?>
      <div class="empty-state">No events yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($eventsList as $event): ?>
        <tr>
          <td><?= e($event['title']) ?></td>
          <td><?= format_date($event['event_date']) ?></td>
          <td><?= e($event['location']) ?></td>
          <td class="actions-cell">
            <a href="events.php?edit=<?= (int)$event['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="events.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$event['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this event?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
