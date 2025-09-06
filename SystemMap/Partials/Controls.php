<div class="top">
    <span class="right" id="value" style="display: none">
        Approximate value:
        <span class="text" id="minval"></span><span id="minvaln" style="display: none"></span>
        <span class="text" id="dash"></span>
        <span class="text" id="maxval"></span><span id="maxvaln" style="display: none"></span>&nbsp;
        ( Very experimental )
    </span>
    <div class="sm_system">System map for: <?= $system . $linkMap?></div>
    <div id="smsys" style="display: none"><?= $system?></div>
    <span class="text" style="margin-right: 20px">Add bodies :</span>
    <?php
    $types = ['star', 'planet', 'other'];

    foreach ($types as $type) {
        $border = $type === 'other' ? ' style="border-right:1px solid #333"' : '';
        ?>
        <div class="categories" id="<?= $type?>_click"<?= $border?>>
            <?= $type?>
        </div>
        <div class="stars_planets" id="<?= $type?>" style="display: none">
        <?php
        $jsonFile = 'bodies.json';
        $jsonString = file_get_contents($jsonFile);

        $jsonArr = json_decode($jsonString, true);

        $i = 0;
        $lastImg = '';

        $lastName = '';
        foreach ($jsonArr as $arr) {
            $type2 = $arr['type'];
            if ($type2 === $type) {
                $img = str_replace([
                    ' ',
                    ','
                ], [
                    '_',
                    ''
                ], $arr['name']);
                $img = strtolower($img);

                $imgfiles = glob($_SERVER['DOCUMENT_ROOT'] . '/SystemMap/bodies/' . $img . '_*');

                $name = $arr['name'];
                $id = $arr['id'];
                $width = $arr['width'];
                $minValue = $arr['min_value'];
                $maxValue = $arr['max_value'];

                if ($name != $lastName) {
                    echo '<div class="cat_name">' . $name;
                }

                $ii = 0;
                foreach ($imgfiles as $imgfile) {
                    $src = str_replace($_SERVER['DOCUMENT_ROOT'], '', $imgfile);
                    $imgid = $imgfile[strlen($imgfile) - 5];

                    $bid = $i . '_' . $imgid;

                    ?>
                    <script>
                        var options<?= $id . $imgid?> = [];
                        options<?= $id . $imgid?>["id"] = "<?= $id?>";
                        options<?= $id . $imgid?>["type"] = "<?= $type?>";
                        options<?= $id . $imgid?>["name"] = "<?= $name?>";
                        options<?= $id . $imgid?>["src"] = "<?= $src?>";
                        options<?= $id . $imgid?>["imgid"] = "<?= $imgid?>";
                        options<?= $id . $imgid?>["width"] = "<?= $width?>";
                        options<?= $id . $imgid?>["min_value"] = "<?= $minValue?>";
                        options<?= $id . $imgid?>["max_value"] = "<?= $maxValue?>";
                        options<?= $id . $imgid?>["bid"] = "<?= $bid?>";
                        options<?= $id . $imgid?>["bodyid"] = "<?= $id?>";
                        options<?= $id . $imgid?>["landable"] = 0;
                        options<?= $id . $imgid?>["ringed"] = 0;
                        options<?= $id . $imgid?>["scanned"] = 1;
                        options<?= $id . $imgid?>["firstdisc"] = 0;
                        options<?= $id . $imgid?>["do_update"] = true;
                        options<?= $id . $imgid?>["pos_top"] = false;
                        options<?= $id . $imgid?>["pos_left"] = false;
                        options<?= $id . $imgid?>["source"] = "php";
                    </script>
                    <div class="add" onclick="add_body(options<?= $id . $imgid?>)">
                        <img class="add_img_<?= $type?>" src="<?= $src?>" alt="<?= $name?>">
                    </div>
                    <?php
                    $ii++;
                }

                if ($img != $lastImg) {
                    echo '</div>';
                }

                $lastName = $name;
                $i++;
            }
        }
        ?>
        </div>
        <?php
    }
    ?>
    <span class="text" style="margin-left: 40px; margin-right: 20px">Controls :</span>
    <div class="categories" id="toggle_grid">Toggle grid</div>
    <div class="categories" id="toggle_names" style="width: 77px">Hide names</div>
    <div class="categories" id="toggle_background">Toggle background</div>
</div>
