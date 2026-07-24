<?php
$guestCompany = \App\Models\Setting::group('company');
$guestSiteName = $guestCompany['site_name'] ?? ($appName ?? 'EduTop');
$guestFavicon = !empty($guestCompany['favicon']) ? media_url($guestCompany['favicon'])
    : (!empty($guestCompany['logo']) ? media_url($guestCompany['logo']) : null);
$guestLogo = !empty($guestCompany['logo']) ? media_url($guestCompany['logo']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($guestSiteName) ?> Admin</title>
    <?php if ($guestFavicon): ?><link rel="icon" href="<?= e($guestFavicon) ?>"><?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; }
        .auth-card { max-width: 420px; width: 100%; margin: 0 auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="auth-card card shadow-lg border-0">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <?php if ($guestLogo): ?>
                    <img src="<?= e($guestLogo) ?>" alt="<?= e($guestSiteName) ?>" style="max-height: 52px; width: auto;" class="mb-2">
                <?php endif; ?>
                <h1 class="h4 fw-bold mb-0"><?= e($guestSiteName) ?></h1>
                <p class="text-muted small">Admin Dashboard</p>
            </div>
            <?= $content ?>
        </div>
    </div>
</div>
</body>
</html>
