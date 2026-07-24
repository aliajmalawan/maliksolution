<?php
use App\Models\Setting;

$bgUrl = media_url($content['background'] ?? null);
$ctaContact = Setting::group('contact');
$isDemo = ($content['button_url'] ?? '') === '#demo-modal';
?>
<section class="edu-section">
    <div class="container animate-on-scroll reveal-zoom">
        <div class="edu-cta-banner text-white" style="<?= $bgUrl ? 'background-image: linear-gradient(100deg, rgba(20,12,60,.92), rgba(var(--edu-primary-rgb),.86)), url(' . e($bgUrl) . '); background-size: cover; background-position: center;' : '' ?>">
            <div class="row align-items-center g-4 g-lg-5 position-relative">
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="edu-slide-eyebrow"><i class="bi bi-megaphone-fill me-1"></i>Admissions Open</span>
                    <?php if (!empty($content['heading'])): ?>
                        <h2 class="fw-bold mb-3 text-white display-6"><?= e($content['heading']) ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($content['subtext'])): ?>
                        <p class="lead mb-4" style="color: #D6D9F5; max-width: 34rem;"><?= e($content['subtext']) ?></p>
                    <?php endif; ?>
                    <div class="d-flex gap-3 flex-wrap justify-content-center justify-content-lg-start mb-4">
                        <?php if (!empty($content['button_text'])): ?>
                            <a href="<?= $isDemo ? '#' : e($content['button_url'] ?? '#') ?>" class="btn btn-secondary btn-lg edu-btn-pulse" <?= $isDemo ? 'data-bs-toggle="modal" data-bs-target="#demoModal"' : '' ?>>
                                <i class="bi bi-calendar-check me-2"></i><?= e($content['button_text']) ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?= url('/contact') ?>" class="btn btn-outline-light btn-lg">Contact Us</a>
                    </div>
                    <ul class="edu-cta-trust list-unstyled d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start mb-0">
                        <li><i class="bi bi-check-circle-fill me-1"></i>Pre-School to Intermediate</li>
                        <li><i class="bi bi-check-circle-fill me-1"></i>Affordable fees</li>
                        <li><i class="bi bi-check-circle-fill me-1"></i>Qualified faculty</li>
                    </ul>
                </div>
                <div class="col-lg-5">
                    <div class="edu-cta-card">
                        <div class="edu-cta-card-title"><i class="bi bi-headset me-2"></i>Talk to Admissions</div>
                        <?php if (!empty($ctaContact['phone'])): ?>
                            <a class="edu-cta-contact" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $ctaContact['phone'])) ?>">
                                <span class="edu-cta-contact-icon"><i class="bi bi-telephone-fill"></i></span>
                                <span><small>Call us</small><strong><?= e($ctaContact['phone']) ?></strong></span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($ctaContact['whatsapp'])): ?>
                            <a class="edu-cta-contact" href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $ctaContact['whatsapp'])) ?>" target="_blank" rel="noopener noreferrer">
                                <span class="edu-cta-contact-icon" style="background: rgba(37, 211, 102, 0.18); color: #25D366;"><i class="bi bi-whatsapp"></i></span>
                                <span><small>WhatsApp</small><strong><?= e($ctaContact['whatsapp']) ?></strong></span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($ctaContact['address'])): ?>
                            <div class="edu-cta-contact">
                                <span class="edu-cta-contact-icon"><i class="bi bi-geo-alt-fill"></i></span>
                                <span><small>Visit us</small><strong><?= e($ctaContact['address']) ?></strong></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
