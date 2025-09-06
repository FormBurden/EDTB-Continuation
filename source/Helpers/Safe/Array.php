<?php
/**
 * Normalize a mixed config value (comma-separated string or array) into array.
 * @param mixed $v
 * @return array
 */
function edtb_to_array($v): array {
    if (!isset($v) || $v === '' || $v === null) return [];
    if (is_array($v)) return $v;
    if (is_string($v)) {
        $parts = array_filter(array_map('trim', explode(',', $v)));
        return $parts;
    }
    return [$v];
}
