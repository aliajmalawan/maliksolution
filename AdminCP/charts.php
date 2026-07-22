<?php
declare(strict_types=1);
// Self-hosted SVG chart helpers for the AdminCP dashboard.
// No external libraries — server-rendered SVG, themed via the header
// stylesheet (light/dark both work), accessible (role="img" + <title> tips).

/** Last-6-months count series for a table, as ['Jan' => 3, 'Feb' => 5, ...]. */
function ms_months_series(mysqli $conn, string $table, string $dateCol = 'created_at', string $whereExtra = ''): array
{
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $ts = strtotime("-{$i} months");
        $months[date('Y-m', $ts)] = ['label' => date('M', $ts), 'count' => 0];
    }
    $out = [];
    try {
        $where = "{$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)" . ($whereExtra !== '' ? " AND {$whereExtra}" : '');
        $res = mysqli_query($conn, "SELECT DATE_FORMAT({$dateCol}, '%Y-%m') ym, COUNT(*) c FROM {$table} WHERE {$where} GROUP BY ym");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            if (isset($months[$r['ym']])) {
                $months[$r['ym']]['count'] = (int)$r['c'];
            }
        }
    } catch (Exception $e) { /* table missing — return zero-filled series */ }
    foreach ($months as $m) {
        $out[$m['label']] = $m['count'];
    }
    return $out;
}

/** Daily count series for the last N days, zero-filled, as ['Y-m-d' => n]. */
function ms_daily_series(mysqli $conn, string $table, int $days, string $dateCol = 'created_at', string $whereExtra = ''): array
{
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $out[date('Y-m-d', strtotime("-{$i} days"))] = 0;
    }
    try {
        $where = "{$dateCol} >= NOW() - INTERVAL {$days} DAY" . ($whereExtra !== '' ? " AND {$whereExtra}" : '');
        $res = mysqli_query($conn, "SELECT DATE({$dateCol}) d, COUNT(*) c FROM {$table} WHERE {$where} GROUP BY DATE({$dateCol})");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            if (isset($out[$r['d']])) {
                $out[$r['d']] = (int)$r['c'];
            }
        }
    } catch (Exception $e) { /* table missing — return zero-filled series */ }
    return $out;
}

/**
 * Line/area chart with one or two series (same axis).
 * $series = [['label' => 'Admissions', 'class' => 'series-a', 'data' => [int,...]], ...]
 * $labels aligns 1:1 with each series' data by index.
 */
function svg_line_chart(array $series, array $labels, string $ariaLabel): string
{
    $w = 640; $h = 210;
    $padL = 38; $padR = 14; $padT = 12; $padB = 26;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $n = max(1, count($labels));

    $allVals = [0];
    foreach ($series as $s) {
        foreach ($s['data'] as $v) {
            $allVals[] = (int)$v;
        }
    }
    $max = max(1, ...$allVals);
    $yMax = max(5, (int)(ceil($max / 5) * 5));

    $xAt = fn(int $i): float => $padL + ($n > 1 ? $i / ($n - 1) : 0.5) * $plotW;
    $yAt = fn(float $v): float => $padT + $plotH - ($v / $yMax) * $plotH;

    ob_start(); ?>
    <svg class="chart line-chart" viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-label="<?= htmlspecialchars($ariaLabel) ?>" preserveAspectRatio="xMidYMid meet">
      <?php for ($g = 0; $g <= 4; $g++): $gy = $padT + $plotH - ($g / 4) * $plotH; ?>
        <line class="grid-line" x1="<?= $padL ?>" y1="<?= round($gy, 1) ?>" x2="<?= $w - $padR ?>" y2="<?= round($gy, 1) ?>"></line>
        <text class="axis-label" x="<?= $padL - 6 ?>" y="<?= round($gy + 3, 1) ?>" text-anchor="end"><?= (int)round($yMax * $g / 4) ?></text>
      <?php endfor; ?>
      <?php
      $ticks = array_values(array_unique([0, intdiv($n - 1, 4), intdiv($n - 1, 2), intdiv(3 * ($n - 1), 4), $n - 1]));
      foreach ($ticks as $idx): ?>
        <text class="axis-label" x="<?= round($xAt($idx), 1) ?>" y="<?= $h - 8 ?>" text-anchor="middle"><?= htmlspecialchars((string)($labels[$idx] ?? '')) ?></text>
      <?php endforeach; ?>
      <?php foreach ($series as $si => $s):
          $pts = [];
          foreach ($s['data'] as $i => $v) {
              $pts[] = round($xAt((int)$i), 1) . ',' . round($yAt((float)$v), 1);
          }
          $poly = implode(' ', $pts);
          $endVal = (float)($s['data'][$n - 1] ?? 0);
          if ($si === 0):
              $areaD = 'M' . round($xAt(0), 1) . ',' . ($padT + $plotH) . ' L' . implode(' L', $pts) . ' L' . round($xAt($n - 1), 1) . ',' . ($padT + $plotH) . ' Z'; ?>
          <path class="series-area <?= htmlspecialchars($s['class']) ?>" d="<?= $areaD ?>"></path>
      <?php endif; ?>
        <polyline class="series-line <?= htmlspecialchars($s['class']) ?>" points="<?= $poly ?>"></polyline>
        <circle class="end-dot <?= htmlspecialchars($s['class']) ?>" cx="<?= round($xAt($n - 1), 1) ?>" cy="<?= round($yAt($endVal), 1) ?>" r="3.5"></circle>
      <?php endforeach; ?>
      <?php for ($i = 0; $i < $n; $i++):
          $tipParts = [];
          foreach ($series as $s) {
              $tipParts[] = $s['label'] . ': ' . (int)($s['data'][$i] ?? 0);
          }
          $tip = ($labels[$i] ?? '') . ' — ' . implode(', ', $tipParts); ?>
        <rect class="hover-col" x="<?= round($xAt($i) - $plotW / $n / 2, 1) ?>" y="<?= $padT ?>" width="<?= round($plotW / $n, 1) ?>" height="<?= $plotH ?>"><title><?= htmlspecialchars($tip) ?></title></rect>
      <?php endfor; ?>
    </svg>
    <?php if (count($series) > 1): ?>
    <div class="chart-legend">
      <?php foreach ($series as $s): ?>
        <span class="legend-item"><span class="legend-key <?= htmlspecialchars($s['class']) ?>"></span><?= htmlspecialchars($s['label']) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif;
    return (string)ob_get_clean();
}

/** Vertical column chart. $data = ['Jan' => 3, ...]. */
function svg_columns(array $data, string $ariaLabel, string $barClass = 'col-primary'): string
{
    $w = 640; $h = 176;
    $padL = 34; $padR = 10; $padT = 16; $padB = 26;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $n = max(1, count($data));
    $max = max(1, ...($data ? array_values($data) : [0]));
    $yMax = max(5, (int)(ceil($max / 5) * 5));
    $band = $plotW / $n;
    $barW = min(40.0, $band * 0.55);

    ob_start(); ?>
    <svg class="chart col-chart" viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-label="<?= htmlspecialchars($ariaLabel) ?>" preserveAspectRatio="xMidYMid meet">
      <?php for ($g = 0; $g <= 4; $g++): $gy = $padT + $plotH - ($g / 4) * $plotH; ?>
        <line class="grid-line" x1="<?= $padL ?>" y1="<?= round($gy, 1) ?>" x2="<?= $w - $padR ?>" y2="<?= round($gy, 1) ?>"></line>
        <text class="axis-label" x="<?= $padL - 6 ?>" y="<?= round($gy + 3, 1) ?>" text-anchor="end"><?= (int)round($yMax * $g / 4) ?></text>
      <?php endfor; ?>
      <?php $i = 0; foreach ($data as $label => $val):
          $cx = $padL + $band * ($i + 0.5);
          $bh = ($val / $yMax) * $plotH;
          $y = $padT + $plotH - $bh; ?>
        <rect class="col-bar <?= htmlspecialchars($barClass) ?>" x="<?= round($cx - $barW / 2, 1) ?>" y="<?= round($y, 1) ?>" width="<?= round($barW, 1) ?>" height="<?= round(max(0, $bh), 1) ?>" rx="3"><title><?= htmlspecialchars($label . ': ' . (int)$val) ?></title></rect>
        <?php if ($val > 0): ?><text class="col-value" x="<?= round($cx, 1) ?>" y="<?= round($y - 5, 1) ?>" text-anchor="middle"><?= (int)$val ?></text><?php endif; ?>
        <text class="axis-label" x="<?= round($cx, 1) ?>" y="<?= $h - 9 ?>" text-anchor="middle"><?= htmlspecialchars((string)$label) ?></text>
      <?php $i++; endforeach; ?>
    </svg>
    <?php return (string)ob_get_clean();
}

/**
 * Donut chart with a centred total and a legend.
 * $slices = [['label' => 'Pending', 'value' => 4, 'class' => 'slice-1'], ...]
 */
function svg_donut(array $slices, string $ariaLabel, string $centerLabel = 'Total'): string
{
    $total = 0;
    foreach ($slices as $s) {
        $total += (int)$s['value'];
    }
    $cx = 70; $cy = 70; $r = 52; $sw = 22;
    $circ = 2 * M_PI * $r;
    $gap = $total > 0 ? 1.5 : 0;

    ob_start(); ?>
    <div class="donut-wrap">
      <svg class="chart donut" viewBox="0 0 140 140" role="img" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
        <circle class="donut-track" cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke-width="<?= $sw ?>"></circle>
        <?php if ($total > 0): $offset = 0; foreach ($slices as $s):
            $val = (int)$s['value'];
            if ($val <= 0) { continue; }
            $frac = $val / $total;
            $len = max(0.0, $frac * $circ - $gap); ?>
          <circle class="donut-slice <?= htmlspecialchars($s['class']) ?>" cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke-width="<?= $sw ?>"
                  stroke-dasharray="<?= round($len, 2) ?> <?= round($circ - $len, 2) ?>" stroke-dashoffset="<?= round(-$offset, 2) ?>"
                  transform="rotate(-90 <?= $cx ?> <?= $cy ?>)"><title><?= htmlspecialchars($s['label'] . ': ' . $val . ' (' . round($frac * 100) . '%)') ?></title></circle>
        <?php $offset += $frac * $circ; endforeach; endif; ?>
        <text class="donut-center-num" x="70" y="67" text-anchor="middle"><?= $total ?></text>
        <text class="donut-center-label" x="70" y="85" text-anchor="middle"><?= htmlspecialchars($centerLabel) ?></text>
      </svg>
      <div class="chart-legend donut-legend">
        <?php foreach ($slices as $s): ?>
          <span class="legend-item"><span class="legend-key <?= htmlspecialchars($s['class']) ?>"></span><?= htmlspecialchars($s['label']) ?> <strong><?= (int)$s['value'] ?></strong></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php return (string)ob_get_clean();
}

/** Horizontal bar list. $data = ['label' => count]. */
function svg_hbars(array $data, string $barClass = 'bar-fill-primary'): string
{
    $max = max(1, ...($data ? array_values($data) : [0]));
    ob_start(); ?>
    <div class="bar-list">
      <?php foreach ($data as $label => $val): ?>
        <div class="bar-row">
          <span class="bar-label" title="<?= htmlspecialchars((string)$label) ?>"><?= htmlspecialchars((string)$label) ?></span>
          <div class="bar-track"><div class="bar-fill <?= htmlspecialchars($barClass) ?>" style="width:<?= (int)round($val / $max * 100) ?>%"></div></div>
          <span class="bar-value"><?= (int)$val ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php return (string)ob_get_clean();
}

/** Month-over-month delta badge, e.g. ▲ 25%. Empty string when no data. */
function delta_html(int $current, int $previous): string
{
    if ($previous === 0 && $current === 0) {
        return '';
    }
    if ($previous === 0) {
        return '<span class="stat-delta up">&#9650; new</span>';
    }
    $pct = (int)round(($current - $previous) / $previous * 100);
    return $pct >= 0
        ? '<span class="stat-delta up">&#9650; ' . $pct . '%</span>'
        : '<span class="stat-delta down">&#9660; ' . abs($pct) . '%</span>';
}
