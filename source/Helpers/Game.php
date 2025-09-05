<?php
/**
 * Get rank
 *
 * @param int $rank
 * @return string $retvalue
 * @author Mauri Kujala
 */
function getRank($rank)
{
    $ranks = [
        0 => 'Harmless',
        1 => 'Mostly Harmless',
        2 => 'Novice',
        3 => 'Competent',
        4 => 'Expert',
        5 => 'Master',
        6 => 'Dangerous',
        7 => 'Deadly',
        8 => 'Elite',
    ];

    return $ranks[(int)$rank] ?? 'Unknown';
}
