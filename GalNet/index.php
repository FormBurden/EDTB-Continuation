<?php
/**
 * Galnet news
 *
 * No description
 *
 * @package EDTB\Main
 * @author Mauri Kujala <contact@edtb.xyz>
 * @copyright Copyright (C) 2016, Mauri Kujala
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 */

/*
* ED ToolBox, a companion web app for the video game Elite Dangerous
* (C) 1984 - 2016 Frontier Developments Plc.
* ED ToolBox or its creator are not affiliated with Frontier Developments Plc.
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
*/

/** @require phpfastcache */
require_once __DIR__ . '/../source/Vendor/phpfastcache/phpfastcache.php';

/** @require Theme class */
require_once __DIR__ . '/../style/Theme.php';

/**
 * initiate page header
 */
$header = new Header();

/** @var string page_title */
$header->pageTitle = 'Galnet News';

/**
 * display the header
 */
$header->displayHeader();

/**
 * get cached content
 */
$html = __c('files')->get('galnet');

if ($html === null) {
    ob_start();
    ?>
    <div class="entries">
        <div class="entries_inner">
            <h2><img class="icon24" src="/style/img/galnet.png" alt="GalNet" style="margin-right: 6px"/>Latest Galnet News</h2>
            <hr>
            <?php
            $galnetUrl = defined('GALNET_FEED')
            ? GALNET_FEED
            : 'https://cms.zaonce.net/en-GB/jsonapi/node/galnet_article?sort=-published_at&page[offset]=0&page[limit]=12';

            /** Fetch JSON from Frontier CMS (JSON:API) */
            $ch = curl_init($galnetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'EDTB-Continuation/1.0');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.api+json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $feed = [];

            if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $json = json_decode($response, true);
            if (isset($json['data']) && is_array($json['data'])) {
                foreach ($json['data'] as $entry) {
                    $attr = isset($entry['attributes']) && is_array($entry['attributes']) ? $entry['attributes'] : [];

                    $title = isset($attr['title']) ? $attr['title'] : '';
                    $pub   = isset($attr['published_at']) ? $attr['published_at'] : '';

                    // Body is a Drupal field; prefer 'processed' (HTML), else 'value'
                    $body  = '';
                    if (isset($attr['body']) && is_array($attr['body'])) {
                        $body = isset($attr['body']['processed'])
                            ? $attr['body']['processed']
                            : (isset($attr['body']['value']) ? $attr['body']['value'] : '');
                    } elseif (isset($attr['body'])) {
                        $body = $attr['body'];
                    }

                    // Link: use path.alias if provided; otherwise the Galnet hub
                    $link = 'https://www.elitedangerous.com/news/galnet';
                    if (isset($attr['path']) && is_array($attr['path']) && !empty($attr['path']['alias'])) {
                        $link = 'https://www.elitedangerous.com' . $attr['path']['alias'];
                    }

                    $feed[] = [
                        'title'   => $title,
                        'link'    => $link,
                        'pubDate' => $pub,
                        'content' => $body,
                    ];
                }
            }
            }

            

            $i = 0;
            foreach ($feed as $data) {
                $title = $data['title'];
                $link = $data['link'];
                $text = $data['content'];

                // exclude stuff
                $continue = true;

                foreach ($settings['galnet_excludes'] as $exclude) {
                    $find = $exclude;
                    $pos = strpos($title, $find);

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
            ?>
        </div>
    </div>
    <?php
    $html = ob_get_contents();
    // Save to Cache for 30 minutes
    __c('files')->set('galnet', $html, 1800);

    /**
     * initiate page footer
     */
    $footer = new Footer();

    /**
     * display the footer
     */
    $footer->displayFooter();

    exit;
}
echo $html;

/**
 * initiate page footer
 */
$footer = new Footer();

/**
 * display the footer
 */
$footer->displayFooter();
