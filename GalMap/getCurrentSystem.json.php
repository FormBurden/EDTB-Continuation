<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
require_once $root . '/source/config.inc.php';
require_once $root . '/source/curSys.php'; // populates $curSys

$name = (string)($curSys['name'] ?? '');
$x    = $curSys['x'] ?? null;
$y    = $curSys['y'] ?? null;
$z    = $curSys['z'] ?? null;

if ($name !== '' && is_numeric($x) && is_numeric($y) && is_numeric($z)) {
    echo json_encode(['name' => $name, 'x' => (float)$x, 'y' => (float)$y, 'z' => (float)$z], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'current_system_not_found'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
