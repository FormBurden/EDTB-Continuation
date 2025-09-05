<?php require_once __DIR__ . '/../Theme.php'; ?>
<?= /** BEGIN left_panel.php (moved from Header::displayHeader) */ '' ?>

        <div class="leftpanel">
            <div class="leftpanel-top">
                <!-- current system name will be rendered here -->
                <div class="leftpanel-title" id="t1"><?= htmlspecialchars($curSys['name'] ?? '') ?></div>
                <!-- date and clock will be rendered here -->
                <div id="datetime">
                    <?php
                    if (\EDTB\style\Theme::sidebarStyle() !== 'narrow') {
                        ?>
                        <div class="leftpanel-date" id="date"></div>
                        <div class="leftpanel-clock" id="hrs"></div>
                        <?php
                    } else {
                        ?>
                        <div class="leftpanel-clock" id="hrsns"></div>
                        <?php
                    }
                    ?>
                </div>
                <!-- links to external resources -->
                <div id="ext_links" class="leftpanel-ext_links">
                    <?php
                    /**
                     * External links
                     */
                    if (!isset($settings['ext_links']) || !is_iterable($settings['ext_links'])) {
                        $settings['ext_links'] = [];
                    }

                    foreach ($settings['ext_links'] as $name => $linkHref) {
                        echo '<a href="' .  $linkHref . '" target="_blank" onclick="$(\'#ext_links\').fadeToggle(\'fast\')">';
                        echo '<div class="leftpanel-ext_links_link">' . $name . '</div>';
                        echo '</a>';
                    }
                    ?>
                </div>
            </div>
            <div class="leftpanel-systeminfo">
                <!-- system info will be rendered here -->
                <!-- <div id="systeminfo" onclick="update_values('/get/getSystemEditData.php');tofront('editsystem')"></div> -->
                <div id="systeminfo"></div>
            </div>
            <!-- stations for the current system will be rendered here -->
            <div class="leftpanel-stations" id="stations"></div>

            <!-- navigation links -->
            <div class="leftpanel-links">
                <div class="links">
                    <?php
                    /**
                     * set main navigation links
                     */
                    $this->navLinks();
                    ?>
                </div>
            </div>
            <?php
            /**
             *  minimize or maximize left panel
             */
            if (\EDTB\style\Theme::sidebarStyle() === 'narrow') {
                $minm .= '<a href="javascript:void(0)" onclick="minmax(\'normal\')" title="Maximize left panel">';
                $minm .= '<img class="minmax" src="/style/img/minmax.png" alt="Max">';
                $minm .= '</a>';
            } else {
                $minm .= '<a href="javascript:void(0)" onclick="minmax(\'narrow\')" title="Minimize left panel">';
                $minm .= '<img class="minmax" src="/style/img/minmax.png" alt="Min">';
                $minm .= '</a>';
            }
            ?>

            <div class="leftpanel-bottom">
                <div class="links">
                    <a href="/EDToolbox/?to=settings" data-push="true" title="Settings">
                        <div class="links_link">Settings</div>
                    </a>
                    <a href="javascript:void(0)" onclick="tofront('notice')" title="Notice: Read this!">
                        <div class="links_link">Notice</div>
                    </a>
                    <a href="javascript:void(0)" onclick="tofront('about')" title="About ED ToolBox">
                        <div class="links_link">About</div>
                    </a>
                    <a href="javascript:void(0)" onclick="$('#ext_links').fadeToggle('fast')" title="External links">
                        <div class="links_link">External</div>
                    </a>
                </div>
                <div class="seslog" id="seslog">
                    <?php
                    if (($settings['show_now_playing'] ?? 'false') === 'true') {
                        ?>
                        <div id="nowplaying"></div>
                        <a href="javascript:void(0)" onclick="toggleLogs('seslog')">
                            <img class="minmax" src="/style/img/minmax.png" alt="Min">
                        </a>
                        <?php
                    } else {
                        ?>
                        <span id="seslogsuccess"><?= $minm?></span>
                        <span id="old_val" style="display: none"><?= $minm?></span>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
<?= /** END left_panel.php */ '' ?>
