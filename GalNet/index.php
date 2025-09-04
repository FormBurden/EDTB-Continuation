<?php
/**
 * Galnet news
 *
 * No description
 *
 * @package EDTB\Main
 * @author Mauri Kujala <contact@edtb.xyz>
 * @copyright Copyright (C) 2016, Mauri Kujala
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 */

/*
* ED ToolBox, a companion web app for the video game Elite Dangerous
* (C) 1984 - 2016 Frontier Developments Plc.
* ED ToolBox or its creator are not affiliated with Frontier Developments Plc.
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
*/

/** @require phpfastcache */
require_once __DIR__ . '/../source/Vendor/phpfastcache/phpfastcache.php';

/** @require Theme class */
require_once __DIR__ . '/../style/Theme.php';

/**
 * initiate page header
 */
$header = new Header();

/** @var string page_title */
$header->pageTitle = 'Galnet News';

/**
 * display the header
 */
$header->displayHeader();

/**
 * get cached content
 */
$html = __c('files')->get('galnet');

if ($html === null) {
    ob_start();
    ?>
    <div class="entries">
        <div class="entries_inner">
            <?php include __DIR__ . '/partials/header.php'; ?>

            <?php
            require_once __DIR__ . '/GalnetProvider.php';
            require_once __DIR__ . '/GalnetCache.php';


            $provider = new GalnetProvider();
            $feed = GalnetCache::remember('galnet_latest_12', 600, function() use ($provider) { return $provider->fetchLatest(12); });

            // Render the list items (uses $feed and $settings['galnet_excludes'])
            include __DIR__ . '/partials/list.php';
            
    $html = ob_get_contents();
    // Save to Cache for 30 minutes
    __c('files')->set('galnet', $html, 1800);

    /**
     * initiate page footer
     */
    $footer = new Footer();

    /**
     * display the footer
     */
    $footer->displayFooter();

    exit;
}
echo $html;

/**
 * initiate page footer
 */
$footer = new Footer();

/**
 * display the footer
 */
$footer->displayFooter();
