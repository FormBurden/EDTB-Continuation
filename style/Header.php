<?php
// Resolve repo root once and load core functions/$mysqli
if (!isset($ROOT)) {
    $ROOT = realpath(__DIR__ . '/..');
}
require_once $ROOT . '/source/functions.php';

/** @require config */
require_once __DIR__ . '/../source/config.inc.php';
/** @require functions */
require_once __DIR__ . '/../source/functions.php';
/** @require curSys */
require_once __DIR__ . '/../source/MySQL.php';
require_once __DIR__ . '/Theme.php';

require_once __DIR__ . '/../source/curSys.php';

use \EDTB\style\Theme;

/**
 * Header
 *
 * @author Mauri Kujala <contact@edtb.xyz>
 */
class Header extends Theme
{
    /** @var string $pageTitle */
    public $pageTitle = '';

    /**
     * Display the header
     */
        /**
     * Display the header
     */
    public function displayHeader()
    {
        global $settings;
        $minm = '';
        include __DIR__ . '/header/head.php';
        include __DIR__ . '/header/body_open.php';
        include __DIR__ . '/header/left_panel.php';
        $this->topPanel();
        $this->about();
        echo '<div class="rightpanel">';

    }


        /**
     * Main navigation links
     */
    private function navLinks()
    {
        include __DIR__ . '/header/nav_links.php';
    }



        /**
     * Top panel with CMDR bar, search, and session log area
     */
    private function topPanel()
    {
        include __DIR__ . '/header/top_panel.php';
    }

        /**
     * about ED ToolBox
     */
    private function about()
    {
        include __DIR__ . '/header/about.php';
    }

}
