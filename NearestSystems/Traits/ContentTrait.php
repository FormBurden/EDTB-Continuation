<?php
/** Auto-extracted trait from NearestSystems.php to reduce file size. */
trait NearestSystemsContentTrait
{
    private function content()
    {
     /** Build hidden inputs for the small GET forms to preserve current state. */
    $keep = $_GET;
    unset($keep['facility'], $keep['ship_name'], $keep['station_type'], $keep['pad'], $keep['search']);
    $this->hiddenInputs = '';
    foreach ($keep as $k => $v) {
        $this->hiddenInputs .= '<input type="hidden" name="' . htmlspecialchars((string)$k, ENT_QUOTES) . '" value="' . htmlspecialchars((string)$v, ENT_QUOTES) . '" />' . PHP_EOL;
    }

    /** Original list size behavior */
    $add = " LIMIT 100";
    $this->mainQuery .= $add;

    /** Run query */
    $result = $this->safeQuery($this->mainQuery);

    /** Outer wrapper so data-target="#nscontent" links replace the correct region */
    echo '<div id="nscontent" class="ns-wrap">';

    /** Left column: allegiance icons, filters, powers, search */
    echo '<div class="ns-left">';
    render_allegiance_icons($this->allegianceParams);

    echo '<div class="ns-actions">';
    render_nearestsystems_search($this->hiddenInputs);
    render_facilities_filter($this->mysqli, $this->hiddenInputs);
    render_station_type_filter($this->hiddenInputs);
    render_landing_pads_filter($this->hiddenInputs);
    render_ships_filter($this->mysqli, $this->hiddenInputs);
    echo '</div>';

    $hasPowers = ($this->mysqli->query("SHOW TABLES LIKE 'edtb_powers'")->num_rows > 0);
    echo '<div class="ns-powers">';
    render_powers_links(
        $this->mysqli,
        $this->powerParams,
        isset($_GET['power']) ? (string)$_GET['power'] : '',
        $hasPowers
    );
    echo '</div>';

    echo '</div>'; /** .ns-left */

    /** Right column: results/table */
    echo '<div class="ns-main">';
    $this->results($result);
    echo '</div>'; /** .ns-main */

    echo '</div>'; /** #nscontent */

    }

    private function results($result)
    {
        /**
         * results!
         */
        if ($result === false) {
            write_log('Error during query', __FILE__, __LINE__);
            echo '<div class="box">Something went wrong querying the database.</div>';
            return;
        }

        $rows = $result->num_rows;

        if ($rows > 0) {
            echo NearestSystemsTableFormatter::tableOpen($this->stations);
            echo NearestSystemsTableFormatter::header($this->stations);

            while ($row = $result->fetch_object()) {
                echo NearestSystemsTableFormatter::row($row, $this->stations);
            }

            echo NearestSystemsTableFormatter::tableClose();

        } else {
            echo '<div class="box">No results' . $this->is_unknown . '.</div>';
        }

        $result->close();
    }
}
