<?php
/**
 * System information
 *
 * Front-end file for System information
 *
 * @package   EDTB\Main
 * @author    Mauri Kujala <contact@edtb.xyz>
 * @copyright Copyright (C) 2016, Mauri Kujala
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
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
$header->pageTitle = 'System Information';

/**
 * display the header
 */
$header->displayHeader();
?>
<div class="entries">
    <div class="entries_inner" id="system_page">
        <h2 id="si_name"></h2>
        <hr>
        <div class="systeminfo_st">
            <!-- STATIONS -->
            <div class="systeminfo_stations" id="si_stations"></div>
        </div>
        <div class="systeminfo_sy">
            <!-- SYSTEM INFO -->
            <div class="systeminfo_system" id="si_detailed"></div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
            // Fetch just the System Information JSON and inject into this page's containers
            fetch('/System/getData_systemInfo.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j) return;
                var nameEl     = document.getElementById('si_name');
                var stationsEl = document.getElementById('si_stations');
                var detailedEl = document.getElementById('si_detailed');
                if (nameEl &&     typeof j.si_name     !== 'undefined') nameEl.innerHTML     = j.si_name     || '';
                if (stationsEl && typeof j.si_stations !== 'undefined') stationsEl.innerHTML = j.si_stations || '';
                if (detailedEl && typeof j.si_detailed !== 'undefined') detailedEl.innerHTML = j.si_detailed || '';
            })
            .catch(console.error);
        
    } catch (e) {
        console.error(e);
    }
});
</script>

<?php
/**
 * initiate page footer
 */
$footer = new Footer();
?>


<?php
/**
 * display the footer
 */
$footer->displayFooter();
