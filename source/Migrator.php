<?php
declare(strict_types=1);

namespace EDTB;

use PDO;

final class Migrator
{
    public static function run(PDO $pdo, string $dir): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations_applied (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $applied = [];
        $stmt = $pdo->query("SELECT filename FROM migrations_applied");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $applied[$row['filename']] = true;
        }

        $files = glob(rtrim($dir, '/')."/*.sql") ?: [];
        sort($files);

        foreach ($files as $file) {
            $base = basename($file);
            if (isset($applied[$base])) {
                continue;
            }
            $sql = file_get_contents($file) ?: '';
            // naive split on semicolons followed by newline(s)
            $parts = array_filter(array_map('trim', preg_split('/;[\r\n]+/m', $sql)));
            foreach ($parts as $part) {
                if ($part !== '') {
                    $pdo->exec($part);
                }
            }
            $ins = $pdo->prepare("INSERT INTO migrations_applied (filename) VALUES (:f)");
            $ins->execute([':f' => $base]);
            echo "[migrate] applied {$base}\n";
        }
    }
}
