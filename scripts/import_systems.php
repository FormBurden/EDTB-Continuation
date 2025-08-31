<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use EDTB\Config;
use EDTB\DB;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/import_systems.php /path/to/systems_populated.csv\n");
    exit(1);
}

$path = $argv[1];
if (!is_file($path)) {
    fwrite(STDERR, "File not found: $path\n");
    exit(1);
}

$config = Config::fromEnv(__DIR__ . '/..');
$pdo    = DB::connect($config)->pdo();

$pdo->beginTransaction();
$insert = $pdo->prepare("
    INSERT INTO systems (name, x, y, z)
    VALUES (:name, :x, :y, :z)
    ON DUPLICATE KEY UPDATE x=VALUES(x), y=VALUES(y), z=VALUES(z)
");

$fh = fopen($path, 'r');
if (!$fh) {
    throw new RuntimeException("Cannot open $path");
}

$line = 0;
$map = null;

while (($row = fgetcsv($fh)) !== false) {
    $line++;
    // header
    if ($line === 1) {
        $headers = array_map(static fn($h) => strtolower(trim($h)), $row);
        $map = array_flip($headers);
        foreach (['name','x','y','z'] as $h) {
            if (!isset($map[$h])) {
                throw new RuntimeException("Missing header '$h' in CSV");
            }
        }
        continue;
    }

    if ($row === [null] || count($row) < 4) {
        continue;
    }

    $name = trim((string)$row[$map['name']]);
    if ($name === '') {
        continue;
    }

    $x = (float)$row[$map['x']];
    $y = (float)$row[$map['y']];
    $z = (float)$row[$map['z']];

    $insert->execute([
        ':name' => $name,
        ':x'    => $x,
        ':y'    => $y,
        ':z'    => $z,
    ]);

    if (($line % 5000) === 0) {
        echo "Imported $line lines...\n";
        $pdo->commit();
        $pdo->beginTransaction();
    }
}

$pdo->commit();
fclose($fh);

echo "Import complete. Lines processed: $line\n";
