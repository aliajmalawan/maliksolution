<?php

/**
 * CLI trigger for unattended scheduled backups — wire this to Windows Task
 * Scheduler (XAMPP/Windows has no native cron), e.g. daily at 2am:
 *
 *   schtasks /create /tn "EduTop DB Backup" /sc daily /st 02:00 ^
 *     /tr "C:\xampp\php\php.exe C:\xampp\htdocs\EduTop\database\backup-cli.php"
 *
 * Usage: php database/backup-cli.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\DatabaseBackup;

try {
    $filename = DatabaseBackup::dump();
    echo "Backup created: {$filename}\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Backup failed: {$e->getMessage()}\n");
    exit(1);
}
