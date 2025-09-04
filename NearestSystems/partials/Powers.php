<?php
/**
 * Render the "Powers" select form (PowerPlay leaders).
 *
 * @param mysqli $mysqli
 * @param string $hiddenInputs Prebuilt hidden inputs string from the caller ($this->hiddenInputs)
 */
function render_powers_filter($mysqli, $hiddenInputs)
{
    $sel = isset($_GET['power']) ? (string)$_GET['power'] : '0';

    echo '<form method="get" action="/NearestSystems/" name="go" id="powers"'
       . ' data-push="true" data-target="#nscontent" data-include-blank-url-params="true"'
       . ' data-optimize-url-params="false">' . PHP_EOL;

    echo $hiddenInputs . PHP_EOL;

    echo '<select title="Power" class="selectbox" name="power" style="width: 180px"'
       . ' onchange="$(\'.se-pre-con\').show();this.form.submit()">' . PHP_EOL;

    echo '    <option value="0"' . ($sel === '0' ? " selected='selected'" : '') . '>Under Power</option>' . PHP_EOL;

    $query  = 'SELECT name FROM edtb_powers ORDER BY name';
    $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

    while ($row = $result->fetch_object()) {
        $name = $row->name;
        $selected = ($sel === $name) ? " selected='selected'" : '';
        echo '    <option value="' . $name . '"' . $selected . '>' . $name . '</option>' . PHP_EOL;
    }

    $result->close();

    echo '</select><br/>' . PHP_EOL;
    echo '</form>' . PHP_EOL;
}
