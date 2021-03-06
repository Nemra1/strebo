<?php

namespace Strebo\SocialNetworks;

use Strebo;
use PicoFeed\Reader\Reader;

class BingNews extends Strebo\AbstractSocialNetwork implements Strebo\PublicInterface
{
    private $reader;

    public function __construct()
    {
        parent::__construct(
            'Bing News',
            'Bing_logo_2016',
            '#008273',
            ["DE" => "de", "US" => "us", "W" => "us"],
            null,
            null,
            null
        );
        $this->reader = new Reader;
    }

    public function search($tag)
    {
        try {
            return $this->encode($this->reader->download('https://www.bing.com/news?q=' . $tag . '&format=RSS'));
        } catch (Exception $e) {
            print_r($e->getMessage());
            return null;
        }
    }

    public function getPublicFeed($location)
    {
        try {
            return $this->encode($this->reader->download('https://www.bing.com/news?cc=' . $location . '&format=RSS'));
        } catch (Exception $e) {
            print_r($e->getMessage());
            return null;
        }
    }

    public function encode($resource)
    {
        $parser = $this->reader->getParser(
            $resource->getUrl(),
            $resource->getContent(),
            $resource->getEncoding()
        );

        $data = $parser->execute();
        $feed = [];
        if (isset($data->items) && isset($data->items[0]) && $data->items[0]->hasNamespace('News')) {
            foreach ($data->items as $value) {
                $item = [];
                $item['type'] = 'image';
                $item['media'] = $value->getTag('News:Image')[0];
                $item['author'] = $value->getTag('News:Source')[0];
                $item['title'] = $value->getTag('title')[0];
                $item['text'] = $value->getTag('description')[0];
                $item['createdTime'] = parent::formatTime(strtotime($value->getTag('pubDate')[0]));
                $item['link'] = $value->getTag('link')[0];
                $feed[] = $item;
            }
        }
        return json_encode(array('name' => parent::getName(),
            'icon' => parent::getIcon(),
            'color' => parent::getColor(),
            'customIcon' => true,
            'feed' => $feed));
    }
}
