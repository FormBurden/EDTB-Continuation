<?php require_once __DIR__ . '/../Theme.php'; ?>
<?php
/** left_panel.php — left side container + nav links */
$panelClass = 'leftpanel';
if (class_exists('Theme') && method_exists('Theme', 'sidebarStyle')) {
    $panelClass .= ' ' . Theme::sidebarStyle();
}
?>
<div class="<?= htmlspecialchars($panelClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php
    // The nav list itself lives here:
    //   style/header/nav_links.php  (already in your bundle)
    include __DIR__ . '/nav_links.php';
    ?>
</div>

