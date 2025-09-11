<?php
/**
 * DataPoint Schema helpers
 *
 * - Table whitelist (keeps Data Point pointed at known/allowed tables)
 * - Friendly column labels (uses column comments when available, otherwise Title Case)
 *
 * No dependencies beyond mysqli ($mysqli).
 */

/**
 * Return an array of allowed tables.
 * If you prefer to lock it down, list them explicitly.
 * Otherwise we auto-discover tables starting with "edtb_".
 *
 * @param mysqli $mysqli
 * @return string[] table names
 */
function datapoint_table_whitelist(mysqli $mysqli): array
{
    $allowed = [];

    // Try to auto-discover EDTB tables. Falls back to a small common set.
    if ($res = $mysqli->query("SHOW TABLES")) {
        while ($row = $res->fetch_row()) {
            $t = (string)($row[0] ?? '');
            if ($t !== '' && (strpos($t, 'edtb_') === 0 || strpos($t, 'user_') === 0)) {
                $allowed[] = $t;
            }
        }
        $res->free();
    }

    if (!$allowed) {
        // Fallback: adjust to your install if needed
        $allowed = [
            'edtb_systems',
            'edtb_stations',
            'edtb_factions',
            'edtb_conflicts',
            'edtb_distances',
        ];
    }

    sort($allowed);
    return $allowed;
}

/**
 * Build list-view fields and friendly labels for MySQLtabledit
 * Returns: [$fieldsInListView, $showTextAssoc]
 *
 * @param mysqli $mysqli
 * @param string $table
 * @return array{0: string[], 1: array<string,string>}
 */
function datapoint_get_column_labels(mysqli $mysqli, string $table): array
{
    $fields = [];
    $labels = [];

    // Try to use column comments to generate human labels
    $sql = sprintf('SHOW FULL COLUMNS FROM `%s`', $mysqli->real_escape_string($table));
    if ($res = $mysqli->query($sql)) {
        while ($col = $res->fetch_assoc()) {
            $name    = (string)($col['Field'] ?? '');
            $comment = trim((string)($col['Comment'] ?? ''));

            if ($name === '') {
                continue;
            }

            $fields[] = $name;

            // Prefer explicit comment; otherwise build Title Case from snake_case
            if ($comment !== '') {
                $labels[$name] = $comment;
            } else {
                $labels[$name] = datapoint_titleize($name);
            }
        }
        $res->free();
    }

    // If SHOW FULL COLUMNS failed or returned nothing, try DESCRIBE
    if (!$fields) {
        $sql2 = sprintf('DESCRIBE `%s`', $mysqli->real_escape_string($table));
        if ($res2 = $mysqli->query($sql2)) {
            while ($col = $res2->fetch_assoc()) {
                $name = (string)($col['Field'] ?? '');
                if ($name === '') {
                    continue;
                }
                $fields[]       = $name;
                $labels[$name]  = datapoint_titleize($name);
            }
            $res2->free();
        }
    }

    // Last resort: at least avoid empty arrays (MySQLtabledit expects content)
    if (!$fields) {
        $fields = ['id'];
        $labels = ['id' => 'ID'];
    }

    return [$fields, $labels];
}

/**
 * Convert snake_case / lowercase to Title Case (e.g., "needs_permit" -> "Needs Permit")
 */
function datapoint_titleize(string $name): string
{
    // Replace underscores/dashes with spaces, collapse multiple spaces, ucwords
    $pretty = preg_replace('/[_\-]+/', ' ', $name);
    $pretty = preg_replace('/\s+/', ' ', $pretty ?? '');
    $pretty = trim((string)$pretty);
    if ($pretty === '') {
        return $name;
    }
    return mb_convert_case($pretty, MB_CASE_TITLE, 'UTF-8');
}
