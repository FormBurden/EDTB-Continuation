<?php
function getStationIcon($type, $planetary = '0', $style = '')
{
    // NOTE: moved one directory deeper -> use dirname(__DIR__)
    $map = require dirname(__DIR__) . '/../System/Lookups/StationTypeIconMap.php';

    if (isset($map[$type])) {
        $file = $map[$type];
        $alt  = $type;
    } elseif ($planetary === '0') {
        $file = 'spaceport.png';
        $alt  = 'Starport';
    } elseif ($planetary === '1') {
        $file = 'planetary.png';
        $alt  = 'Planetary';
    } else {
        $file = 'unknown.png';
        $alt  = 'Unknown';
    }

    $style = $style !== '' ? ' style="' . $style . '"' : '';
    return '<img src="/style/img/spaceports/' . $file . '" alt="' . $alt . '" title="' . $alt . '"' . $style . '>';
}

/**
 * Get Allegiance Icon filename by allegiance value
 *
 * @param string $allegiance
 * @return string
 */
function getAllegianceIcon($allegiance)
{
    // NOTE: moved one directory deeper -> use dirname(__DIR__)
    $map = require dirname(__DIR__) . '/../System/Lookups/AllegianceIconMap.php';
    return $map[$allegiance] ?? 'system.png';
}
