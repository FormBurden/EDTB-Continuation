<?php
/**
 * DataPoint Schema helpers
 * - Builds ordered column list ($output)
 * - Builds label map ($labels) using DB comments, with sane fallbacks and overrides
 */

if (!function_exists('datapoint_get_column_labels')) {
    /**
     * @param mysqli $mysqli
     * @param string $table
     * @return array{0: array<int,string>, 1: array<string,string>} [$output, $labels]
     */
    function datapoint_get_column_labels($mysqli, $table)
    {
        $output = [];
        $labels = [];

        $tableEsc = $mysqli->real_escape_string($table);
        $query = "SELECT COLUMN_NAME, COLUMN_COMMENT
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE table_name = '$tableEsc'";

        $result = $mysqli->query($query);
        if ($result === false) {
            // keep behavior consistent with existing code path
            write_log($mysqli->error, __FILE__, __LINE__);
            return [$output, $labels];
        }

        while ($columnObj = $result->fetch_object()) {
            $colName = $columnObj->COLUMN_NAME;
            $output[] = $colName;
            $labels[$colName] = $columnObj->COLUMN_COMMENT;
        }
        $result->close();

        // Fallback labels: snake_case -> Title Case, with acronym touch-ups
        foreach ($output as $col) {
            if (!isset($labels[$col]) || $labels[$col] === '' || $labels[$col] === null) {
                $label = str_replace('_', ' ', $col);
                $label = ucwords($label);

                // Common acronym fixes
                $label = preg_replace('/\bId\b/u', 'ID', $label);
                $label = preg_replace('/\bEddb\b/iu', 'EDDB', $label);
                $label = preg_replace('/\bSimbad\b/iu', 'SIMBAD', $label);

                $labels[$col] = $label;
            }
        }

        // Specific overrides to match the Windows look
        $overrides = [
            'power_state'    => 'Power State',
            'ruling_faction' => 'Ruling Faction',
            'needs_permit'   => 'Needs Permit',
            'updated_at'     => 'Updated At',
            'simbad_ref'     => 'SIMBAD Ref',
        ];
        foreach ($overrides as $k => $v) {
            if (isset($labels[$k])) {
                $labels[$k] = $v;
            }
        }

        return [$output, $labels];
    }
}
