

<?php global $settings; ?>
<?= /** BEGIN about.php (moved from Header::about) */ '' ?>

        <div class="settings_panel" id="about">
            <table class="table">
                <tr>
                    <td class="dark">Version</td>
                    <td class="light"><?= $settings['edtb_version']?></td>
                    <td class="black">
                        <a target="_blank" href="https://github.com/FormBurden/EDTB-Continuation" title="View on GitHub">
                            <img class="ext_icon" src="/style/img/external_link.png" alt="ext">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="dark">Github</td>
                    <td class="light">EDTB Continuation (Linux)</td>
                    <td class="black">
                        <a target="_blank" href="https://github.com/FormBurden/EDTB-Continuation/tree/feat/native-linux-overhaul" title="View on GitHub">
                            <img class="ext_icon" src="/style/img/external_link.png" alt="ext">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="dark">Original Author</td>
                    <td class="light">Mauri Kujala</td>
                    <td class="black">
                        <a target="_blank" href="https://github.com/DBnR1/EDTB" title="View Original Repo">
                            <img class="ext_icon" src="/style/img/external_link.png" alt="ext">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="info_td" colspan="3">
                        ED ToolBox was created using assets and imagery from the game Elite: Dangerous, with the permission of Frontier Developments plc,<br>
                        for non-commercial purposes. It is not endorsed by nor reflects the views or opinions of Frontier Developments and no<br>
                        employee of Frontier Developments was involved in the making of it.
                    </td>
                </tr>
            </table>
        </div>
<?= /** END about.php */ '' ?>
