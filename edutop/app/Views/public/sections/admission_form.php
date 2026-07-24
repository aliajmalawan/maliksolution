<?php

use App\Core\Sanitizer;

$fields = is_array($content['fields'] ?? null) ? $content['fields'] : [];
$sectionId = (int) ($section['id'] ?? 0);

$optionsFromText = static function (string $text): array {
    $lines = array_filter(array_map('trim', explode("\n", $text)), fn($l) => $l !== '');
    return array_values($lines);
};
?>
<section class="edu-section">
    <div class="container animate-on-scroll">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5">
                <span class="edu-eyebrow">Admissions Open</span>
                <h2 class="fw-bold mb-2"><?= e($content['heading']) ?></h2>
                <?php if (!empty($content['subtext'])): ?>
                    <p class="mx-auto" style="color: var(--edu-text); max-width: 40rem;"><?= e($content['subtext']) ?></p>
                <?php endif; ?>
                <div class="edu-heading-divider mx-auto"></div>
            </div>
        <?php endif; ?>

        <?php if (empty($fields)): ?>
            <p class="text-center" style="color: var(--edu-text);">This form has no fields configured yet. Add fields to this section in the admin panel.</p>
        <?php else: ?>
        <form method="POST" action="<?= url('/admissions/apply') ?>" class="edu-admission-form mx-auto needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="redirect_to" value="<?= e(current_path()) ?>">
            <input type="hidden" name="section_id" value="<?= $sectionId ?>">
            <div class="d-none" aria-hidden="true">
                <label>Leave this field blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <?php
            $blockOpen = false;
            $blockNum = 0;
            foreach ($fields as $field):
                $type = $field['field_type'] ?? 'text';

                if ($type === 'heading'):
                    if ($blockOpen): ?></div></div><?php endif;
                    $blockNum++;
                    $blockOpen = true;
                    ?>
                    <div class="edu-form-block">
                        <div class="edu-form-block-title"><span class="edu-form-step"><?= $blockNum ?></span> <?= e($field['label'] ?? '') ?></div>
                        <div class="row g-3">
                    <?php
                    continue;
                endif;

                if (!$blockOpen):
                    $blockOpen = true;
                    ?><div class="edu-form-block"><div class="row g-3"><?php
                endif;

                $key = Sanitizer::fieldKey((string) ($field['name'] ?? ''));
                $label = (string) ($field['label'] ?? '');
                $required = !empty($field['required']);
                $placeholder = (string) ($field['placeholder'] ?? '');
                $width = in_array((string) ($field['width'] ?? '12'), ['12', '6', '4'], true) ? (string) $field['width'] : '12';
                $options = $optionsFromText((string) ($field['options'] ?? ''));
            ?>
                <div class="col-md-<?= e($width) ?>">
                    <?php if ($type !== 'checkbox'): ?>
                        <label class="form-label fw-semibold"><?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></label>
                    <?php endif; ?>

                    <?php if (in_array($type, ['text', 'tel', 'email', 'date'], true)): ?>
                        <input type="<?= e($type) ?>" name="<?= e($key) ?>" class="form-control" placeholder="<?= e($placeholder) ?>" <?= $required ? 'required' : '' ?>>
                        <div class="invalid-feedback">This field is empty, please fill it.</div>

                    <?php elseif ($type === 'textarea'): ?>
                        <textarea name="<?= e($key) ?>" class="form-control" rows="2" placeholder="<?= e($placeholder) ?>" <?= $required ? 'required' : '' ?>></textarea>
                        <div class="invalid-feedback">This field is empty, please fill it.</div>

                    <?php elseif ($type === 'select'): ?>
                        <select name="<?= e($key) ?>" class="form-select" <?= $required ? 'required' : '' ?>>
                            <option value="">— Select —</option>
                            <?php foreach ($options as $opt): ?>
                                <option><?= e($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">This field is empty, please fill it.</div>

                    <?php elseif ($type === 'radio'): ?>
                        <div class="btn-group w-100 edu-radio-group" role="group" data-radio-name="<?= e($key) ?>">
                            <?php foreach ($options as $i => $opt): ?>
                                <?php $optId = 'r_' . $key . '_' . $i; ?>
                                <input type="radio" class="btn-check" name="<?= e($key) ?>" id="<?= e($optId) ?>" value="<?= e($opt) ?>" <?= ($required && $i === 0) ? 'required' : '' ?>>
                                <label class="btn btn-outline-primary" for="<?= e($optId) ?>"><?= e($opt) ?></label>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($required): ?><div class="invalid-feedback edu-radio-feedback">Please make a selection.</div><?php endif; ?>

                    <?php elseif ($type === 'checkbox'): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="chk_<?= e($key) ?>" name="<?= e($key) ?>" value="1" <?= $required ? 'required' : '' ?>>
                            <label class="form-check-label" for="chk_<?= e($key) ?>"><?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        </div>
                        <div class="invalid-feedback">Please check this box to continue.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($blockOpen): ?></div></div><?php endif; ?>

            <?= recaptcha_field() ?>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5"><i class="bi bi-send-fill me-2"></i>Submit Application</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</section>
