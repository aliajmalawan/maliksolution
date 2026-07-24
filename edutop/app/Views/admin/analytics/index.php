<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Analytics</h1>
    <span class="badge bg-success fs-6"><span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span><?= number_format((int) $onlineNow) ?> online now</span>
</div>

<h2 class="h6 text-muted text-uppercase mb-3">Leads &amp; Content</h2>

<div class="row g-3 mb-3">
    <?php
    $overviewCards = [
        ['label' => 'Total Leads', 'value' => $leadsTotal, 'icon' => '📨'],
        ['label' => 'New Leads', 'value' => $leadsStatusCounts['new'], 'icon' => '🆕'],
        ['label' => 'Blog Posts', 'value' => $blogTotal, 'icon' => '📝'],
        ['label' => 'Pages', 'value' => $pagesTotal, 'icon' => '📄'],
        ['label' => 'Media Files', 'value' => $mediaTotal, 'icon' => '🖼️'],
        ['label' => 'Team Users', 'value' => $usersTotal, 'icon' => '👤'],
    ];
    ?>
    <?php foreach ($overviewCards as $card): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= $card['icon'] ?> <?= e($card['label']) ?></div>
                    <div class="fs-3 fw-bold"><?= number_format((int) $card['value']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Leads by Status</div>
            <div class="card-body">
                <?php
                $leadStatusMeta = [
                    'new' => ['label' => 'New', 'class' => 'bg-warning'],
                    'contacted' => ['label' => 'Contacted', 'class' => 'bg-primary'],
                    'closed' => ['label' => 'Closed', 'class' => 'bg-success'],
                ];
                $leadStatusTotal = array_sum($leadsStatusCounts);
                ?>
                <?php foreach ($leadStatusMeta as $key => $meta): ?>
                    <?php $count = $leadsStatusCounts[$key]; $pct = $leadStatusTotal > 0 ? round($count / $leadStatusTotal * 100) : 0; ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                            <span class="text-muted"><?= $pct ?>% (<?= number_format($count) ?>)</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar <?= $meta['class'] ?>" style="width: <?= $pct ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Leads by Type</div>
            <div class="card-body">
                <?php
                $leadTypeMeta = [
                    'admission' => ['label' => 'Admission', 'class' => 'bg-success'],
                    'demo' => ['label' => 'Visit Request', 'class' => 'bg-info'],
                    'contact' => ['label' => 'Contact', 'class' => 'bg-secondary'],
                ];
                $leadTypeTotal = array_sum($leadsTypeCounts);
                ?>
                <?php foreach ($leadTypeMeta as $key => $meta): ?>
                    <?php $count = $leadsTypeCounts[$key]; $pct = $leadTypeTotal > 0 ? round($count / $leadTypeTotal * 100) : 0; ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                            <span class="text-muted"><?= $pct ?>% (<?= number_format($count) ?>)</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar <?= $meta['class'] ?>" style="width: <?= $pct ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">New Leads — Last 6 Months</div>
    <div class="card-body">
        <canvas id="leadsChart" height="70"></canvas>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Blog Posts by Status</div>
            <div class="card-body">
                <?php
                $blogStatusMeta = [
                    'published' => ['label' => 'Published', 'class' => 'bg-success'],
                    'scheduled' => ['label' => 'Scheduled', 'class' => 'bg-info'],
                    'draft' => ['label' => 'Draft', 'class' => 'bg-secondary'],
                ];
                $blogStatusTotal = array_sum($blogStatusCounts);
                ?>
                <?php if ($blogStatusTotal === 0): ?>
                    <p class="text-muted mb-0">No blog posts yet.</p>
                <?php else: ?>
                    <?php foreach ($blogStatusMeta as $key => $meta): ?>
                        <?php $count = $blogStatusCounts[$key]; $pct = round($count / $blogStatusTotal * 100); ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                                <span class="text-muted"><?= $pct ?>% (<?= number_format($count) ?>)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar <?= $meta['class'] ?>" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Pages by Status</div>
            <div class="card-body">
                <?php
                $pageStatusMeta = [
                    'published' => ['label' => 'Published', 'class' => 'bg-success'],
                    'draft' => ['label' => 'Draft', 'class' => 'bg-secondary'],
                ];
                $pageStatusTotal = array_sum($pagesStatusCounts);
                ?>
                <?php if ($pageStatusTotal === 0): ?>
                    <p class="text-muted mb-0">No pages yet.</p>
                <?php else: ?>
                    <?php foreach ($pageStatusMeta as $key => $meta): ?>
                        <?php $count = $pagesStatusCounts[$key]; $pct = round($count / $pageStatusTotal * 100); ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                                <span class="text-muted"><?= $pct ?>% (<?= number_format($count) ?>)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar <?= $meta['class'] ?>" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<h2 class="h6 text-muted text-uppercase mb-3">Website Traffic</h2>

<div class="row g-3 mb-3">
    <?php
    $statCards = [
        ['label' => 'Today', 'value' => $viewsToday, 'icon' => '☀️'],
        ['label' => 'Yesterday', 'value' => $viewsYesterday, 'icon' => '🌙'],
        ['label' => 'Last 7 Days', 'value' => $views7, 'icon' => '📅'],
        ['label' => 'This Month', 'value' => $viewsMonth, 'icon' => '🗓️'],
        ['label' => 'This Year', 'value' => $viewsYear, 'icon' => '📆'],
        ['label' => 'All Time', 'value' => $totalViews, 'icon' => '📈'],
    ];
    ?>
    <?php foreach ($statCards as $card): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= $card['icon'] ?> <?= e($card['label']) ?></div>
                    <div class="fs-3 fw-bold"><?= number_format((int) $card['value']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">👥 Unique Visitors (30 days)</div>
                    <div class="fs-3 fw-bold"><?= number_format((int) $uniqueVisitors30) ?></div>
                </div>
                <div class="text-end text-muted small">distinct IP addresses</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">📊 Views (last 30 days)</div>
                    <div class="fs-3 fw-bold"><?= number_format((int) $views30) ?></div>
                </div>
                <div class="text-end text-muted small">
                    ≈ <?= $uniqueVisitors30 > 0 ? number_format($views30 / $uniqueVisitors30, 1) : '0' ?> pages per visitor
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">Page Views — Last 30 Days</div>
    <div class="card-body">
        <canvas id="analyticsChart" height="70"></canvas>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Top Pages (all time)</div>
            <div class="card-body">
                <?php if (empty($topPages)): ?>
                    <p class="text-muted mb-0">No page views recorded yet.</p>
                <?php else: ?>
                    <?php $maxViews = max(array_column($topPages, 'views')); ?>
                    <?php foreach ($topPages as $p): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <code><?= e($p['url'] ?: '/') ?></code>
                                <span class="text-muted"><?= number_format((int) $p['views']) ?></span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" style="width: <?= $maxViews > 0 ? round($p['views'] / $maxViews * 100) : 0 ?>%; background-color: #2a78d6;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Top External Referrers</div>
            <ul class="list-group list-group-flush">
                <?php if (empty($topReferrers)): ?>
                    <li class="list-group-item text-muted">No external referrers yet — traffic so far is direct.</li>
                <?php else: ?>
                    <?php foreach ($topReferrers as $r): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-truncate me-2" style="max-width: 75%;" title="<?= e($r['referrer']) ?>"><?= e($r['referrer']) ?></span>
                            <span class="badge bg-secondary"><?= number_format((int) $r['views']) ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php
$breakdownCards = [
    'Browsers (30d)' => ['data' => $agents['browsers'], 'icon' => '🌐'],
    'Operating Systems (30d)' => ['data' => $agents['systems'], 'icon' => '💻'],
    'Devices (30d)' => ['data' => $agents['devices'], 'icon' => '📱'],
];
?>
<div class="row g-3 mt-0 mb-3">
    <?php foreach ($breakdownCards as $title => $meta): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><?= $meta['icon'] ?> <?= e($title) ?></div>
                <div class="card-body">
                    <?php if (empty($meta['data'])): ?>
                        <p class="text-muted mb-0">No data yet.</p>
                    <?php else: ?>
                        <?php $total = array_sum($meta['data']); ?>
                        <?php foreach ($meta['data'] as $name => $count): ?>
                            <?php $pct = $total > 0 ? round($count / $total * 100) : 0; ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= e($name) ?></span>
                                    <span class="text-muted"><?= $pct ?>% (<?= number_format($count) ?>)</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= $pct ?>%; background-color: #2a78d6;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">🕒 Recent Visits (last 20)</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Time</th>
                    <th>Page</th>
                    <th>IP</th>
                    <th>Browser / System</th>
                    <th>Came From</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentVisits)): ?>
                    <tr><td colspan="5" class="text-muted">No visits recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentVisits as $v): ?>
                        <?php $parsed = \App\Models\PageView::classifyUserAgent((string) $v['user_agent']); ?>
                        <tr>
                            <td class="text-nowrap small"><?= e(date('M j, H:i', strtotime($v['created_at']))) ?></td>
                            <td><code class="small"><?= e($v['url'] ?: '/') ?></code></td>
                            <td class="small"><?= e($v['ip']) ?></td>
                            <td class="small"><?= e($parsed['browser']) ?> · <?= e($parsed['system']) ?> · <?= e($parsed['device']) ?></td>
                            <td class="small text-truncate" style="max-width: 220px;" title="<?= e($v['referrer'] ?? '') ?>">
                                <?= $v['referrer'] ? e($v['referrer']) : '<span class="text-muted">Direct</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script nonce="<?= e(\App\Core\Nonce::value()) ?>">
(function () {
    var labels = <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), array_keys($dailyCounts))) ?>;
    var data = <?= json_encode(array_values($dailyCounts)) ?>;
    var ctx = document.getElementById('analyticsChart');
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
                fill: true,
                tension: 0.3,
                pointRadius: 2,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
})();

(function () {
    var labels = <?= json_encode(array_map(fn($ym) => date('M Y', strtotime($ym . '-01')), array_keys($leadsMonthly))) ?>;
    var data = <?= json_encode(array_values($leadsMonthly)) ?>;
    var ctx = document.getElementById('leadsChart');
    if (!ctx || typeof Chart === 'undefined') return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'New Leads',
                data: data,
                backgroundColor: '#2a78d6',
                borderRadius: 4,
                maxBarThickness: 40,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
})();
</script>
