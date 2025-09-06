<?php
/** body_open.php — open <body> and main wrappers */
$sidebarClass = 'normal';
if (class_exists('Theme') && method_exists('Theme', 'sidebarStyle')) {
    $sidebarClass = Theme::sidebarStyle();
}
?>
<body class="sidebar-<?= htmlspecialchars($sidebarClass, ENT_QUOTES, 'UTF-8') ?>">
<div id="wrap">
