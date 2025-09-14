<?= /** BEGIN nav_links.php (moved from Header::navLinks) */ '' ?>
<?php
global $settings;
/**
 * Links for the navigation panel
 */
$maplink = $settings['default_map'] === 'galaxy_map' ? '/GalMap' : '/Map';

$links = [
        'ED ToolBox--log.png--false' => '/EDToolbox/',
        'System Information--info.png--false' => '/System',
        'Galaxy Map--grid.png--false' => '/GalMap',
        'Neighborhood Map--grid.png--false' => '/Map',
        'Points of Interest&nbsp;&nbsp;&&nbsp;&nbsp;Bookmarks--poi.png--false' => '/Bookmarks',
        'Nearest Systems&nbsp;&nbsp;&&nbsp;&nbsp;Stations--find.png--false' => '/NearestSystems',
        'Data Point--dataview.png--false' => '/DataPoint',
        'Galnet News--news.png--false' => '/GalNet',
        'Screenshot Gallery--gallery.png--false' => '/Gallery',
        'Rare Commodities--rare.png--false' => '/RareCommodities',
        'Map Creator--map_settings.png--false' => '/Map',
];

$i = 1;
foreach ($links as $name => $linkHref) {
    $a = explode('--', $name);
    $name = $a[0];
    $pic = $a[1];
    $push = ($a[2] ?? 'false') === 'true';

    $aclass = $push ? ' data-push="true"' : '';
    $onclick = $name === 'System Information' ? ' onclick="return openSystemInfo(event)"' : '';

    $styling = $name === 'ED ToolBox' ? ' style="height:26px;margin-top:7px"' : '';

    $class = 'links_link';
    $class .= $name === 'ED ToolBox' ? ' edtb' : '';
    $class .= ' topmar';

    if ($name === 'ED ToolBox') {
        echo '<a id="nav-edtoolbox" class="links_link topmar" href="/EDToolbox/" data-hard-nav="1">';
        echo '<div id="link_' . $i . '" class="' . $class . '">';
        echo '<img src="/style/img/' . $pic . '" alt="pic" class="icon"' . $styling . '>' . 'ED TOOLBOX';
        echo '</div>';
        echo '</a>';
    } else {

        echo '<a' . $aclass . $onclick . ' href="' .  $linkHref . '">';
        echo '<div id="link_' . $i . '" class="' . $class . '">';
        echo '<img src="/style/img/' . $pic . '" alt="pic" class="icon">' . $name;
        echo '</div>';
        echo '</a>';
    }
    $i++;
}
?>
<?= /** END nav_links.php */ '' ?>
