<?php
use App\Models\Setting;

$cfContact = Setting::group('contact');
$cfSocial = Setting::group('social');
$cfSocialIcons = array_filter([
    'facebook' => $cfSocial['facebook'] ?? '',
    'twitter-x' => $cfSocial['twitter'] ?? '',
    'linkedin' => $cfSocial['linkedin'] ?? '',
    'instagram' => $cfSocial['instagram'] ?? '',
    'youtube' => $cfSocial['youtube'] ?? '',
]);
?>
<section class="edu-section">
    <div class="container animate-on-scroll">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5">
                <span class="edu-eyebrow">Get In Touch</span>
                <h2 class="fw-bold mb-2"><?= e($content['heading']) ?></h2>
                <?php if (!empty($content['subtext'])): ?>
                    <p class="mx-auto" style="color: var(--edu-text); max-width: 38rem;"><?= e($content['subtext']) ?></p>
                <?php endif; ?>
                <div class="edu-heading-divider mx-auto"></div>
            </div>
        <?php endif; ?>

        <div class="edu-contact-wrap">
            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="edu-contact-info h-100">
                        <h5 class="text-white fw-bold mb-2">Contact Information</h5>
                        <p class="mb-4" style="color: #D6D9F5; font-size: .92rem;">Reach us directly, or send a message with the form — we usually respond the same day.</p>

                        <?php if (!empty($cfContact['phone'])): ?>
                            <a class="edu-cta-contact" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $cfContact['phone'])) ?>">
                                <span class="edu-cta-contact-icon"><i class="bi bi-telephone-fill"></i></span>
                                <span><small>Phone</small><strong><?= e($cfContact['phone']) ?></strong></span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($cfContact['whatsapp'])): ?>
                            <a class="edu-cta-contact" href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $cfContact['whatsapp'])) ?>" target="_blank" rel="noopener noreferrer">
                                <span class="edu-cta-contact-icon" style="background: rgba(37, 211, 102, 0.18); color: #25D366;"><i class="bi bi-whatsapp"></i></span>
                                <span><small>WhatsApp</small><strong><?= e($cfContact['whatsapp']) ?></strong></span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($cfContact['email'])): ?>
                            <a class="edu-cta-contact" href="mailto:<?= e($cfContact['email']) ?>">
                                <span class="edu-cta-contact-icon"><i class="bi bi-envelope-fill"></i></span>
                                <span><small>Email</small><strong><?= e($cfContact['email']) ?></strong></span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($cfContact['address'])): ?>
                            <div class="edu-cta-contact">
                                <span class="edu-cta-contact-icon"><i class="bi bi-geo-alt-fill"></i></span>
                                <span><small>Address</small><strong><?= e($cfContact['address']) ?></strong></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfSocialIcons)): ?>
                            <div class="d-flex gap-2 mt-4">
                                <?php foreach ($cfSocialIcons as $icon => $link): ?>
                                    <a href="<?= e(safe_url($link)) ?>" class="edu-social-btn" target="_blank" rel="noopener noreferrer" aria-label="<?= e($icon) ?>"><i class="bi bi-<?= e($icon) ?>"></i></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-7">
                    <form method="POST" action="<?= url('/leads/contact') ?>" class="edu-contact-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect_to" value="<?= e(current_path()) ?>">
                        <div class="d-none" aria-hidden="true">
                            <label>Leave this field blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Full name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="03xx-xxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Subject</label>
                                <select name="subject" class="form-select">
                                    <option value="Admissions inquiry">Admissions inquiry</option>
                                    <option value="Campus visit">Campus visit</option>
                                    <option value="Fees & scholarships">Fees &amp; scholarships</option>
                                    <option value="Careers">Careers</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Message</label>
                                <textarea name="message" class="form-control" rows="5" placeholder="How can we help you?"></textarea>
                            </div>
                        </div>
                        <?= recaptcha_field() ?>
                        <button type="submit" class="btn btn-primary btn-lg mt-4 px-5">
                            <i class="bi bi-send-fill me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
