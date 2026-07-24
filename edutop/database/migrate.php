<?php

/**
 * CLI migration runner. Usage: php database/migrate.php
 * Applies any .sql files in database/migrations/ that haven't run yet,
 * tracked in a `migrations` table.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        run_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$already = array_column($pdo->query('SELECT migration FROM migrations')->fetchAll(), 'migration');

$dir = __DIR__ . '/migrations';
$files = glob($dir . '/*.sql');
sort($files);

$applied = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $already, true)) {
        continue;
    }

    $sql = file_get_contents($file);
    echo "Applying {$name}... ";

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
        $stmt->execute([$name]);
        echo "OK\n";
        $applied++;
    } catch (\PDOException $e) {
        echo "FAILED\n";
        echo $e->getMessage() . "\n";
        exit(1);
    }
}

echo $applied === 0 ? "Nothing to migrate.\n" : "{$applied} migration(s) applied.\n";
