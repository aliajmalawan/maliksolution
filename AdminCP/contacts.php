<?php
declare(strict_types=1);
$page_title = 'Contact Queries';
$active_nav = 'contacts';
include 'header.php';

$contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC");
$total    = mysqli_num_rows($contacts);
?>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-envelope me-2 text-primary"></i>Contact Queries</h1>
    <p>Messages submitted via the public contact form &mdash; <?= $total ?> total</p>
  </div>
  <a href="../contact.php" target="_blank" class="btn btn-outline-primary btn-sm">
    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View Contact Form
  </a>
</div>

<!-- Contacts Table -->
<div class="cardx">
  <?php if ($total > 0): ?>
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Date</th>
            <th>Received</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($row = mysqli_fetch_assoc($contacts)): ?>
            <tr>
              <td class="text-muted small"><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
              <td class="small">
                <?php if ($row['email']): ?>
                  <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="text-decoration-none">
                    <?= htmlspecialchars($row['email']) ?>
                  </a>
                <?php else: ?>&mdash;<?php endif; ?>
              </td>
              <td class="small"><?= $row['phone'] ? htmlspecialchars($row['phone']) : '&mdash;' ?></td>
              <td class="small"><?= $row['subject'] ? htmlspecialchars($row['subject']) : '&mdash;' ?></td>
              <td class="small" style="max-width:250px">
                <?php $msg_full = htmlspecialchars($row['message'] ?? ''); ?>
                <?php if (mb_strlen($row['message'] ?? '') > 60): ?>
                  <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $msg_full ?>">
                    <?= htmlspecialchars(mb_substr($row['message'] ?? '', 0, 60)) ?>…
                    <i class="fa-solid fa-eye text-primary ms-1" style="font-size:.7rem"></i>
                  </span>
                <?php else: ?>
                  <?= $msg_full ?>
                <?php endif; ?>
              </td>
              <td class="text-muted small">
                <?= $row['date'] ? date('d M Y', strtotime($row['date'])) : '&mdash;' ?>
              </td>
              <td class="text-muted small"><?= date('d M Y, g:ia', strtotime($row['created_at'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-envelope-open-text"></i>
      <p>No contact queries yet.<br>
         <a href="../contact.php" target="_blank">View the contact form</a></p>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
