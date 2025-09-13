<?php
declare(strict_types=1);

// CLI/HTTP entrypoint to force Journal ingestion on demand.
// Usage (CLI): php scripts/ingest_journal.php
// Usage (HTTP): /scripts/ingest_journal.php  (if web-exposed)

require_once __DIR__ . '/../source/Journal/Parser.php';
\EDTB\Journal\Parser::ingest();

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'ran' => time()], JSON_UNESCAPED_SLASHES);
}
