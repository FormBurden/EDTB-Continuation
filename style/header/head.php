<?php require_once __DIR__ . '/../Theme.php'; ?>
<?= /** BEGIN head.php (moved from the top of Header::displayHeader) */ '' ?>

        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <!-- icon, styles and custom fonts -->
            <link type="image/png" href="/style/img/icon.png" rel="icon" />
            <link type="text/css" href="/style/style.css?ver=<?= $settings['edtb_version']?>" rel="stylesheet" />

            <?php
            if (\EDTB\style\Theme::sidebarStyle() === 'narrow') {
                ?>
                <link type="text/css" href="/style/style_narrow.css?ver=<?= $settings['edtb_version']?>" rel="stylesheet" />
                <?php
            }
            ?>

            <!-- jquery -->
            <script src="/source/Vendor/jquery-2.2.0.min.js"></script>
            <!-- wiselinks -->
            <script src="/source/Vendor/wiselinks-1.2.2.min.js"></script>
            <!-- clipboard -->
            <script src="/source/Vendor/clipboard.min.js"></script>
            <!-- audio recorder -->
            <script src="/source/Vendor/Recordmp3js/recordmp3.js"></script>
            <script src="/source/Vendor/adamwdraper-Numeral-js-7487acb/numeral.js"></script>

            <!-- markitup -->
            <script src="/source/Vendor/markitup/jquery.markitup.js"></script>
            <script src="/source/Vendor/markitup/sets/default/set.js"></script>
            <link rel="stylesheet" type="text/css" href="/source/Vendor/markitup/skins/simple/style.css" />
            <link rel="stylesheet" type="text/css" href="/source/Vendor/markitup/sets/default/style.css" />

            <!-- own js -->
            <script src="/source/javascript.js"></script>
          <!-- global variable for clock -->
            <script>
                var gmt = "<?= $settings['game_time']?>";
            </script>

            <title>CMDR <?= $settings['cmdr_name']?>'s ToolBox</title>
        </head>
<?= /** END head.php */ '' ?>
