<?php
/**
 * GalnetProvider
 * Small helper to fetch the latest GalNet items from Frontier's CMS (JSON:API).
 * Extracted from GalNet/index.php to reduce file size and separate responsibilities.
 */
class GalnetProvider
{
    /** @var string */
    private $feedUrl;

    /**
     * @param string|null $feedUrl Optional override; if null uses GALNET_FEED or default.
     */
    public function __construct(?string $feedUrl = null)
    {
        $this->feedUrl = $feedUrl ?: (
            defined('GALNET_FEED')
                ? GALNET_FEED
                : 'https://cms.zaonce.net/en-GB/jsonapi/node/galnet_article?sort=-published_at&page[offset]=0&page[limit]=12'
        );
    }

    /**
     * Fetch latest items
     *
     * @param int $limit
     * @return array<int,array{title:string,link:string,pubDate:string,content:string}>
     */
    public function fetchLatest(int $limit = 12): array
    {
        $ch = curl_init($this->feedUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EDTB-Continuation/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.api+json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $items = [];

        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $json = json_decode($response, true);
            if (isset($json['data']) && is_array($json['data'])) {
                foreach ($json['data'] as $entry) {
                    $attr = (isset($entry['attributes']) && is_array($entry['attributes'])) ? $entry['attributes'] : [];

                    $title = isset($attr['title']) ? $attr['title'] : '';
                    $pub   = isset($attr['published_at']) ? $attr['published_at'] : '';

                    // Prefer processed HTML, else raw value
                    $body  = '';
                    if (isset($attr['body']) && is_array($attr['body'])) {
                        $body = isset($attr['body']['processed'])
                            ? $attr['body']['processed']
                            : (isset($attr['body']['value']) ? $attr['body']['value'] : '');
                    } elseif (isset($attr['body'])) {
                        $body = $attr['body'];
                    }

                    // Link: fall back to Galnet hub if alias missing
                    $link = 'https://www.elitedangerous.com/news/galnet';
                    if (isset($attr['path']) && is_array($attr['path']) && !empty($attr['path']['alias'])) {
                        $link = 'https://www.elitedangerous.com' . $attr['path']['alias'];
                    }

                    $items[] = [
                        'title'   => $title,
                        'link'    => $link,
                        'pubDate' => $pub,
                        'content' => $body,
                    ];

                    if (count($items) >= $limit) {
                        break;
                    }
                }
            }
        }

        return $items;
    }
}
