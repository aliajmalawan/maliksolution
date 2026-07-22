<?php declare(strict_types=1); /* Closes the admin shell opened by header.php */ ?>
</main>

<script src="<?= UMS_URL ?>/assets/js/chart.umd.min.js"></script>
<script src="<?= UMS_URL ?>/assets/js/ums.js?v=<?= e(UMS_VERSION) ?>"></script>
<?php if (!empty($page_scripts)) echo $page_scripts; ?>
</body>
</html>
