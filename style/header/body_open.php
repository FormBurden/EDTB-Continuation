<?php
/** body_open.php — open <body> and main wrappers */
$sidebarClass = 'normal';
if (class_exists('Theme') && method_exists('Theme', 'sidebarStyle')) {
    $sidebarClass = Theme::sidebarStyle();
}
?>
<?php require_once __DIR__ . '/../../source/Journal/Parser.php'; \EDTB\Journal\Parser::ingest(); ?>
<body class="sidebar-<?= htmlspecialchars($sidebarClass, ENT_QUOTES, 'UTF-8') ?>">
<div id="wrap">
