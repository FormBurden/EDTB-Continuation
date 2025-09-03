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
    try {
        if (typeof get_data === 'function') {
            // Use the existing global fetcher (same code path as the front page)
            get_data(true);
        } else {
            // Fallback (in case get_data() isn’t defined on this template)
            fetch('/get/getData.php?request=0', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(j => {
                    if (j && j.log_data !== undefined) {
                        var el = document.getElementById('scrollable');
                        if (el) el.innerHTML = j.log_data || '';
                    }
                })
                .catch(console.error);
        }
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
<script>
(function () {
  function loadSystemInfo() {
    try {
      var params = {};
      var sName   = <?php echo json_encode($_GET['system_name'] ?? ''); ?>;
      var sId     = <?php echo json_encode($_GET['system_id']   ?? ''); ?>;
      var sLegacy = <?php echo json_encode($_GET['system']      ?? ''); ?>;
      if (!sName && sLegacy) sName = sLegacy;

      if (sName) params.system_name = sName;
      if (sId)   params.system_id   = sId;

      $.ajax({
        url: '/System/getData_systemInfo.php',
        data: params,
        dataType: 'json',
        cache: false
      })
      .done(function (d) {
        var name = d && d.si_name     ? d.si_name     : '';
        var st   = d && d.si_stations ? d.si_stations : '';
        var det  = d && d.si_detailed ? d.si_detailed : '';

        $('#si_name').html(name);
        $('#si_stations').html(st);
        $('#si_detailed').html(det);

        if (!name && !st && !det) {
          $('#si_name').html('<div class="light">No data returned for "' + (sName || sId || '') + '".</div>');
        }
      })
      .fail(function (xhr) {
        var msg = 'Failed to load system info';
        if (xhr && xhr.status) msg += ' (' + xhr.status + ')';
        if (xhr && xhr.responseText) {
          var snippet = $('<div>').text(xhr.responseText).text().slice(0, 300);
          msg += ': ' + snippet;
        }
        $('#si_name').html('<div class="light">' + msg + '</div>');
      });
    } catch (e) {
      console.error(e);
    }
  }

  // Run after PJAX page swaps (Wiselinks)
  if (window.jQuery) {
    $(document).on('page:load', loadSystemInfo);
  }
  // Run on full page load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadSystemInfo);
  } else {
    loadSystemInfo();
  }
})();
</script>


<?php
/**
 * display the footer
 */
$footer->displayFooter();
