<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$admissionsPage = $pdo->query("SELECT * FROM pages WHERE slug = 'admissions'")->fetch();
$formError = '';
$formSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $formError = 'Your session expired. Please try submitting the form again.';
    } else {
        $student_name = trim((string)($_POST['student_name'] ?? ''));
        $parent_name = trim((string)($_POST['parent_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $class_applying = trim((string)($_POST['class_applying'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($student_name === '' || $parent_name === '' || $phone === '') {
            $formError = 'Please fill in the student name, parent name, and phone number.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO inquiries (student_name, parent_name, phone, email, class_applying, message) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$student_name, $parent_name, $phone, $email, $class_applying, $message]);
            notify($pdo, 'inquiry', 'New admission inquiry', $student_name . ($class_applying !== '' ? ' — ' . $class_applying : ''), 'inquiries.php');
            $formSuccess = true;
        }
    }
}

$pageTitle = 'Admissions';
$pageDescription = $admissionsPage['meta_description'] ?? '';
$breadcrumbs = [['label' => 'Admissions']];
require_once __DIR__ . '/includes/header.php';

$steps = [
    ['num' => 1, 'title' => 'Submit Inquiry', 'desc' => 'Fill out the admission inquiry form below or visit our campus.'],
    ['num' => 2, 'title' => 'Campus Visit', 'desc' => 'Our team will contact you to schedule a campus tour and meeting.'],
    ['num' => 3, 'title' => 'Assessment', 'desc' => 'A short, age-appropriate assessment for the applying student.'],
    ['num' => 4, 'title' => 'Confirm Enrollment', 'desc' => 'Complete documentation and fee submission to confirm your seat.'],
];
?>

<div class="page-banner">
  <div class="container">
    <h1>Admissions</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Join Alpine</span>
      <h2><?= e($admissionsPage['title'] ?? 'Admissions') ?></h2>
      <p><?= e(strip_tags($admissionsPage['body'] ?? '')) ?></p>
    </div>
    <div class="steps-grid">
      <?php foreach ($steps as $i => $step): ?>
      <div class="step-card" data-anim="up" data-anim-delay="<?= $i * 90 ?>">
        <div class="step-num"><?= $step['num'] ?></div>
        <h3 style="font-size:17px;"><?= e($step['title']) ?></h3>
        <p style="color:var(--text-light);font-size:14px;"><?= e($step['desc']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <?php $admissionsOpen = get_setting($pdo, 'admission_open', '1') === '1'; ?>
    <div class="apply-cta" data-anim="up">
      <?php if ($admissionsOpen): ?>
        <span class="apply-cta-badge">Admissions Open — Session <?= e(get_setting($pdo, 'admission_session', '2026-27')) ?></span>
        <h3>Apply Online in Minutes</h3>
        <p>Submit the full application form with your documents and get an application number to track your progress.</p>
        <div class="cta-actions" style="margin-top:22px;">
          <a href="admission-form.php" class="btn btn-primary">Start Online Application</a>
          <a href="application-status.php" class="btn btn-outline">Track My Application</a>
        </div>
      <?php else: ?>
        <h3>Admissions Are Currently Closed</h3>
        <p>Online applications will reopen for the next session. Send us an inquiry below and we'll notify you.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Get Started</span>
      <h2>Admission Inquiry Form</h2>
      <p>Fill in the details below and our admissions team will get in touch with you shortly.</p>
    </div>

    <?php if ($formSuccess): ?>
      <div role="status" class="alert alert-success">Thank you! Your inquiry has been received. Our admissions team will contact you soon.</div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div role="alert" class="alert alert-error"><?= e($formError) ?></div>
    <?php endif; ?>

    <div class="inquiry-grid">
    <form class="form-card" method="post" action="admissions.php">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="student_name">Student's Full Name *</label>
          <input type="text" id="student_name" name="student_name" required>
        </div>
        <div class="form-group">
          <label for="parent_name">Parent/Guardian Name *</label>
          <input type="text" id="parent_name" name="parent_name" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="phone">Phone Number *</label>
          <input type="tel" id="phone" name="phone" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email">
        </div>
      </div>
      <div class="form-group">
        <label for="class_applying">Class Applying For</label>
        <select id="class_applying" name="class_applying">
          <option value="">Select a class</option>
          <option>Montessori / Playgroup</option>
          <option>Prep</option>
          <?php for ($i = 1; $i <= 10; $i++): ?>
          <option>Grade <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="message">Message (Optional)</label>
        <textarea id="message" name="message" placeholder="Any questions or additional details..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Submit Inquiry</button>
    </form>

    <aside class="inquiry-aside">
      <?php
      $inqPhone = get_setting($pdo, 'phone');
      $inqEmail = get_setting($pdo, 'email');
      $inqAddress = get_setting($pdo, 'address');
      ?>
      <div class="contact-info-card">
        <h3>Talk to Admissions</h3>
        <p style="opacity:.85;font-size:14px;margin-bottom:20px;">Prefer to speak with someone? Call, email, or visit us — we're happy to help.</p>
        <?php if ($inqPhone): ?>
        <div class="contact-info-item">
          <span class="icon" aria-hidden="true"><?= edu_icon('phone') ?></span>
          <div><small style="opacity:.75;">Call Us</small><br><a href="tel:<?= e($inqPhone) ?>" style="color:#fff;font-weight:600;"><?= e($inqPhone) ?></a></div>
        </div>
        <?php endif; ?>
        <?php if ($inqEmail): ?>
        <div class="contact-info-item">
          <span class="icon" aria-hidden="true"><?= edu_icon('mail') ?></span>
          <div><small style="opacity:.75;">Email Us</small><br><a href="mailto:<?= e($inqEmail) ?>" style="color:#fff;font-weight:600;"><?= e($inqEmail) ?></a></div>
        </div>
        <?php endif; ?>
        <?php if ($inqAddress): ?>
        <div class="contact-info-item" style="margin-bottom:0;">
          <span class="icon" aria-hidden="true"><?= edu_icon('map-pin') ?></span>
          <div><small style="opacity:.75;">Visit Us</small><br><span style="font-weight:600;"><?= e($inqAddress) ?></span></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="docs-card">
        <h3 style="font-size:18px;margin-bottom:12px;">Documents You'll Need</h3>
        <ul class="check-list" style="margin:0;">
          <li>Student's B-Form / birth certificate copy</li>
          <li>Two recent passport-size photographs</li>
          <li>Previous school result card (if any)</li>
          <li>Parent / guardian CNIC copy</li>
        </ul>
      </div>
    </aside>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
