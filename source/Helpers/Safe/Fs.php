<?php
/**
 * Converts bytes into human readable file size.
 *
 * @param string|float $bytes
 * @return string human readable file size
 * @author Mogilev Arseny
 */
function FileSizeConvert($bytes)
{
    $bytes = (float)$bytes;
    $arBytes = [
        0 => ['UNIT' => 'TB', 'VALUE' => 1024 ** 4],
        1 => ['UNIT' => 'GB', 'VALUE' => 1024 ** 3],
        2 => ['UNIT' => 'MB', 'VALUE' => 1024 ** 2],
        3 => ['UNIT' => 'KB', 'VALUE' => 1024],
        4 => ['UNIT' => 'B',  'VALUE' => 1],
    ];

    foreach ($arBytes as $arItem) {
        if ($bytes >= $arItem['VALUE']) {
            $result = $bytes / $arItem['VALUE'];
            $result = str_replace('.', '.', strval(round($result, 2))) . ' ' . $arItem['UNIT'];
            break;
        }
    }

    return $result ?? ('0 B');
}

/**
 * Check if directory is empty
 * @param string $dir
 * @return bool
 * @author Your Common Sense
 */
function is_dir_empty($dir)
{
    if (!is_readable($dir)) {
        return true;
    }
    return (count(scandir($dir)) == 2);
}
