<?php
/**
 * Render the allegiance icon strip (Empire / Alliance / Federation / Independent)
 * Uses the caller-provided $allegianceParams (string starting with '&' or empty).
 */
function render_allegiance_icons($allegianceParams)
{
    // $allegianceParams is a string starting with '&' (preserved GET params) or empty
    $params = (string)$allegianceParams;
    $base   = '/NearestSystems/?allegiance=';

    // Plain anchors; no Wiselinks data-*; no inline onclick on <img>
    $icons = [
        ['href' => $base . 'Alliance'    . $params, 'title' => 'Alliance',    'src' => '/style/img/alliance.png'],
        ['href' => $base . 'Empire'      . $params, 'title' => 'Empire',      'src' => '/style/img/empire.png'],
        ['href' => $base . 'Federation'  . $params, 'title' => 'Federation',  'src' => '/style/img/federation.png'],
        ['href' => $base . 'Independent' . $params, 'title' => 'Independent', 'src' => '/style/img/elite.png'],
    ];

    echo '<div class="ns-allegiances" style="display:flex;gap:10px;align-items:center;">' . PHP_EOL;

    foreach ($icons as $icon) {
        $href  = $icon['href'];
        $title = $icon['title'];
        $src   = $icon['src'];

        echo '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '" title="' . htmlspecialchars($title, ENT_QUOTES) . '">'
           . '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . htmlspecialchars($title, ENT_QUOTES) . '"></a>' . PHP_EOL;
    }

    echo '</div>' . PHP_EOL;
}



