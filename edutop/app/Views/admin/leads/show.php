<?php
$isAdmission = $lead['type'] === 'admission';
// Admission messages are stored as "Label: value" lines — parse for a clean table.
$applicationFields = [];
if ($isAdmission && $lead['message']) {
    foreach (explode("\n", $lead['message']) as $line) {
        if (str_contains($line, ':')) {
            [$label, $value] = explode(':', $line, 2);
            $applicationFields[trim($label)] = trim($value);
        }
    }
}
$typeBadge = ['demo' => 'bg-info', 'admission' => 'bg-success', 'contact' => 'bg-secondary'][$lead['type']] ?? 'bg-secondary';
$statusBadge = ['new' => 'bg-warning text-dark', 'contacted' => 'bg-primary', 'closed' => 'bg-success'][$lead['status']];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="<?= url('/admin/leads') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to Leads</a>
        <h1 class="h4 mb-0 mt-1">
            <?= $isAdmission ? 'Admission Application' : 'Lead' ?> &mdash; <?= e($lead['name']) ?>
            <span class="badge <?= $typeBadge ?> ms-2 fs-6"><?= e($lead['type']) ?></span>
            <span class="badge <?= $statusBadge ?> fs-6"><?= e($lead['status']) ?></span>
        </h1>
    </div>
    <div class="text-muted small">Received: <?= e(date('M j, Y H:i', strtotime($lead['created_at']))) ?></div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <?php if ($isAdmission && !empty($applicationFields)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-mortarboard-fill me-2 text-success"></i>Application Details</div>
                <table class="table table-striped mb-0">
                    <tbody>
                        <?php foreach ($applicationFields as $label => $value): ?>
                            <tr>
                                <th class="w-40 text-muted fw-semibold" style="width: 40%;"><?= e($label) ?></th>
                                <td><?= e($value) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($lead['email']): ?>
                            <tr><th class="text-muted fw-semibold">Email</th><td><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></td></tr>
                        <?php endif; ?>
                        <?php if ($lead['phone']): ?>
                            <tr><th class="text-muted fw-semibold">Phone</th><td><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $lead['phone'])) ?>"><?= e($lead['phone']) ?></a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?= e($lead['name']) ?></dd>
                        <?php if ($lead['email']): ?><dt class="col-sm-3">Email</dt><dd class="col-sm-9"><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd><?php endif; ?>
                        <?php if ($lead['phone']): ?><dt class="col-sm-3">Phone</dt><dd class="col-sm-9"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $lead['phone'])) ?>"><?= e($lead['phone']) ?></a></dd><?php endif; ?>
                        <?php if ($lead['school_name']): ?><dt class="col-sm-3">School</dt><dd class="col-sm-9"><?= e($lead['school_name']) ?></dd><?php endif; ?>
                        <?php if ($lead['message']): ?><dt class="col-sm-3">Message</dt><dd class="col-sm-9"><?= nl2br(e($lead['message'])) ?></dd><?php endif; ?>
                    </dl>
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body small text-muted">
                <i class="bi bi-shield-lock me-1"></i>Submitted from IP <?= e($lead['ip']) ?> &middot; <?= e($lead['user_agent']) ?>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <?php if (can('leads.manage')): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Update Status</h6>
                    <form method="POST" action="<?= url('/admin/leads/' . $lead['id'] . '/status') ?>" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <select name="status" class="form-select">
                            <option value="new" <?= $lead['status'] === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="contacted" <?= $lead['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                            <option value="closed" <?= $lead['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                        <button class="btn btn-primary text-nowrap">Update</button>
                    </form>
                </div>
            </div>
            <?php if ($lead['phone']): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Quick Contact</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $lead['phone'])) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-telephone me-1"></i>Call</a>
                            <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $lead['phone'])) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
                            <?php if ($lead['email']): ?>
                                <a href="mailto:<?= e($lead['email']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-envelope me-1"></i>Email</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?= url('/admin/leads/' . $lead['id'] . '/delete') ?>" data-confirm="Delete this lead permanently?">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete Lead</button>
            </form>
        <?php endif; ?>
    </div>
</div>
