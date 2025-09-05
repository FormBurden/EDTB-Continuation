<?= /** BEGIN top_panel.php (moved from Header::topPanel) */ '' ?>
<?php
global $settings, $api;
?>
        <div class="rightpanel-top">
            <!-- elite emblem and add logs -->
            <a href="javascript:void(0)" id="toggle" title="Add log entry">
                <img src="/style/img/elite.png" alt="Add log" class="elite_emb">
            </a>

            <!-- page title and search systems & stations -->
            <div class="rightpanel-title">
            <div id="pageTitle"><?= $this->pageTitle?></div>
                <div id="quicksearch">
                    <form id="searchform" method="get" action="/NearestSystems/">
                        <input type="text" class="textbox" id="sys_jump" name="system_name" placeholder="Find a system..." oninput="showResult(this.value, '0')">
                        <button type="submit" class="button">Go</button>
                    </form>
                </div>
            </div>
            <!-- settings & about -->
            <div class="rightpanel-icons">
                <a href="javascript:void(0)" id="settings_click" title="Settings">
                    <img src="/style/img/settings.png" alt="Settings" class="elite_emb">
                </a>
                <a href="javascript:void(0)" id="about_click" title="About">
                    <img src="/style/img/about.png" alt="About" class="elite_emb">
                </a>
            </div>

            <!-- session log -->

            <!-- session log -->
            <div id="seslog_wrap">
                <div id="seslog_notice" class="light">
                    Start logging to show your route, notes and screenshots.
                </div>
                <div id="seslog_container" class="light"></div>
            </div>
        </div>
<?= /** END top_panel.php */ '' ?>
