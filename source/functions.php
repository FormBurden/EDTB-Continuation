<?php
/**
 * Functions
 *
 * No description
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

/** @require config */
require_once __DIR__ . '/config.inc.php';
/** @require other functions */
require_once __DIR__ . '/functions_safe.php';
/** @require curSys */
//require_once("curSys.php"); // can't require curSys here, it interferes with the data update
/** @require mappings */
require_once __DIR__ . '/FDMaps.php';
/** @require utility */
require_once __DIR__ . '/Vendor/utility.php';
/** @require System class */
require_once __DIR__ . '/System.php';

require_once __DIR__ . '/Helpers/Coords.php';
require_once __DIR__ . '/Helpers/Icons.php';
require_once __DIR__ . '/Helpers/Text.php';
require_once __DIR__ . '/Helpers/Game.php';
require_once __DIR__ . '/Helpers/Common.php';














