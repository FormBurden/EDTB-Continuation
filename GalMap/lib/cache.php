<?php
declare(strict_types=1);

/**
 * Minimal cache helpers for GalMap endpoints.
 * Uses phpFastCache (already configured in source/config.inc.php).
 */

if (!defined('EDTB_GALMAP_SUGGEST_TTL')) define('EDTB_GALMAP_SUGGEST_TTL', 300); // 5 minutes
if (!defined('EDTB_GALMAP_POINTS_TTL'))  define('EDTB_GALMAP_POINTS_TTL', 300); // 5 minutes

/**
 * Return a phpFastCache instance, or null if unavailable.
 * We load the vendor file here to avoid repeating it in endpoints.
 */
function edtb_cache_instance() {
    static $inst = null;
    if ($inst !== null) {
        return $inst;
    }

    if (!class_exists('phpFastCache')) {
        // Helper file lives in GalMap/lib/, project root is 2 levels up
        $root = dirname(__DIR__, 2);
        $vendor = $root . '/source/Vendor/phpfastcache/phpfastcache.php';
        if (is_file($vendor)) {
            require_once $vendor;
        }
    }

    if (class_exists('phpFastCache')) {
        try {
            // config.inc.php already ran and set storage/path/securityKey
            $inst = phpFastCache();
        } catch (\Throwable $e) {
            $inst = null;
        }
    }

    return $inst;
}

/**
 * Build a stable cache key from a namespace and an associative array of parts.
 */
function edtb_cache_key(string $ns, array $parts): string
{
    ksort($parts);
    return $ns . ':' . md5(json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
