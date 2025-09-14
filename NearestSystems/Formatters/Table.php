<?php
class NearestSystemsTableFormatter
{
    public static function tableOpen($stations): string
    {
        $cls = 'system_table' . ($stations !== false ? ' with-stations' : ' without-stations');
        return '<table class="' . $cls . '">';
    }
    public static function header($stations): string
    {
        // Order matches the original: Distance first, then System, then details
        $out = '<tr><th class="col-distance">Distance</th><th>System</th>';
        if ($stations !== false) {
            $out .= '<th>Station</th><th>LS From Star</th><th>Pad</th><th>Type</th>';
        }
        $out .= '<th>Allegiance</th><th>Pop</th><th>Eco</th><th>Gov</th><th>Sec</th></tr>';
        return $out;
    }


    public static function row($row, $stations): string
    {
        $out = '<tr>';

        // Distance (ly), 2 decimals
        $dist = isset($row->distance) ? (float)$row->distance : 0.0;
        $out .= '<td class="col-distance">' . number_format($dist, 2) . '</td>';

        // System (link to System page)
        $sys = $row->system ?? '';
        $out .= '<td>' . ($sys !== '' 
            ? '<a href="/System?system_name=' . rawurlencode($sys) . '">' . htmlspecialchars($sys, ENT_QUOTES) . '</a>'
            : '') . '</td>';

        if ($stations !== false) {
            $out .= '<td>' . htmlspecialchars($row->station_name ?? '', ENT_QUOTES) . '</td>';
            $out .= '<td class="col-ls">' . (isset($row->ls_from_star) && $row->ls_from_star !== '' ? (int)$row->ls_from_star : '') . '</td>';
            $out .= '<td class="col-pad">' . htmlspecialchars($row->max_landing_pad_size ?? '', ENT_QUOTES) . '</td>';
            $out .= '<td>' . htmlspecialchars($row->type ?? '', ENT_QUOTES) . '</td>';
        }

        $out .= '<td>' . htmlspecialchars($row->allegiance ?? '', ENT_QUOTES) . '</td>';
        $out .= '<td class="col-pop">' . number_format((int)($row->population ?? 0)) . '</td>';
        $out .= '<td>' . htmlspecialchars($row->economy ?? '', ENT_QUOTES) . '</td>';
        $out .= '<td>' . htmlspecialchars($row->government ?? '', ENT_QUOTES) . '</td>';
        $out .= '<td>' . htmlspecialchars($row->security ?? '', ENT_QUOTES) . '</td>';

        $out .= '</tr>';
        return $out;
    }


    public static function tableClose(): string
    {
        return '</table>';
    }
}
