<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Leads &amp; Applications</h1>
</div>

<?php $canManage = can('leads.manage'); ?>

<!-- Status summary for the current tab -->
<div class="row g-3 mb-3">
    <?php
    $statusCards = [
        ['label' => 'New', 'count' => $statusCounts['new'], 'class' => 'text-warning', 'icon' => 'bi-envelope-exclamation-fill', 'status' => 'new'],
        ['label' => 'Contacted', 'count' => $statusCounts['contacted'], 'class' => 'text-primary', 'icon' => 'bi-telephone-outbound-fill', 'status' => 'contacted'],
        ['label' => 'Closed', 'count' => $statusCounts['closed'], 'class' => 'text-success', 'icon' => 'bi-check-circle-fill', 'status' => 'closed'],
        ['label' => 'Total', 'count' => array_sum($statusCounts), 'class' => 'text-secondary', 'icon' => 'bi-inbox-fill', 'status' => ''],
    ];
    $baseQuery = $typeFilter !== '' ? 'type=' . urlencode($typeFilter) . '&' : '';
    ?>
    <?php foreach ($statusCards as $card): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= url('/admin/leads?' . $baseQuery . ($card['status'] !== '' ? 'status=' . $card['status'] : '')) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 <?= $statusFilter === $card['status'] && !($card['status'] === '' && $statusFilter !== '') ? 'border-start border-4 border-primary' : '' ?>">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small"><?= e($card['label']) ?></div>
                            <div class="fs-3 fw-bold text-body"><?= number_format($card['count']) ?></div>
                        </div>
                        <i class="bi <?= $card['icon'] ?> fs-2 <?= $card['class'] ?>"></i>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<!-- Type tabs + search -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link <?= $typeFilter === '' ? 'active' : '' ?>" href="<?= url('/admin/leads') ?>">All</a></li>
        <li class="nav-item"><a class="nav-link <?= $typeFilter === 'admission' ? 'active' : '' ?>" href="<?= url('/admin/leads?type=admission') ?>">Admissions <span class="badge bg-light text-dark ms-1"><?= $typeCounts['admission'] ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $typeFilter === 'demo' ? 'active' : '' ?>" href="<?= url('/admin/leads?type=demo') ?>">Visit Requests <span class="badge bg-light text-dark ms-1"><?= $typeCounts['demo'] ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $typeFilter === 'contact' ? 'active' : '' ?>" href="<?= url('/admin/leads?type=contact') ?>">Contact <span class="badge bg-light text-dark ms-1"><?= $typeCounts['contact'] ?></span></a></li>
    </ul>
    <form method="GET" action="<?= url('/admin/leads') ?>" class="d-flex gap-2 flex-grow-1 flex-md-grow-0" style="max-width: 420px;">
        <?php if ($typeFilter !== ''): ?><input type="hidden" name="type" value="<?= e($typeFilter) ?>"><?php endif; ?>
        <?php if ($statusFilter !== ''): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name, phone, email…" value="<?= e($search) ?>">
        <button type="submit" class="btn btn-sm btn-primary flex-shrink-0"><i class="bi bi-search"></i></button>
        <?php if ($search !== ''): ?>
            <a href="<?= url('/admin/leads' . ($typeFilter !== '' ? '?type=' . urlencode($typeFilter) : '')) ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php
$typeBadgeClass = ['demo' => 'bg-info', 'admission' => 'bg-success', 'contact' => 'bg-secondary'];
$statusBadgeClass = ['new' => 'bg-warning text-dark', 'contacted' => 'bg-primary', 'closed' => 'bg-success'];
$statusBorderClass = ['new' => 'border-warning', 'contacted' => 'border-primary', 'closed' => 'border-success'];
$classApplyingFor = static function (array $lead): ?string {
    if ($lead['type'] === 'admission' && preg_match('/Class Applying For: (.+)/', (string) $lead['message'], $m)) {
        return $m[1];
    }
    return null;
};
?>

<!-- Mobile card list: a wide table doesn't work well on small screens, even
     scrollable, so leads render as stacked cards below md instead. -->
<div class="d-md-none">
    <?php if (empty($leads)): ?>
        <p class="text-muted text-center py-4">No leads match this filter.</p>
    <?php endif; ?>
    <?php foreach ($leads as $lead): ?>
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="fw-semibold"><?= e($lead['name']) ?></div>
                        <?php if ($classApplyingFor($lead)): ?>
                            <div class="text-muted small"><?= e($classApplyingFor($lead)) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="badge <?= $typeBadgeClass[$lead['type']] ?? 'bg-secondary' ?> flex-shrink-0"><?= e($lead['type']) ?></span>
                </div>
                <div class="small mb-2">
                    <div><?= $lead['phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $lead['phone'])) . '">' . e($lead['phone']) . '</a>' : '<span class="text-muted">No phone</span>' ?></div>
                    <div class="text-truncate"><?= $lead['email'] ? e($lead['email']) : '<span class="text-muted">No email</span>' ?></div>
                    <div class="text-muted"><?= e(date('M j, Y H:i', strtotime($lead['created_at']))) ?></div>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <?php if ($canManage): ?>
                        <form method="POST" action="<?= url('/admin/leads/' . $lead['id'] . '/status') ?>" class="flex-grow-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="return" value="index">
                            <select name="status" class="form-select form-select-sm <?= $statusBorderClass[$lead['status']] ?? '' ?>" data-auto-submit-select>
                                <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $lead['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php else: ?>
                        <span class="badge <?= $statusBadgeClass[$lead['status']] ?? 'bg-secondary' ?>"><?= e($lead['status']) ?></span>
                    <?php endif; ?>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <a href="<?= url('/admin/leads/' . $lead['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                        <?php if ($canManage): ?>
                            <form method="POST" action="<?= url('/admin/leads/' . $lead['id'] . '/delete') ?>" data-confirm="Delete this lead permanently?">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Applicant / Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th style="min-width:140px;">Status</th>
                    <th>Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr><td colspan="7" class="text-muted py-4 text-center">No leads match this filter.</td></tr>
                <?php endif; ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($lead['name']) ?></div>
                            <?php if ($classApplyingFor($lead)): ?>
                                <div class="text-muted small"><?= e($classApplyingFor($lead)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= $lead['phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $lead['phone'])) . '">' . e($lead['phone']) . '</a>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="small"><?= $lead['email'] ? e($lead['email']) : '<span class="text-muted">—</span>' ?></td>
                        <td><span class="badge <?= $typeBadgeClass[$lead['type']] ?? 'bg-secondary' ?>"><?= e($lead['type']) ?></span></td>
                        <td>
                            <?php if ($canManage): ?>
                                <form method="POST" action="<?= url('/admin/leads/' . $lead['id'] . '/status') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="return" value="index">
                                    <select name="status" class="form-select form-select-sm <?= $statusBorderClass[$lead['status']] ?? '' ?>" data-auto-submit-select>
                                        <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $val => $label): ?>
                                            <option value="<?= $val ?>" <?= $lead['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="badge <?= $statusBadgeClass[$lead['status']] ?? 'bg-secondary' ?>"><?= e($lead['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-nowrap"><?= e(date('M j, Y H:i', strtotime($lead['created_at']))) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= url('/admin/leads/' . $lead['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
                            <?php if ($canManage): ?>
                                <form method="POST" action="<?= url('/admin/leads/' . $lead['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this lead permanently?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
