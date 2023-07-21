<?php

namespace App\WebPages\Rumble;

use \DOMXpath;
use \DOMDocument;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ConversionHelper as Convert;

class VideoPage
{
    protected $url;
    protected $dom = [];
    protected $apiData = [];

    public function __construct(string $url)
    {
        $doc = new DOMDocument();

        $cacheKey = 'HTML_' . md5($url);

        if (Cache::has($cacheKey)) {
            $html = Cache::get($cacheKey);

            @$doc->loadHTML($html);
        } else {
            @$doc->loadHTMLFile($url);
            $html = $doc->saveHTML();

            Cache::put($cacheKey, $html, now()->addHours(6));
        }

        $this->url = $url;
        $this->dom = [
            'doc' => $doc,
            'xpath' => new DOMXpath($doc)
        ];
        $this->apiData = $this->apiData();
    }

    public function apiData()
    {
        $xpath = $this->dom['xpath'];

        if (empty($xpath)) {
            throw new Exception('xpath is empty');
        }

        $elements = $xpath->query('//script[@type="application/ld+json"]');
        
        return ($elements->length > 0) ? json_decode($elements->item(0)->textContent, true) : null;
    }

    public function id()
    {
        $videoData = $this->apiData[0];

        if (empty($videoData)) return null;

        $id = str_replace('https://rumble.com/embed/', '', $videoData['embedUrl']);

        if (empty($id)) return null;

        return str_replace('/', '', $id);
    }

    public function url()
    {
        $videoData = $this->apiData[0];

        if (empty($videoData)) return null;

        return $videoData['url'];
    }

    public function src()
    {
        $videoData = $this->apiData[0];

        if (empty($videoData)) return null;

        return $videoData['embedUrl'];
    }

    public function name()
    {
        $videoData = $this->apiData[0];

        if (empty($videoData)) return null;

        return $videoData['name'];
    }

    public function thumbnail()
    {
        $videoData = $this->apiData[0];

        if (empty($videoData)) return null;

        return $videoData['thumbnailUrl'];
    }

    public function description()
    {
        $videoData = $this->apiData[0];

        if (empty($videoData)) return null;

        return $videoData['description'];
    }

    public function channelName()
    {
        $xpath = $this->dom['xpath'];

        if (empty($xpath)) {
            throw new Exception('xpath is empty');
        }

        $elements = $xpath->query('//div[@class="media-heading-name"]');

        return ($elements->length > 0) ? trim($elements->item(0)->textContent) : null;
    }

    public function likesCount()
    {
        $xpath = $this->dom['xpath'];

        if (empty($xpath)) {
            throw new Exception('xpath is empty');
        }

        $elements = $xpath->query('//span[@class="rumbles-up-votes"]');

        return ($elements->length > 0) ? $elements->item(0)->textContent : null;
    }

    public function dislikesCount()
    {
        $xpath = $this->dom['xpath'];

        if (empty($xpath)) {
            throw new Exception('xpath is empty');
        }

        $elements = $xpath->query('//span[@class="rumbles-down-votes"]');

        return ($elements->length > 0) ? $elements->item(0)->textContent : null;
    }

    public function commentsCount()
    {
        $xpath = $this->dom['xpath'];

        if (empty($xpath)) {
            throw new Exception('xpath is empty');
        }

        $elements = $xpath->query('//div[@class="video-counters--item video-item--comments"]');

        return ($elements->length > 0) ? trim($elements->item(0)->textContent) : null;
    }

    public function viewsCount()
    {
        $xpath = $this->dom['xpath'];

        if (empty($xpath)) {
            throw new Exception('xpath is empty');
        }

        $elements = $xpath->query('//div[@class="video-counters--item video-item--views"]');

        return ($elements->length > 0) ? trim($elements->item(0)->textContent) : null;
    }

    public function uploadedAt()
    {
        $xpath = $this->dom['xpath'];

        if (empty($xpath)) {
            throw new Exception('xpath is empty');
        }

        // Case 1: Normal video
        $elements = $xpath->query('//div[@class="media-published"]');

        if ($elements->length > 0) return $elements->item(0)->getAttribute('title');

        // Case 2: Livestream which has ended
        $elements = $xpath->query('//div[@class="streamed-on"]/time');

        if ($elements->length > 0) {
            $date = $elements->item(0)->getAttribute('datetime');

            return Convert::ISO8601ToDateString($date);
        }

        // Case 3: Livestream which has NOT ended, or other
        return null;
    }
}