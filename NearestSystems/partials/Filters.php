<?php
/**
 * Render the "Facilities" select form used on Nearest Systems.
 *
 * @param mysqli $mysqli
 * @param string $hiddenInputs Prebuilt hidden inputs string from the caller ($this->hiddenInputs)
 */
function render_facilities_filter($mysqli, $hiddenInputs)
{
    echo '<form method="get" action="/NearestSystems/" name="go" id="facilities">' . PHP_EOL;

    echo $hiddenInputs . PHP_EOL;

    echo '<select title="Facility" class="selectbox" name="facility" style="width: 180px"'
       . ' onchange="$(\'.se-pre-con\').show();this.form.submit()">' . PHP_EOL;

    echo '    <option value="0"' . (isset($_GET['facility']) && $_GET['facility'] === '0' ? " selected='selected'" : '') . '>Has Facilities</option>' . PHP_EOL;
    echo '    <option value="1"' . (isset($_GET['facility']) && $_GET['facility'] === '1' ? " selected='selected'" : '') . '>Outfitting</option>' . PHP_EOL;
    echo '    <option value="2"' . (isset($_GET['facility']) && $_GET['facility'] === '2' ? " selected='selected'" : '') . '>Shipyard</option>' . PHP_EOL;

    echo '</select><br/>' . PHP_EOL;
    echo '</form>' . PHP_EOL;
}



/**
 * Render the "Station Type" select form (space / planetary / all).
 *
 * @param string $hiddenInputs Prebuilt hidden inputs string from the caller ($this->hiddenInputs)
 */
function render_station_type_filter($hiddenInputs)
{
    $sel = isset($_GET['station_type']) ? (string)$_GET['station_type'] : 'all';

    echo '<form method="get" action="/NearestSystems/" name="go" id="stationtype">' . PHP_EOL;

    echo $hiddenInputs . PHP_EOL;

    echo '<select title="Station Type" class="selectbox" name="station_type" style="width: 180px"'
       . ' onchange="$(\'.se-pre-con\').show();this.form.submit()">' . PHP_EOL;

    echo '    <option value="space"'     . ($sel === 'space'     ? " selected='selected'" : '') . '>Space Stations</option>' . PHP_EOL;
    echo '    <option value="planetary"' . ($sel === 'planetary' ? " selected='selected'" : '') . '>Planetary Bases</option>' . PHP_EOL;
    echo '    <option value="all"'       . ($sel === 'all'       ? " selected='selected'" : '') . '>All</option>' . PHP_EOL;

    echo '</select><br/>' . PHP_EOL;
    echo '</form>' . PHP_EOL;
}



/**
 * Render the "Landing Pads" select form (Large / Medium).
 *
 * @param string $hiddenInputs Prebuilt hidden inputs string from the caller ($this->hiddenInputs)
 */
function render_landing_pads_filter($hiddenInputs)
{
    // Valid options in current codepath: 'L' (Large) or 'M' (Medium). Empty = no filter.
    $sel = isset($_GET['pad']) ? (string)$_GET['pad'] : '';

    echo '<form method="get" action="/NearestSystems/" name="go" id="landingpads">' . PHP_EOL;

    echo $hiddenInputs . PHP_EOL;

    echo '<select title="Landing Pads" class="selectbox" name="pad" style="width: 180px"'
       . ' onchange="$(\'.se-pre-con\').show();this.form.submit()">' . PHP_EOL;

    echo '    <option value=""'  . ($sel === ''  ? " selected='selected'" : '') . '>Large Pads</option>' . PHP_EOL;
    echo '    <option value="L"' . ($sel === 'L' ? " selected='selected'" : '') . '>Large</option>' . PHP_EOL;
    echo '    <option value="M"' . ($sel === 'M' ? " selected='selected'" : '') . '>Medium</option>' . PHP_EOL;

    echo '</select><br/>' . PHP_EOL;
    echo '</form>' . PHP_EOL;
}



/**
 * Render the "Ships" select form (populated from edtb_ships).
 *
 * @param mysqli $mysqli
 * @param string $hiddenInputs Prebuilt hidden inputs string from the caller ($this->hiddenInputs)
 */
function render_ships_filter($mysqli, $hiddenInputs)
{
    $sel = isset($_GET['ship_name']) ? (string)$_GET['ship_name'] : '0';

    echo '<form method="get" action="/NearestSystems/" name="go" id="ships">' . PHP_EOL;

    echo $hiddenInputs . PHP_EOL;

    echo '<select title="Ship" class="selectbox" name="ship_name" style="width: 180px"'
       . ' onchange="$(\'.se-pre-con\').show();this.form.submit()">' . PHP_EOL;

    echo '    <option value="0"' . ($sel === '0' ? " selected='selected'" : '') . '>Sells Ships</option>' . PHP_EOL;

    $query  = 'SELECT name FROM edtb_ships ORDER BY name';
    $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

    while ($shipObj = $result->fetch_object()) {
        $selected = ($sel === $shipObj->name) ? " selected='selected'" : '';
        echo '    <option value="' . $shipObj->name . '"' . $selected . '>' . $shipObj->name . '</option>' . PHP_EOL;
    }

    $result->close();

    echo '</select><br/>' . PHP_EOL;
    echo '</form>' . PHP_EOL;
}
