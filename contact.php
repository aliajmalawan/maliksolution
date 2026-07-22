<?php
declare(strict_types=1);
require_once 'includes/config.php';

// Editable content (AdminCP → Website Pages → Contact)
$pc = fn(string $k, string $d): string => get_content($conn, 'contact', $k, $d);

$current   = 'contact.php';
$pageTitle = 'Contact Us';
$pageHero  = [
    'kicker' => 'Get In Touch',
    'title'  => $pc('hero_title', 'Contact Malik Solution'),
    'lead'   => $pc('hero_lead', 'Tell us about your project — web, mobile, software, IT services, or hosting — and we will get back to you shortly.'),
];

// Ensure the contacts table exists (shared with AdminCP → Contact Queries)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    subject VARCHAR(255) DEFAULT '',
    message TEXT,
    date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $date    = date('Y-m-d');

    if ($name === '' || $message === '') {
        $error = 'Please enter your name and message.';
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO contacts (name, email, phone, subject, message, date) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $phone, $subject, $message, $date);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Your message has been sent successfully! We will get back to you shortly.';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}

$prefill_subject = trim((string)($_GET['subject'] ?? ''));

require_once 'includes/site-header.php';

$cPhone   = get_setting($conn, 'contact_phone', '+92 333 2150925');
$cEmail   = get_setting($conn, 'contact_email', 'support@maliksolution.com');
$cAddress = get_setting($conn, 'contact_address', 'Kehkashan Society, Malir Halt, Karachi, Pakistan');
?>

<!-- ══ CONTACT ══ -->
<section class="section">
  <div class="container">
    <div class="row g-4">

      <!-- Left — info -->
      <div class="col-lg-5">
        <div class="contact-card">
          <h3><i class="fa-solid fa-address-book me-2 text-primary"></i>Contact Information</h3>
          <div class="ci-row">
            <div class="ci-icon"><i class="fa-solid fa-phone"></i></div>
            <div>
              <strong>Phone / WhatsApp</strong>
              <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $cPhone)) ?>"><?= htmlspecialchars($cPhone) ?></a>
            </div>
          </div>
          <div class="ci-row">
            <div class="ci-icon"><i class="fa-solid fa-envelope"></i></div>
            <div>
              <strong>Email</strong>
              <a href="mailto:<?= htmlspecialchars($cEmail) ?>"><?= htmlspecialchars($cEmail) ?></a>
            </div>
          </div>
          <div class="ci-row">
            <div class="ci-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <strong>Office</strong>
              <span><?= nl2br(htmlspecialchars($cAddress)) ?></span>
            </div>
          </div>
          <div class="ci-row mb-0">
            <div class="ci-icon"><i class="fa-solid fa-clock"></i></div>
            <div>
              <strong>Working Hours</strong>
              <span><?= htmlspecialchars($pc('working_hours', 'Monday to Saturday, 9:00 AM – 6:00 PM')) ?></span>
            </div>
          </div>

          <div class="contact-map mt-4">
            <iframe
              src="https://www.google.com/maps?q=Malir%20Halt%2C%20Karachi%2C%20Pakistan&output=embed"
              title="Malik Solution — Kehkashan Society, Malir Halt, Karachi"
              loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
        </div>
      </div>

      <!-- Right — form -->
      <div class="col-lg-7">
        <div class="contact-card">
          <h3><i class="fa-solid fa-paper-plane me-2 text-primary"></i>Send Us a Message</h3>

          <?php if ($success !== ''): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?></div>
          <?php elseif ($error !== ''): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="c-name">Your Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="c-name" name="name" required placeholder="Full name">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="c-email">Email</label>
                <input type="email" class="form-control" id="c-email" name="email" placeholder="you@example.com">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="c-phone">Phone</label>
                <input type="text" class="form-control" id="c-phone" name="phone" placeholder="+92 3xx xxxxxxx">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="c-subject">Subject</label>
                <input type="text" class="form-control" id="c-subject" name="subject"
                       value="<?= htmlspecialchars($prefill_subject) ?>" placeholder="e.g. New website project">
              </div>
              <div class="col-12">
                <label class="form-label" for="c-message">Your Message <span class="text-danger">*</span></label>
                <textarea class="form-control" id="c-message" name="message" rows="5" required
                          placeholder="Tell us about your project, timeline, and budget…"></textarea>
              </div>
              <div class="col-12">
                <button type="submit" name="submit_contact" value="1" class="btn-grad border-0">
                  <i class="fa-solid fa-paper-plane me-2"></i>Send Message
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>

    <!-- ══ POPULAR REQUESTS ══ -->
    <div class="text-center mt-5 mb-4">
      <div class="section-kicker">Popular Requests</div>
      <h2 class="section-title mb-0" style="font-size:1.4rem">Not Sure Where to Start? Pick a Service</h2>
    </div>
    <div class="row g-3">
      <div class="col-sm-6 col-xl-3">
        <a href="web-development.php" class="req-tile">
          <div class="req-icon"><i class="fa-solid fa-code"></i></div>
          <div class="req-text">
            <strong>Business Website</strong>
            <span>Websites &amp; e-commerce stores</span>
          </div>
          <i class="fa-solid fa-arrow-right req-arrow"></i>
        </a>
      </div>
      <div class="col-sm-6 col-xl-3">
        <a href="mobile-app.php" class="req-tile">
          <div class="req-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
          <div class="req-text">
            <strong>Mobile App</strong>
            <span>Android &amp; iOS applications</span>
          </div>
          <i class="fa-solid fa-arrow-right req-arrow"></i>
        </a>
      </div>
      <div class="col-sm-6 col-xl-3">
        <a href="it-services.php" class="req-tile">
          <div class="req-icon"><i class="fa-solid fa-server"></i></div>
          <div class="req-text">
            <strong>IT Services</strong>
            <span>Managed IT &amp; support contracts</span>
          </div>
          <i class="fa-solid fa-arrow-right req-arrow"></i>
        </a>
      </div>
      <div class="col-sm-6 col-xl-3">
        <a href="hosting.php" class="req-tile">
          <div class="req-icon"><i class="fa-solid fa-cloud"></i></div>
          <div class="req-text">
            <strong>Web Hosting</strong>
            <span>Plans from Rs.1,999/yr</span>
          </div>
          <i class="fa-solid fa-arrow-right req-arrow"></i>
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
