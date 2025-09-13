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
                        <!-- page title -->
            <div class="edtb-page-title" id="pageTitle">
                <?= htmlspecialchars($this->pageTitle, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <!-- commander name -->
            <div class="rightpanel-cmdr">
                CMDR <?= htmlspecialchars($settings['edtb_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
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
        </div>
<?= /** END top_panel.php */ '' ?>
