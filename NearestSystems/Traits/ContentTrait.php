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
            echo NearestSystemsTableFormatter::tableOpen();
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
