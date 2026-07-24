<?php

namespace App\Core;

use PDO;

/**
 * Pure-PHP database backup/restore — no shell_exec/mysqldump dependency,
 * consistent with this project's hand-rolled approach elsewhere (SMTP,
 * HTML sanitizing). Dump introspects via SHOW CREATE TABLE and generates
 * quoted INSERTs; restore uses a quote-aware statement splitter (a naive
 * explode(';', ...) breaks on semicolons inside string literals).
 */
class DatabaseBackup
{
    private const BATCH_SIZE = 500;

    public static function backupsDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/backups';
    }

    /** Dumps the live database to a timestamped .sql file and returns its filename. */
    public static function dump(): string
    {
        $pdo = Database::connection();
        $dir = self::backupsDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'db_backup_' . date('Y-m-d_His') . '.sql';
        $path = $dir . '/' . $filename;

        $handle = fopen($path, 'w');
        if (!$handle) {
            throw new \RuntimeException('Could not open backup file for writing.');
        }

        fwrite($handle, "-- EduTop database backup — " . date('c') . "\n");
        fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $createRow = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
            $createSql = $createRow['Create Table'] ?? null;
            if (!$createSql) {
                continue;
            }

            fwrite($handle, "-- Table: {$table}\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createSql . ";\n\n");

            self::dumpTableData($pdo, $handle, $table);
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return $filename;
    }

    private static function dumpTableData($pdo, $handle, string $table): void
    {
        $countRow = $pdo->query('SELECT COUNT(*) AS c FROM `' . $table . '`')->fetch();
        $total = (int) ($countRow['c'] ?? 0);
        if ($total === 0) {
            return;
        }

        $columnsStmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        $columns = array_column($columnsStmt->fetchAll(), 'Field');
        $columnList = '`' . implode('`, `', $columns) . '`';

        for ($offset = 0; $offset < $total; $offset += self::BATCH_SIZE) {
            $rows = $pdo->query(
                "SELECT * FROM `{$table}` LIMIT " . self::BATCH_SIZE . " OFFSET {$offset}"
            )->fetchAll();

            if (!$rows) {
                break;
            }

            $valueLines = [];
            foreach ($rows as $row) {
                $values = array_map(
                    fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row)
                );
                $valueLines[] = '(' . implode(', ', $values) . ')';
            }

            fwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $valueLines) . ";\n");
        }

        fwrite($handle, "\n");
    }

    /**
     * Restores a .sql backup. Defaults to the live app connection; a
     * different PDO can be passed in to verify a dump against a disposable
     * scratch database instead of touching production data.
     */
    public static function restore(string $filePath, ?PDO $pdo = null): void
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException('Backup file not found.');
        }

        $pdo ??= Database::connection();
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new \RuntimeException('Could not read backup file.');
        }

        $statements = self::splitStatements($sql);

        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }
            $pdo->exec($statement);
        }
    }

    /** Quote-aware statement splitter: only splits on ';' outside of string literals. */
    public static function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;
                if ($char === '\\') {
                    if ($i + 1 < $len) {
                        $current .= $sql[++$i];
                    }
                    continue;
                }
                if ($char === $stringChar) {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
                continue;
            }

            if ($char === ';') {
                $statements[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $statements[] = trim($current);
        }

        return array_values(array_filter(
            $statements,
            fn($s) => $s !== '' && !str_starts_with(ltrim($s), '--')
        ));
    }
}
