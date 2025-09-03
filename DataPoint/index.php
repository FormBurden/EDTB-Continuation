<?php
/**
 * Data Point
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

/**
 * Start session
 */
session_start();

/** @require Theme class */
require_once __DIR__ . '/../style/Theme.php';

/**
 * initiate page header
 */
$header = new Header();

/** @var string page_title */
$header->pageTitle = 'Data Point';

/**
 * display the header
 */
$header->displayHeader();
/** DataPoint bootstrap: session flag + DB globals for MySQLtabledit */
if (!isset($_SESSION['content_saved'])) {
    $_SESSION['content_saved'] = '';
}

/** Map $settings DB config to globals for the vendor class */
$server = $settings['db_host'];
$user   = $settings['db_user'];
$pwd    = $settings['db_pass'];
$db     = $settings['db_name'];


/** @require functions file */
require_once __DIR__ . '/functions.php';
/** @require MySQL table edit class */
require_once __DIR__ . '/Vendor/MySQL_table_edit/mte.php';

/** @var string $dataTable */
$dataTable = $_GET['table'] ?? $settings['data_view_default_table'];

/**
 * initate MySQLtabledit class
 */
$tabledit = new MySQLtabledit();

/** @var string table */
$tabledit->table = $dataTable;

/**
 * get column comment from database to use as a name for the fields
 */
$query = "  SELECT COLUMN_NAME, COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$dataTable'";

$result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

$output = [];
$showt = [];

while ($columnObj = $result->fetch_object()) {
    $output[] = $columnObj->COLUMN_NAME;
    $showt[$columnObj->COLUMN_NAME] = $columnObj->COLUMN_COMMENT;
}

$result->close();

$tabledit->linksToDb        = $settings['data_view_table'] ?? [$dataTable => $dataTable];
$tabledit->skip             = $settings['data_view_ignore'][$dataTable] ?? [];
$tabledit->primaryKey       = 'id';
$tabledit->fieldsInListView = $output;
$tabledit->numRowsListView  = 10;
//$tabledit->fieldsRequired = array('name');
$tabledit->urlBase          = 'Vendor/MySQL_table_edit/';
$tabledit->urlScript        = '/DataPoint';
$tabledit->showText         = $showt;

?>
    <div class="entries">
    <div class="entries_inner">
<?php
    /* DataPoint: seed params + label column before invoking editor */
    $tbl = (isset($_GET['table']) && $_GET['table'] !== '')
        ? $_GET['table']
        : ($settings['data_view_default_table'] ?? 'edtb_systems');

    /* Choose the human-readable label column per table */
    $labelCol = 'name';
    if ($tbl === 'edtb_stations') { $labelCol = 'station_name'; }
    elseif ($tbl === 'user_log')  { $labelCol = 'title'; }

    /* Expose to the vendor class via query params (it reads $_GET directly) */
    if (empty($_GET['label_col'])) { $_GET['label_col'] = $labelCol; }

    /* Quiet legacy code that reads $_GET directly later */
    if (!isset($_GET['sort'])) { $_GET['sort'] = ''; }
    if (!isset($_GET['ad']))   { $_GET['ad']   = 'a'; }
    if (!isset($_GET['s']))    { $_GET['s']    = ''; }

    /* Satisfy vendor object property expectations */
    if (!isset($tabledit->width_editor))    { $tabledit->width_editor    = '100%'; }
    if (!isset($tabledit->debug_html))      { $tabledit->debug_html      = false; }
    if (!isset($tabledit->content_deleted)) { $tabledit->content_deleted = ''; }

    /* Render the editor */
    $tabledit->do_it();
?>
        </div>

    </div>
<?php

/**
 * initiate page footer
 */
$footer = new Footer();

/**
 * display the footer
 */
$footer->displayFooter();
