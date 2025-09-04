<?php
/**
 * Render the "Powers" links list (PowerPlay leaders).
 *
 * @param mysqli $mysqli
 * @param string $powerParams  The existing query string tail used in this page (e.g., "&pad=L&station_type=all")
 * @param string $currentPower The current ?power=... value ('' if none)
 * @param bool   $hasTable     True if edtb_powers table exists (caller passes $this->tableExists('edtb_powers'))
 */
function render_powers_links($mysqli, $powerParams, $currentPower, $hasTable)
{
    if (!$hasTable) {
        echo '<em style="opacity:.7">Powers list unavailable</em>';
        return;
    }

    // Clean out any existing ?power=... from the params tail (matches original inline logic).
    $cleanParams = $powerParams;
    if ($currentPower !== '') {
        $cleanParams = str_replace('&power=', '', $cleanParams);
        $cleanParams = str_replace('?power=', '', $cleanParams);
        $cleanParams = str_replace(rawurlencode($currentPower), '', $cleanParams);
    }

    $query  = 'SELECT name FROM edtb_powers ORDER BY name';
    $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

    while ($row = $result->fetch_object()) {
        $name   = $row->name;
        $href   = '/NearestSystems/?power=' . rawurlencode($name) . $cleanParams;
        $title  = htmlspecialchars($name, ENT_QUOTES);

        echo '<a data-replace="true" data-target="#nscontent" href="', $href, '" title="', $title, '">', $title, '</a><br>';
    }

    $result->close();
}
