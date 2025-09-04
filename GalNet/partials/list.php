<?php
/**
 * List renderer for GalNet items.
 * Expects:
 *   - $feed: array<int,array{title:string,link:string,pubDate:string,content:string}>
 *   - $settings['galnet_excludes']: array<string>
 */
$i = 0;
foreach ($feed as $data) {
    $title = $data['title'];
    $link  = $data['link'];
    $text  = $data['content'];

    // Excludes (keep behavior identical to original)
    $continue = true;
    foreach ($settings['galnet_excludes'] as $exclude) {
        $find = $exclude;
        $pos  = strpos($title, $find);
        if ($pos !== false) {
            $continue = false;
            break 1;
        }
    }

    if ($continue !== false) {
        ?>
        <h3>
            <a href="javascript:void(0)" onclick="$('#<?= $i ?>').fadeToggle()">
                <img class="icon" src="/style/img/plus.png" alt="expand" style="padding-bottom: 3px"/><?= $title ?>
            </a>
        </h3>
        <div id="<?= $i ?>" style="display: none; padding-left:22px;max-width: 800px">
            <?= $text ?>
            <br><br>
            <span style="margin-bottom: 15px">
                <a href="<?= $link ?>" target="_blank">
                    Read on news.galnet.fr
                </a><img class="ext_icon" src="/style/img/external_link.png" style="margin-bottom: 3px" alt="ext"/>
            </span>
        </div>
        <?php
        $i++;
    }
}
