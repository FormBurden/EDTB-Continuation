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
        $out = '<tr><th>System</th>';
        if ($stations !== false) {
            $out .= '<th>Station</th><th>LS From Star</th><th>Pad</th><th>Type</th>';
        }
        $out .= '<th>Allegiance</th><th>Gov</th><th>Sec</th><th>Eco</th><th>Pop</th></tr>';
        return $out;
    }

    public static function row($row, $stations): string
    {
        $out  = '<tr>';
        $out .= '<td>' . $row->system . '</td>';

        if ($stations !== false) {
            $out .= '<td>' . $row->station_name . '</td>';
            $out .= '<td>' . $row->ls_from_star . '</td>';
            $out .= '<td>' . $row->max_landing_pad_size . '</td>';
            $out .= '<td>' . $row->type . '</td>';
        }

        $out .= '<td>' . ($row->allegiance ?? '') . '</td>';
        $out .= '<td>' . ($row->government ?? '') . '</td>';
        $out .= '<td>' . ($row->security ?? '') . '</td>';
        $out .= '<td>' . ($row->economy ?? '') . '</td>';
        $out .= '<td>' . number_format((int)($row->population ?? 0)) . '</td>';
        $out .= '</tr>';
        return $out;
    }

    public static function tableClose(): string
    {
        return '</table>';
    }
}
