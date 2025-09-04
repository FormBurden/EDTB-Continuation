<?php
/**
 * Render the allegiance icon strip (Empire / Alliance / Federation / Independent)
 * Uses the caller-provided $allegianceParams (string starting with '&' or empty).
 */
function render_allegiance_icons($allegianceParams)
{
    $icons = [
        'Empire'      => 'empire.png',
        'Alliance'    => 'alliance.png',
        'Federation'  => 'federation.png',
        'Independent' => 'system.png',
    ];

    $i = 0;
    $total = count($icons);

    foreach ($icons as $name => $file) {
        $href  = '/NearestSystems/?allegiance=' . rawurlencode($name) . $allegianceParams;
        $title = htmlspecialchars($name, ENT_QUOTES);

        echo '<a data-replace="true" data-target="#nscontent" href="', $href, '" title="', $title, '">';
        echo '<img src="/style/img/', $file, '" class="allegiance_icon" alt="', $title, '"/>';
        echo '</a>';

        if (++$i < $total) {
            echo '&nbsp;';
        }
    }
}
