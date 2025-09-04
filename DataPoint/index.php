<?php
/**
 * Data Point
 *
 * @package EDTB\Main
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

/** Map $settings DB config to globals for the vendor class */
global $settings;
$server = $settings['db_host'];
$user   = $settings['db_user'];
$pwd    = $settings['db_pass'];
$db     = $settings['db_name'];

/** @require functions file */
require_once __DIR__ . '/functions.php';
/** @require MySQL table edit class */
require_once __DIR__ . '/Vendor/MySQL_table_edit/mte.php';

/** @var string $dataTable */
$dataTable = $_GET['table'] ?? ($settings['data_view_default_table'] ?? 'edtb_systems');

/**
 * initate MySQLtabledit class
 */
$tabledit = new MySQLtabledit();

/** @var string table */
$tabledit->table = $dataTable;

/**
 * get column comment from database to use as a name for the fields
 */
global $mysqli;
$output = [];
$showt  = [];

/** Column list + labels (moved to Schema.php) */
require_once __DIR__ . '/Schema.php';
list($output, $showt) = datapoint_get_column_labels($mysqli, $dataTable);

/** Configure tabledit (set both camelCase and snake_case properties for compatibility) */
$tabledit->linksToDb            = $settings['data_view_table'] ?? [$dataTable => $dataTable];
$tabledit->links_to_db          = $tabledit->linksToDb;
$tabledit->skip                 = $settings['data_view_ignore'][$dataTable] ?? [];
$tabledit->primaryKey           = 'id';
$tabledit->primary_key          = 'id';
$tabledit->fieldsInListView     = $output;
$tabledit->fields_in_list_view  = $output;
$tabledit->numRowsListView      = 10;
$tabledit->num_rows_list_view   = 10;
$tabledit->urlBase              = 'Vendor/MySQL_table_edit/';
$tabledit->url_base             = 'Vendor/MySQL_table_edit/';
$tabledit->urlScript            = '/DataPoint';
$tabledit->url_script           = '/DataPoint';
$tabledit->showText             = $showt;
$tabledit->show_text            = $showt;


/** Configure tabledit (set both camelCase and snake_case properties for compatibility) */
$tabledit->linksToDb            = $settings['data_view_table'] ?? [$dataTable => $dataTable];
$tabledit->links_to_db          = $tabledit->linksToDb;
$tabledit->skip                 = $settings['data_view_ignore'][$dataTable] ?? [];
$tabledit->primaryKey           = 'id';
$tabledit->primary_key          = 'id';
$tabledit->fieldsInListView     = $output;
$tabledit->fields_in_list_view  = $output;
$tabledit->numRowsListView      = 10;
$tabledit->num_rows_list_view   = 10;
$tabledit->urlBase              = 'Vendor/MySQL_table_edit/';
$tabledit->url_base             = 'Vendor/MySQL_table_edit/';
$tabledit->urlScript            = '/DataPoint';
$tabledit->url_script           = '/DataPoint';
$tabledit->showText             = $showt;
$tabledit->show_text            = $showt;

?>
    <div class="entries">
        <div class="entries_inner">
<?php
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
