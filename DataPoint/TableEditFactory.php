<?php
/**
 * DataPoint TableEdit factory — vendor instance config extracted from index.php
 * Defines: $tabledit (MySQLtabledit)
 */

require_once __DIR__ . '/Vendor/MySQL_table_edit/mte.php';

$tabledit = new MySQLtabledit();

$tabledit->table               = $dataTable;
$tabledit->links_to_db         = $linksMap;
$tabledit->linksToDb           = $linksMap;
$tabledit->primary_key         = 'id';
$tabledit->primaryKey          = 'id';
$tabledit->fields_in_list_view = $fieldsInListView;
$tabledit->fieldsInListView    = $fieldsInListView;
$tabledit->show_text           = $showText;
$tabledit->showText            = $showText;
$tabledit->num_rows_list_view  = $rows;
$tabledit->numRowsListView     = $rows;
$tabledit->url_base            = 'Vendor/MySQL_table_edit/';
$tabledit->urlBase             = 'Vendor/MySQL_table_edit/';
$tabledit->url_script          = '/DataPoint';
$tabledit->urlScript           = '/DataPoint';

if (!empty($config['order_by'])) { $tabledit->order_by = $config['order_by']; }
