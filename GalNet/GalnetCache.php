<?php
/**
 * GalnetCache
 * Thin helper around existing phpfastcache (__c('files')) to cache GalNet feed arrays.
 * No guards; assumes __c('files') is available as in current project.
 */
class GalnetCache
{
    /**
     * Remember pattern: return cached value if hit; otherwise compute, store, and return.
     *
     * @param string   $key
     * @param int      $ttlSeconds
     * @param callable $producer must return an array (GalNet feed)
     * @return array
     */
    public static function remember(string $key, int $ttlSeconds, callable $producer): array
    {
        $cache = __c('files');

        $cached = $cache->get($key);
        if ($cached === null) {
            $value = $producer();
            $cache->set($key, $value, $ttlSeconds);
            return $value;
        }

        return $cached;

    }
}
