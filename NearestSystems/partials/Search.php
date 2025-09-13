<?php
/**
 * Nearest Systems — search box with live suggestions
 * Uses /get/getSystemNames.php to populate a suggestion list while typing.
 */
function render_nearestsystems_search($hiddenInputs)
{
    $q = isset($_GET['search']) ? (string)$_GET['search'] : '';

    // Preserve active filters in suggestion requests (match original backend params)
    $addtl = '';
    if (isset($_GET['allegiance']) && $_GET['allegiance'] !== 'undefined' && $_GET['allegiance'] !== '') {
        $addtl .= '&allegiance=' . rawurlencode((string)$_GET['allegiance']);
    }
    if (isset($_GET['system_allegiance']) && $_GET['system_allegiance'] !== 'undefined' && $_GET['system_allegiance'] !== '') {
        $addtl .= '&system_allegiance=' . rawurlencode((string)$_GET['system_allegiance']);
    }
    if (isset($_GET['power']) && $_GET['power'] !== 'undefined' && $_GET['power'] !== '') {
        $addtl .= '&power=' . rawurlencode((string)$_GET['power']);
    }

    echo '<form id="ns-search-form" class="ns-search" action="/NearestSystems/index.php" method="get" autocomplete="off">' . PHP_EOL;
    echo $hiddenInputs . PHP_EOL;
    echo '  <input type="text" name="search" id="ns-search" value="' . htmlspecialchars($q, ENT_QUOTES) . '" placeholder="Search systems…" style="width:100%;">' . PHP_EOL;
    echo '  <div id="ns-suggest" class="ns-suggest"></div>' . PHP_EOL;
    echo '</form>' . PHP_EOL;

    // Inline, page-local JS to bind the input and call the existing endpoint
    echo '<script>' . PHP_EOL;
    echo '(function(){' . PHP_EOL;
    echo '  var box = $("#ns-search");' . PHP_EOL;
    echo '  var list = $("#ns-suggest");' . PHP_EOL;
    echo '  var base = "/get/getSystemNames.php?divid=ns-suggest&link=yes' . $addtl . '";' . PHP_EOL;
    echo '  var t = null;' . PHP_EOL;
    echo '  function fetchSuggestions(v){' . PHP_EOL;
    echo '    if (!v || v.length < 2){ list.empty(); return; }' . PHP_EOL;
    echo '    // Pull small HTML snippet from the server and drop it in the suggestion box' . PHP_EOL;
    echo '    list.load(base + "&q=" + encodeURIComponent(v));' . PHP_EOL;
    echo '  }' . PHP_EOL;
    echo '  box.on("input", function(){' . PHP_EOL;
    echo '    if (t) clearTimeout(t);' . PHP_EOL;
    echo '    var v = this.value;' . PHP_EOL;
    echo '    t = setTimeout(function(){ fetchSuggestions(v); }, 150);' . PHP_EOL; // debounce
    echo '  });' . PHP_EOL;
    echo '  box.on("focus", function(){ fetchSuggestions(this.value); });' . PHP_EOL;
    echo '  $(document).on("click", function(e){' . PHP_EOL;
    echo '    if (!$(e.target).closest("#ns-search, #ns-suggest").length){ list.empty(); }' . PHP_EOL;
    echo '  });' . PHP_EOL;
    echo '})();' . PHP_EOL;
    echo '</script>' . PHP_EOL;
}
