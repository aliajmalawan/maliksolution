<h1 class="h4 mb-4">Dashboard</h1>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Users</div>
                <div class="fs-3 fw-bold"><?= (int) $totalUsers ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Blogs</div>
                <div class="fs-3 fw-bold"><?= (int) $totalBlogs ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Leads</div>
                <div class="fs-3 fw-bold"><?= (int) $totalLeads ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Visitors</div>
                <div class="fs-3 fw-bold"><?= (int) $totalVisitors ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Page Views — Last 30 Days</div>
            <div class="card-body">
                <canvas id="visitorsChart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Top Pages</div>
            <ul class="list-group list-group-flush">
                <?php if (empty($topPages)): ?>
                    <li class="list-group-item text-muted">No page views recorded yet.</li>
                <?php endif; ?>
                <?php foreach ($topPages as $entry): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate" style="max-width: 70%;"><?= e($entry['url']) ?></span>
                        <span class="badge bg-light text-dark" style="font-variant-numeric: tabular-nums;"><?= (int) $entry['views'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                Recent Visit Requests
                <a href="<?= url('/admin/leads?type=demo') ?>" class="small">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($recentDemoRequests)): ?>
                    <li class="list-group-item text-muted">No demo requests yet.</li>
                <?php endif; ?>
                <?php foreach ($recentDemoRequests as $entry): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div><?= e($entry['name']) ?> &middot; <?= e($entry['email']) ?><?= $entry['school_name'] ? ' — ' . e($entry['school_name']) : '' ?></div>
                            <div class="text-muted small"><?= e($entry['created_at']) ?></div>
                        </div>
                        <a href="<?= url('/admin/leads/' . $entry['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                Latest Activity
                <a href="<?= url('/admin/logs/activity') ?>" class="small">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($recentActivity)): ?>
                    <li class="list-group-item text-muted">No activity yet.</li>
                <?php endif; ?>
                <?php foreach ($recentActivity as $entry): ?>
                    <li class="list-group-item">
                        <div><?= e($entry['description']) ?></div>
                        <div class="text-muted small"><?= e($entry['user_name'] ?? 'System') ?> &middot; <?= e($entry['created_at']) ?> &middot; <?= e($entry['ip']) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                Recent Logins
                <a href="<?= url('/admin/logs/logins') ?>" class="small">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($recentLogins)): ?>
                    <li class="list-group-item text-muted">No login history yet.</li>
                <?php endif; ?>
                <?php foreach ($recentLogins as $entry): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div><?= e($entry['email']) ?></div>
                            <div class="text-muted small"><?= e($entry['ip']) ?> &middot; <?= e($entry['created_at']) ?></div>
                        </div>
                        <span class="badge <?= $entry['status'] === 'success' ? 'bg-success' : 'bg-danger' ?>"><?= e($entry['status']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script nonce="<?= e(\App\Core\Nonce::value()) ?>">
(function () {
    var labels = <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), array_keys($dailyCounts))) ?>;
    var data = <?= json_encode(array_values($dailyCounts)) ?>;
    var ctx = document.getElementById('visitorsChart');
    if (!ctx || typeof Chart === 'undefined') return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Page Views',
                data: data,
                borderColor: '#2a78d6',
                backgroundColor: 'rgba(42, 120, 214, 0.08)',
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4,
                pointBackgroundColor: '#2a78d6',
                tension: 0.3,
                fill: true,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#898781', maxTicksLimit: 8 } },
                y: { beginAtZero: true, grid: { color: '#e1e0d9' }, ticks: { color: '#898781', precision: 0 } },
            },
        },
    });
})();
</script>
