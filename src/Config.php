<?php
declare(strict_types=1);

namespace EDTB;

use Dotenv\Dotenv;

final class Config
{
    public string $env;
    public bool $debug;
    public string $baseUrl;
    public string $dbHost;
    public int $dbPort;
    public string $dbName;
    public string $dbUser;
    public string $dbPass;
    public string $dataDir;
    public string $rootDir;

    private function __construct() {}

    public static function fromEnv(string $projectRoot): self
    {
        if (is_file($projectRoot . '/.env')) {
            $dotenv = Dotenv::createImmutable($projectRoot);
            $dotenv->safeLoad();
        }

        $c = new self();
        $c->env = $_ENV['APP_ENV'] ?? 'dev';
        $c->debug = (bool) (int) ($_ENV['APP_DEBUG'] ?? '1');
        $c->baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost:8080';

        $c->dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $c->dbPort = (int)($_ENV['DB_PORT'] ?? 3306);
        $c->dbName = $_ENV['DB_NAME'] ?? 'edtb';
        $c->dbUser = $_ENV['DB_USER'] ?? 'edtb';
        $c->dbPass = $_ENV['DB_PASS'] ?? 'edtbpass';

        $c->dataDir = rtrim((defined('DATA_DIR') ? DATA_DIR : ($_ENV['EDTB_DATA_DIR'] ?? $_ENV['DATA_DIR'] ?? ($projectRoot . '/data'))), '/');
        $c->rootDir = rtrim($_ENV['ROOT_DIR'] ?? (realpath($projectRoot) ?: $projectRoot), '/');

        return $c;
    }
}
