<?php
/** Auto-extracted trait from NearestSystems.php to reduce file size. */
trait NearestSystemsContentTrait
{
    private function content()
    {
        /**
         * thing
         */
        $add = " LIMIT 100";

        $this->mainQuery .= $add;

        $result = $this->safeQuery($this->mainQuery);

        $this->results($result);
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
            echo '<table class="system_table">';
            echo '<tr><th>System</th>';
            if ($this->stations !== false) {
                echo '<th>Station</th><th>LS From Star</th><th>Pad</th><th>Type</th>';
            }
            echo '<th>Allegiance</th><th>Gov</th><th>Sec</th><th>Eco</th><th>Pop</th></tr>';

            while ($row = $result->fetch_object()) {
                $this->stationInfo($row);
            }

            echo '</table>';
        } else {
            echo '<div class="box">No results' . $this->is_unknown . '.</div>';
        }

        $result->close();
    }

    private function stationInfo($row)
    {
        echo '<tr>';
        echo '<td>' . $row->system . '</td>';

        if ($this->stations !== false) {
            echo '<td>' . $row->station_name . '</td>';
            echo '<td>' . $row->ls_from_star . '</td>';
            echo '<td>' . $row->max_landing_pad_size . '</td>';
            echo '<td>' . $row->type . '</td>';
        }

        echo '<td>' . ($row->allegiance ?? '') . '</td>';
        echo '<td>' . ($row->government ?? '') . '</td>';
        echo '<td>' . ($row->security ?? '') . '</td>';
        echo '<td>' . ($row->economy ?? '') . '</td>';
        echo '<td>' . number_format((int)($row->population ?? 0)) . '</td>';

        echo '</tr>';
    }
}
