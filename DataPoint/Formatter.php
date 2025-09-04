<?php
/**
 * DataPoint value/label formatting helpers
 * - Keep this file small and focused (no DB calls)
 * - Used by DataPoint/functions.php and Vendor table editor rendering
 *
 * NOTE: No guards, just straightforward transforms.
 */

/**
 * Fallback label from a snake_case column key.
 * Title Case with common acronym touch-ups.
 */
function dp_titleize(string $key): string
{
    $label = str_replace('_', ' ', $key);
    $label = ucwords($label);

    // Acronyms and common fixes
    $label = preg_replace('/\bId\b/u', 'ID', $label);
    $label = preg_replace('/\bEddb\b/iu', 'EDDB', $label);
    $label = preg_replace('/\bSimbad\b/iu', 'SIMBAD', $label);

    // Specific overrides to match original Windows look
    switch ($key) {
        case 'power_state':    return 'Power State';
        case 'ruling_faction': return 'Ruling Faction';
        case 'needs_permit':   return 'Needs Permit';
        case 'updated_at':     return 'Updated At';
        case 'simbad_ref':     return 'SIMBAD Ref';
    }
    return $label;
}

/**
 * Format booleans (DB ints/tinyints) to Yes/No.
 */
function dp_format_bool($v): string
{
    if ($v === 1 || $v === '1' || $v === true)  return 'Yes';
    if ($v === 0 || $v === '0' || $v === false) return 'No';
    return (string)$v;
}

/**
 * Format floats consistently (no trailing .000 for integers).
 */
function dp_format_float($v): string
{
    if ($v === null || $v === '') return '';
    if (is_numeric($v)) {
        $n = (float)$v;
        return floor($n) == $n ? (string)(int)$n : rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
    return (string)$v;
}

/**
 * Datetime formatting (Y-m-d H:i by default).
 */
function dp_format_datetime($v): string
{
    if (!$v) return '';
    // Accepts strings like "2025-09-01 12:34:56" or UNIX timestamps
    if (ctype_digit((string)$v)) {
        return date('Y-m-d H:i', (int)$v);
    }
    $t = strtotime($v);
    return $t ? date('Y-m-d H:i', $t) : (string)$v;
}

/**
 * General value formatter by column name.
 * Extend this switch as needed for DataPoint’s columns.
 */
function dp_format_value(string $col, $val): string
{
    switch ($col) {
        case 'needs_permit':
        case 'landable':
        case 'is_populated':
        case 'is_planetary':
            return dp_format_bool($val);

        case 'distance_to_arrival':
        case 'distance':
        case 'radius':
        case 'axial_tilt':
        case 'semi_major_axis':
            return dp_format_float($val);

        case 'updated_at':
        case 'created_at':
        case 'discovered_at':
            return dp_format_datetime($val);

        default:
            if (is_array($val)) {
                return json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            return (string)$val;
    }
}
