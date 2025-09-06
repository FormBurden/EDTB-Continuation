<?php
/**
 * Footer class
 *
 * @package EDTB\Backend
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

 // Linux build: no legacy installer; keep variable benign
 $installer = '';
if (is_file($installer)) {
    require_once $installer;
}
/** @require config */
require_once __DIR__ . '/../source/config.inc.php';
/** @require MySQL */
require_once __DIR__ . '/../source/MySQL.php';
/** @require functions */
require_once __DIR__ . '/../source/functions.php';
/** @require curSys */
require_once __DIR__ . '/../source/curSys.php';

use \EDTB\style\Theme;

/**
 * Footer
 *
 * @author Mauri Kujala <contact@edtb.xyz>
 */
class Footer extends Theme
{
     /**
     * Display footer
     */
    public function displayFooter()
    {
        // include the heavy UI/JS chunk as a partial to keep this file slim
        include __DIR__ . '/footer/rightpanel.php';
        ?>
        </body>
        </html>
        <?php
    }

}
