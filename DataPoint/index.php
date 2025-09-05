<?php
/**
 * Data Point — original-repo features v2
 * - Tabs across tables (vendor linksToDb)
 * - Remembers last table
 * - Rows-per-page persisted (10/25/50/100)
 * - CSV export endpoint (export.php)
 * - Distance sort toggle (when applicable)
 * - NEW: Per-table Quick Filters (chips that auto-fill vendor search and submit)
 * - NEW: Column Visibility Presets (per-table, hides/show columns client-side)
 * - NEW: Boolean inputs in edit forms (checkboxes injected for bool fields)
 */

session_start();

/* ---- Core includes ---- */
require_once dirname(__DIR__) . '/source/config.inc.php';
require_once dirname(__DIR__) . '/source/config_ini.inc.php';
require_once dirname(__DIR__) . '/source/functions.php';

/* ---- UI header (no Theme dependency) ---- */
require_once dirname(__DIR__) . '/style/Header.php';

/* ---- Data Point helpers (from archive if present) ---- */
if (file_exists(__DIR__ . '/Formatter.php')) { require_once __DIR__ . '/Formatter.php'; }
if (file_exists(__DIR__ . '/functions.php')) { require_once __DIR__ . '/functions.php'; }

/* ---- Schema + per-table config ---- */
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/Tables.php';

/* ---- Safety shim ONLY IF setData is missing (keeps vendor happy) ---- */
if (!function_exists('setData')) {
    function setData($key, $value, $dX = null, $dY = null, $dZ = null, &$dist = null, $table = null, $enum = null) {
        $label = htmlspecialchars((string)$key);
        $val   = htmlspecialchars(is_scalar($value) ? (string)$value : json_encode($value));
        return "<td class=\"datapoint_td\" title=\"{$label}\">{$val}</td>";
    }
}

/* ---- Vendor table editor (unchanged) ---- */
require_once __DIR__ . '/Vendor/MySQL_table_edit/mte.php';

/* ---- DB handle + vendor globals ---- */
/** @var mysqli $mysqli */
global $mysqli, $server, $user, $pwd, $db, $settings;

/* Map config -> vendor globals */
$server = $server ?? ($settings['db_host'] ?? (defined('DB_HOST') ? DB_HOST : '127.0.0.1'));
if (!empty($settings['db_port'])) { $server .= ':' . (int)$settings['db_port']; }
$user   = $user ?? ($settings['db_user'] ?? (defined('DB_USER') ? DB_USER : 'root'));
$pwd    = $pwd  ?? ($settings['db_pass'] ?? (defined('DB_PASS') ? DB_PASS : ''));
$db     = $db   ?? ($settings['db_name'] ?? (defined('DB_NAME') ? DB_NAME : ''));

/* Ensure $mysqli exists and has a selected DB */
if (!($mysqli instanceof mysqli)) {
    $mysqli = @new mysqli($server, $user, $pwd, $db);
} else {
    $res = @$mysqli->query('SELECT DATABASE()');
    $row = $res ? $res->fetch_row() : null;
    if ($res) { $res->close(); }
    if (!$row || !$row[0]) { if ($db !== '') { @$mysqli->select_db($db); } }
}

/* ---- Allowed tables & default selection ---- */
$allowedTables = datapoint_table_whitelist($mysqli);

/* Prefer edtb_systems first in list */
usort($allowedTables, function($a, $b) {
    if ($a === 'edtb_systems') return -1;
    if ($b === 'edtb_systems') return 1;
    return strcmp($a, $b);
});

/* Remember last table */
if (isset($_GET['table'])) {
    $_SESSION['dp_last_table'] = (string)$_GET['table'];
}
$last = isset($_SESSION['dp_last_table']) ? (string)$_SESSION['dp_last_table'] : '';

$requested        = isset($_GET['table']) ? (string)$_GET['table'] : ($last ?: '');
$preferredDefault = in_array('edtb_systems', $allowedTables, true) ? 'edtb_systems' : ($allowedTables[0] ?? '');
$dataTable        = in_array($requested, $allowedTables, true) ? $requested : $preferredDefault;

/* ---- Rows-per-page (persist) ---- */
$rows = isset($_GET['rows']) ? (int)$_GET['rows'] : (isset($_SESSION['dp_rows']) ? (int)$_SESSION['dp_rows'] : 25);
if (!in_array($rows, [10,25,50,100], true)) { $rows = 25; }
$_SESSION['dp_rows'] = $rows;

/* ---- Columns + labels (auto) ---- */
list($allFields, $autoLabels) = datapoint_get_column_labels($mysqli, $dataTable);

/* ---- Per-table overrides (curated list + label overrides + filters + presets) ---- */
$config = function_exists('datapoint_config_for_table') ? datapoint_config_for_table($dataTable) : [];

if (!empty($config['list'])) {
    $fieldsInListView = [];
    foreach ($config['list'] as $f) {
        if (in_array($f, $allFields, true)) { $fieldsInListView[] = $f; }
    }
    if (!$fieldsInListView) { $fieldsInListView = $allFields; }
} else {
    $fieldsInListView = array_slice($allFields, 0, 7);
}

$showText = $autoLabels;
if (!empty($config['labels'])) {
    foreach ($config['labels'] as $k => $v) { $showText[$k] = $v; }
}

/* Precompute column index map for client-side formatters */
$colIndex = [];
foreach ($fieldsInListView as $i => $f) { $colIndex[$f] = $i; }

/* ---- Build full tab list (linksToDb) ---- */
$linksMap = [];
foreach ($allowedTables as $t) {
    if (function_exists('datapoint_table_title')) {
        $linksMap[$t] = datapoint_table_title($t);
    } else {
        $linksMap[$t] = strtoupper($t);
    }
}

/* ---- Configure vendor instance ---- */
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

/* ---- Distance sort availability ---- */
$canDistance = false;
if ($dataTable === 'edtb_systems' || $dataTable === 'edtb_stations') {
    $canDistance = true;
} else {
    $hasXYZ = in_array('x', $allFields, true) && in_array('y', $allFields, true) && in_array('z', $allFields, true);
    $hasSys = in_array('system_name', $allFields, true) || in_array('system_id', $allFields, true);
    $canDistance = $hasXYZ || $hasSys;
}

$ad  = isset($_GET['ad']) ? (string)$_GET['ad'] : 'a';
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : '';
$isDist = ($sort === 'distance');
$nextAd = ($ad === 'a') ? 'd' : 'a';
$distLabel = 'Distance ' . ($ad === 'a' ? '▼' : '▲');

/* Derived convenience for client */
$quickFilters = isset($config['quick_filters']) ? $config['quick_filters'] : [];
$presets      = isset($config['presets']) ? $config['presets'] : [];

/* ---- Render ---- */
$header = new Header();
$UPPER  = strtoupper($dataTable);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Data Point — <?php echo htmlspecialchars($UPPER); ?></title>
    <?php
    if (method_exists($header, 'displayCss')) { $header->displayCss(); }
    else { echo '<link rel="stylesheet" href="/style/style.css">'; }
    ?>
    <style>
    :root{
      --dp-accent:#2ca0c9;
      --dp-accent-press:#1f87a9;
      --dp-ink:#cfe8f1;
      --dp-ink-dim:#9fb8c3;
      --dp-border:#2a3742;
    }

    /* Title pill bar (classic) */
    .dp-title-pill{
      display:inline-block; padding:8px 14px; border-radius:6px;
      background:var(--dp-accent); color:#fff; font-weight:600; letter-spacing:.5px;
      text-transform:uppercase; box-shadow:0 1px 0 rgba(0,0,0,.4);
    }
    .dp-bar{ display:flex; align-items:center; justify-content:center; gap:8px; margin:10px 0 6px; }
    .dp-gear{ cursor:pointer; opacity:.8; user-select:none; }
    .dp-gear:hover{ opacity:1; }
    .dp-switch{ display:none; margin:0 8px 8px; text-align:center; }

    .dp-controls{
      display:flex; justify-content:space-between; align-items:center;
      margin:8px 0 10px; gap:8px; flex-wrap:wrap;
    }
    .dp-controls .left, .dp-controls .right{ display:flex; align-items:center; gap:8px; }
    .dp-button{
      appearance:none; background:var(--dp-accent); color:#fff; border:0;
      border-radius:6px; padding:8px 14px; font-weight:600; letter-spacing:.25px;
      box-shadow:0 1px 0 rgba(0,0,0,.4); cursor:pointer; text-transform:uppercase; font-size:12.5px;
    }
    .dp-button:hover{ filter:brightness(1.08); }
    .dp-button:active{ transform:translateY(1px); background:#1f87a9; }

    /* ---------- DataPoint scoped styles ---------- */
    .dp-mte{ color:var(--dp-ink); }
    .dp-mte select,
    .dp-mte input[type="text"],
    .dp-mte input[type="search"]{
      background:#0f1419; color:var(--dp-ink);
      border:1px solid var(--dp-border); border-radius:6px; padding:7px 10px; outline:none;
    }
    .dp-mte select:focus,
    .dp-mte input[type="text"]:focus,
    .dp-mte input[type="search"]:focus{
      border-color:var(--dp-accent); box-shadow:0 0 0 2px rgba(44,160,201,.2);
    }
    .dp-mte button,
    .dp-mte input[type="submit"],
    .dp-mte input[type="button"]{
      appearance:none; background:var(--dp-accent);
      color:#fff; border:0; border-radius:6px; padding:8px 14px;
      font-weight:600; letter-spacing:.25px; box-shadow:0 1px 0 rgba(0,0,0,.4);
      cursor:pointer; text-transform:uppercase; font-size:12.5px;
    }
    .dp-mte [disabled]{ opacity:.55; cursor:not-allowed; }

    /* Ghost / secondary buttons */
    .dp-mte input[type="submit"][value*="Reset" i],
    .dp-mte input[type="submit"][value*="Cancel" i]{
      background:transparent; color:var(--dp-ink);
      border:1px solid var(--dp-border); text-transform:uppercase;
    }

    /* Tables */
    .dp-mte table{ width:100%; border-collapse:collapse; }
    .dp-mte thead th{
      background:#0b1015; color:#fff; font-weight:700; text-transform:capitalize;
      border-bottom:2px solid var(--dp-border); padding:9px 8px;
    }
    .dp-mte tbody td{ border-top:1px solid var(--dp-border); padding:9px 8px; }
    .dp-mte tbody tr:hover{ background:#111820; }

    /* Pagination variants */
    .dp-mte .mte_navigation, .dp-mte .mte_pages, .dp-mte .pages{
      display:flex; align-items:center; gap:6px; margin:8px 0 12px;
    }
    .dp-mte .mte_navigation a, .dp-mte .mte_pages a, .dp-mte .pages a{
      display:inline-block; padding:4px 9px; border-radius:5px;
      background:#0f1419; color:#9fb8c3; text-decoration:none;
      border:1px solid var(--dp-border);
    }
    .dp-mte .mte_navigation a:hover, .dp-mte .mte_pages a:hover, .dp-mte .pages a:hover{
      color:#fff; border-color:var(--dp-accent);
    }
    .dp-mte .mte_navigation strong, .dp-mte .mte_pages strong, .dp-mte .pages strong{
      display:inline-block; padding:4px 9px; border-radius:5px;
      background:#2ca0c9; color:#fff; border:1px solid transparent;
    }

    /* ul.pagination + .mte_nav/mtelink + prev/next */
    .dp-mte ul.pagination{
      display:flex; align-items:center; justify-content:center;
      gap:6px; margin:8px 0 12px; padding:0; list-style:none;
    }

    /* Boolean badges */
    .dp-bool { display:inline-block; padding:2px 7px; border-radius:10px; font-size:12px; font-weight:700; }
    .dp-bool.yes { background:#1b6f1b; color:#fff; }
    .dp-bool.no  { background:#6f1b1b; color:#fff; }

    /* Quick filter chips */
    .dp-chips{ display:flex; flex-wrap:wrap; gap:6px; margin:8px 0 0; }
    .dp-chip{
      display:inline-block; background:#0f1419; color:#cfe8f1;
      border:1px solid var(--dp-border); border-radius:999px;
      padding:6px 10px; cursor:pointer; user-select:none; font-size:12px;
    }
    .dp-chip:hover{ border-color:var(--dp-accent); color:#fff; }
    .dp-chip b{ font-weight:700; }

    /* Preset selector */
    .dp-preset{ display:flex; align-items:center; gap:6px; }
    .dp-hidden{ display:none !important; }
    </style>
    <script>
    // Client state
    window.DP_STATE = {
      table: <?php echo json_encode($dataTable); ?>,
      fields: <?php echo json_encode(array_values($fieldsInListView)); ?>,
      labels: <?php echo json_encode($showText); ?>,
      colIndex: <?php echo json_encode($colIndex); ?>,
      format: <?php echo json_encode(isset($config['format']) ? $config['format'] : []); ?>,
      quickFilters: <?php echo json_encode($quickFilters); ?>,
      presets: <?php echo json_encode($presets); ?>,
      rows: <?php echo (int)$rows; ?>
    };

    // Utilities
    function dp_applyBooleanBadges(listTable, state){
      var fmt = state.format || {};
      var boolCols = [];
      for (var key in fmt) {
        if (fmt[key] === 'bool' && state.colIndex.hasOwnProperty(key)) {
          boolCols.push(state.colIndex[key]);
        }
      }
      if (!boolCols.length) return;
      var rows = listTable.querySelectorAll('tbody tr');
      rows.forEach(function(r){
        var cells = r.children;
        boolCols.forEach(function(idx){
          var c = cells[idx];
          if (!c) return;
          var raw = (c.textContent || '').trim();
          if (raw === '') return;
          var yes = /^(1|true|yes)$/i.test(raw);
          var no  = /^(0|false|no)$/i.test(raw);
          if (yes || no) {
            c.innerHTML = '<span class="dp-bool '+(yes?'yes':'no')+'">'+(yes?'Yes':'No')+'</span>';
          }
        });
      });
    }

    function dp_findListTable(state){
      var tables = document.querySelectorAll('.dp-mte table');
      var listTable = null;
      tables.forEach(function(t){
        var ths = t.querySelectorAll('thead th');
        if (!listTable && ths && ths.length === state.fields.length) { listTable = t; }
      });
      return listTable;
    }

    function dp_applyPreset(state, presetName){
      var listTable = dp_findListTable(state);
      if (!listTable) return;
      var showCols = null;
      if (presetName && state.presets && state.presets[presetName]) {
        showCols = state.presets[presetName];
      }
      // Default: show all fields
      var allow = {};
      if (showCols && showCols.length){
        showCols.forEach(function(f){ if(state.colIndex.hasOwnProperty(f)) allow[state.colIndex[f]] = true; });
      } else {
        state.fields.forEach(function(f, i){ allow[i] = true; });
      }
      // th
      listTable.querySelectorAll('thead th').forEach(function(th, idx){
        th.classList.toggle('dp-hidden', !allow[idx]);
      });
      // tds
      listTable.querySelectorAll('tbody tr').forEach(function(tr){
        Array.prototype.forEach.call(tr.children, function(td, idx){
          td.classList.toggle('dp-hidden', !allow[idx]);
        });
      });
      try{ localStorage.setItem('dp_preset_'+state.table, presetName || ''); }catch(e){}
    }

    function dp_renderQuickFilters(state){
      var wrap = document.getElementById('dp-quickfilters');
      if (!wrap) return;
      if (!state.quickFilters || !state.quickFilters.length) { wrap.style.display='none'; return; }

      var frag = document.createDocumentFragment();
      state.quickFilters.forEach(function(group){
        var items = group.items || [];
        if (!items.length) return;
        var row = document.createElement('div');
        row.className = 'dp-chips';
        if (group.label){
          var title = document.createElement('div');
          title.textContent = group.label + ':';
          title.style.minWidth = '80px';
          title.style.opacity = '0.8';
          row.appendChild(title);
        }
        items.forEach(function(it){
          var chip = document.createElement('span');
          chip.className = 'dp-chip';
          chip.innerHTML = (it.icon ? '<b>'+it.icon+'</b> ' : '') + (it.label || it.value || it.field);
          chip.addEventListener('click', function(){
            dp_applyQuickFilter(state, it);
          });
          row.appendChild(chip);
        });
        frag.appendChild(row);
      });
      wrap.appendChild(frag);
    }

    function dp_applyQuickFilter(state, item){
      // Try to fill the vendor's search form and submit it
      var form = document.getElementById('search_form') || document.querySelector('.dp-mte form[action*="search"]') || document.querySelector('.dp-mte form');
      if (!form){ location.reload(); return; }

      // Find a field selector (first <select>) and a text box (first text/search)
      var fieldSel = form.querySelector('select');
      var txt = form.querySelector('input[type="text"], input[type="search"]');

      function setFieldSelect(sel, field){
        if (!sel) return;
        var want = String(field).toLowerCase();
        var label = (state.labels && state.labels[field]) ? String(state.labels[field]).toLowerCase() : null;
        var matched = false;
        Array.prototype.forEach.call(sel.options, function(opt){
          var ov = String(opt.value || '').toLowerCase();
          var ot = String(opt.text || '').toLowerCase();
          if (ov === want || ot === want || (label && ot === label)) {
            opt.selected = true; matched = true;
          }
        });
        if (!matched && sel.options.length){ sel.selectedIndex = 0; }
      }

      if (item.field) setFieldSelect(fieldSel, item.field);
      if (txt && (item.value !== undefined && item.value !== null)){
        txt.value = String(item.value);
      }
      // Operator hook if vendor exposes one (commonly a second select)
      var opSel = form.querySelectorAll('select')[1];
      if (opSel && item.op){
        Array.prototype.forEach.call(opSel.options, function(opt){
          var ov = String(opt.value || '').toLowerCase();
          var ot = String(opt.text || '').toLowerCase();
          if (ov === String(item.op).toLowerCase() || ot === String(item.op).toLowerCase()){
            opt.selected = true;
          }
        });
      }
      try { form.submit(); } catch(e){ location.reload(); }
    }

    function dp_upgradeBooleanInputs(state){
      // Transform text/select inputs for bool columns into checkboxes
      var boolFields = [];
      for (var k in (state.format||{})) if (state.format[k]==='bool') boolFields.push(k);
      if (!boolFields.length) return;

      function upgrade(form){
        boolFields.forEach(function(name){
          var sel = [
            'input[name="'+name+'"]',
            'select[name="'+name+'"]',
            'input[name$="['+name+']"]',
            'select[name$="['+name+']"]'
          ].join(',');
          var el = form.querySelector(sel);
          if (!el) return;

          var current = (el.value || '').trim();
          var yes = /^(1|true|yes)$/i.test(current);

          // Hidden real field to submit 0/1
          var hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = el.name;
          hidden.value = yes ? '1' : '0';

          // Checkbox UI
          var cb = document.createElement('input');
          cb.type = 'checkbox';
          cb.checked = yes;
          cb.addEventListener('change', function(){
            hidden.value = cb.checked ? '1' : '0';
          });

          // Label
          var lbl = document.createElement('label');
          lbl.style.marginLeft = '6px';
          lbl.textContent = 'Yes/No';

          // Insert and disable original
          el.parentNode.insertBefore(cb, el);
          el.parentNode.insertBefore(lbl, el.nextSibling);
          el.parentNode.insertBefore(hidden, el);
          el.disabled = true;
          el.classList.add('dp-hidden');
        });
      }

      // Upgrade existing forms
      document.querySelectorAll('.dp-mte form').forEach(upgrade);

      // Watch for dynamically inserted edit forms
      var obs = new MutationObserver(function(muts){
        muts.forEach(function(m){
          Array.prototype.forEach.call(m.addedNodes, function(n){
            if (n.nodeType===1){
              if (n.tagName==='FORM') upgrade(n);
              n.querySelectorAll && n.querySelectorAll('form').forEach(upgrade);
            }
          });
        });
      });
      obs.observe(document.querySelector('.dp-mte') || document.body, {childList:true, subtree:true});
    }

    document.addEventListener('DOMContentLoaded', function(){
      var st = window.DP_STATE;
      if (!st) return;

      // Apply saved preset (if any)
      try{
        var saved = localStorage.getItem('dp_preset_'+st.table);
        if (saved) dp_applyPreset(st, saved);
      }catch(e){}

      // Render quick filters
      dp_renderQuickFilters(st);

      // Boolean badges in cells
      var tbl = dp_findListTable(st);
      if (tbl) dp_applyBooleanBadges(tbl, st);

      // Upgrade form inputs for booleans
      dp_upgradeBooleanInputs(st);
    });
    </script>
</head>
<body>
<?php if (method_exists($header, 'displayHeader')) { $header->displayHeader('Data Point'); } ?>

<div class="container" style="padding: 10px 15px;">
    <!-- Classic title pill -->
    <div class="dp-bar">
        <span class="dp-title-pill"><?php echo htmlspecialchars($UPPER); ?></span>
        <span class="dp-gear" onclick="(function(){var s=document.getElementById('dp-switch'); if(!s)return; s.style.display = (s.style.display==='block'?'none':'block');})();">⚙</span>
    </div>

    <!-- Optional table switcher (hidden by default—click gear) -->
    <form id="dp-switch" class="dp-switch" method="get" action="/DataPoint/">
        <label for="table" style="margin-right:6px;">Table:</label>
        <select name="table" id="table" onchange="this.form.submit()">
            <?php foreach ($allowedTables as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>"<?php if ($t === $dataTable) echo ' selected'; ?>>
                    <?php echo function_exists('datapoint_table_title') ? htmlspecialchars(datapoint_table_title($t)) : htmlspecialchars($t); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="dp-button">Open</button></noscript>
    </form>

    <!-- Top controls: Rows-per-page + Export + Distance sort + Preset -->
    <div class="dp-controls">
        <div class="left">
            <form method="get" action="/DataPoint/">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($dataTable); ?>">
                <label for="rows">Rows:</label>
                <select id="rows" name="rows" onchange="this.form.submit()">
                    <?php foreach ([10,25,50,100] as $opt): ?>
                        <option value="<?php echo $opt; ?>"<?php if ($rows === $opt) echo ' selected'; ?>>
                            <?php echo $opt; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="dp-button" type="submit">Apply</button></noscript>
            </form>
            <?php if ($canDistance): ?>
                <a class="dp-button" href="/DataPoint/?table=<?php echo urlencode($dataTable); ?>&rows=<?php echo (int)$rows; ?>&sort=distance&ad=<?php echo htmlspecialchars($nextAd); ?>">
                    <?php echo htmlspecialchars($distLabel); ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($presets)): ?>
            <div class="dp-preset">
                <label for="dp-preset">Preset:</label>
                <select id="dp-preset" onchange="dp_applyPreset(window.DP_STATE, this.value)">
                    <option value="">All Columns</option>
                    <?php foreach ($presets as $pname => $plist): ?>
                        <option value="<?php echo htmlspecialchars($pname); ?>"><?php echo htmlspecialchars($pname); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <script>
              (function(){
                try{
                  var st=window.DP_STATE, sel=document.getElementById('dp-preset');
                  var saved=localStorage.getItem('dp_preset_'+st.table)||'';
                  if (saved && sel.querySelector('option[value="'+saved+'"]')) sel.value = saved;
                }catch(e){}
              })();
            </script>
            <?php endif; ?>
        </div>
        <div class="right">
            <a class="dp-button" href="/DataPoint/export.php?table=<?php echo urlencode($dataTable); ?>">Export CSV</a>
        </div>
    </div>

    <!-- Quick Filters -->
    <div id="dp-quickfilters"></div>

    <div id="data-view" class="dp-mte">
        <?php $tabledit->do_it(); ?>
    </div>
</div>

</body>
</html>
