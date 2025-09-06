<?php
/**
 * System map
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

/** @require Theme class */
require_once __DIR__ . '/../style/Theme.php';

/**
 * initiate page header
 */
$header = new Header();

/** @var string page_title */
$header->pageTitle = 'System Map';

/**
 * display the header
 */
$header->displayHeader();

/**
 * determine what system to display
 */
$system = $curSys['name'];
if (isset($_GET['system'])) {
    $system = $_GET['system'];
}

/**
 * get string if system already mapped
 */
$escSystemName = $mysqli->real_escape_string($system);

$query = "  SELECT string
            FROM user_system_map
            WHERE system_name = '$escSystemName'
            LIMIT 1";

$result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);
$obj = $result->fetch_object();

$string = $obj->string;

$result->close();

$linkMap = !empty($string) ? '<span id="mlink">&nbsp;&ndash;&nbsp;<a href="http://map.edtb.xyz?v1=' . $string . '" target="_blank" id="maplink" title="View on map.edtb.xyz">View on map.edtb.xyz</a></span>' : '<span id="mlink"></span>';
?>
<link type="text/css" href="/source/Vendor/jquery-ui-1.11.4/jquery-ui.min.css" rel="stylesheet" />
<script src="/source/Vendor/timmywil-jquery-panzoom/panzoom.js"></script>
<script src="/source/Vendor/jquery-ui-1.11.4/jquery-ui.min.js"></script>
<script src="/source/Vendor/color-thief.js"></script>
<script src="SystemMap.js"></script>
<script>window.__SM_URL_VARS = <?= $string !== '' ? json_encode($string) : 'null' ?>;</script>
<script src="Init.js"></script>
<section id="focal">
    <div class="entries explor_entries">
    <?php include __DIR__ . '/Partials/Controls.php'; ?>

        <div class="panzoom"></div>

    </div>
</section>

<?php
/**
 * initiate page footer
 */
$footer = new Footer();

/**
 * display the footer
 */
$footer->displayFooter();
