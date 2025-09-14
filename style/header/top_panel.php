<?= /** BEGIN top_panel.php (moved from Header::topPanel) */ '' ?>
<?php
global $settings, $api;
?>
<style>
  /* Make this strip taller and position children */
  .rightpanel-top { padding-top: 6px; }
  /* Enlarge emblem and drop it down slightly */
  #elite-emb { width: 56px; height: 56px; display: block; margin-top: 6px; }
  /* Place the Journal badge to the right of the emblem (green area) */
  #journal-badge {
    position: absolute;
    left: 88px;           /* emblem (56) + ~16px gutter */
    top: 18px;            /* aligns visually with emblem centerline */
    font-size: 14px;
    line-height: 1.2;
    color: #ddd;
    white-space: nowrap;
    pointer-events: none; /* don’t steal clicks from header icons */
  }
</style>

        <div class="rightpanel-top">
            <!-- elite emblem and add logs -->
            <a href="javascript:void(0)" id="toggle" title="Add log entry">
            <img id="elite-emb" src="/style/img/elite.png" alt="Add log" class="elite_emb">
            </a>

            <!-- page title and search systems & stations -->
                        <!-- page title -->
            <div class="edtb-page-title" id="pageTitle">
                <?= htmlspecialchars($this->pageTitle, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div id="journal-badge" class="journal-badge" title="Last Journal Sync">Journal: …</div>

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
