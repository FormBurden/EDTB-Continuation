<?php
/**
 * Convert unix timestamp to relative time ("x minutes ago")
 *
 * @param int|string $ptime
 * @param bool $format if true, wrap with .old_data when dataIsOld()
 * @return string
 */
function get_timeago($ptime, $format = false)
{
    $ptimeOg = $ptime;

    $etime = time() - (int)$ptime;
    if ($etime < 1) {
        $etime = 1;
    }

    $a = [
        12 * 30 * 24 * 60 * 60  => 'year',
        30 * 24 * 60 * 60       => 'month',
        24 * 60 * 60            => 'day',
        60 * 60                 => 'hour',
        60                      => 'minute',
        1                       => 'second'
    ];

    foreach ($a as $secs => $str) {
        $d = $etime / $secs;

        if ($d >= 1) {
            $r = round($d);
            if ($format !== true) {
                return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
            }

            if (dataIsOld($ptimeOg)) {
                return '<span class="old_data">' . $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago</span>';
            }

            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}
