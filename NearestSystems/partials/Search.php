<?php
/**
 * Render the "Search systems and stations" form used on Nearest Systems.
 *
 * @param string $hiddenInputs Prebuilt hidden inputs string from the caller ($this->hiddenInputs)
 */
function render_nearestsystems_search($hiddenInputs)
{
    $q = isset($_GET['search']) ? (string)$_GET['search'] : '';

    echo '<div style="text-align: left">' . PHP_EOL;
    echo '  <div style="width: 180px">' . PHP_EOL;

    echo '    <form method="get" action="/NearestSystems/" name="go"'
       . ' data-push="true" data-target="#nscontent" data-include-blank-url-params="true"'
       . ' data-optimize-url-params="false">' . PHP_EOL;

    echo $hiddenInputs . PHP_EOL;

    echo '      <input type="text" class="system_search_field" name="search"'
       . ' value="' . htmlspecialchars($q, ENT_QUOTES) . '"'
       . ' placeholder="Search systems & stations" style="width: 180px"'
       . ' onkeydown="if(event.key===\'Enter\'){ $(\'.se-pre-con\').show(); this.form.submit(); }" />' . PHP_EOL;

    echo '      <br />' . PHP_EOL;

    echo '      <input class="btn btn-default" type="submit" value="Search"'
       . ' onclick="$(\'.se-pre-con\').show();" />' . PHP_EOL;

    echo '    </form>' . PHP_EOL;

    echo '  </div>' . PHP_EOL;
    echo '</div>' . PHP_EOL;
}
