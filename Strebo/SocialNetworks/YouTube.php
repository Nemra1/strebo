<?php

namespace Strebo\SocialNetworks;

use Strebo;
use Symfony\Component\Config\Definition\Exception\Exception;

class YouTube extends Strebo\AbstractSocialNetwork implements Strebo\PrivateInterface, Strebo\PublicInterface
{
    private $youtube;
    private $googlePlus;
    private $client;
    private $client2;

    public function __construct()
    {
        parent::__construct(
            'YouTube',
            'youtube',
            '#e62117',
            ["DE" => 'DE', "US" => 'US', "W" => null],
            getenv('strebo_youtube_1'),
            null,
            null
        );
        $this->client = new \Google_Client();
        $this->client->setApplicationName("strebo_youtube");
        $this->client->setDeveloperKey($this->getApiKey());
        $this->youtube = new \Google_Service_YouTube($this->client);
        $this->client2 = new \Google_Client();
        $this->client2->setApplicationName("strebo_google_plus");
        $this->client2->setDeveloperKey(getenv("strebo_youtube_2"));
        $this->googlePlus = new \Google_Service_Plus($this->client2);
    }

    public function connect($code)
    {
        $oauthClient = new \Google_Client();
        $oauthClient->setApplicationName("strebo_youtube");
        $oauthClient->setDeveloperKey($this->getApiKey());
        $oauthClient->setAccessToken($code);
        $oauthYoutube = new \Google_Service_YouTube($oauthClient);
        return $oauthYoutube;

    }

    public function getPersonalFeed($token)
    {
        $youtube = $this->connect($token);
        return $this->encodeJSON(
            $youtube->videos->listVideos(
                "snippet,statistics",
                ["myRating" => "like", "maxResults" => 25]
            )
        );
    }

    public function search($tag)
    {
        return $this->encodeJSON($this->youtube->search->listSearch("snippet", ["maxResults" => 25, "q" => $tag]));
    }

    public function getPublicFeed($location)
    {
        try {
            if ($location != null) {
                $popularMedia = $this->youtube->videos->listVideos(
                    "snippet,statistics",
                    ["chart" => "mostPopular",
                        "regionCode" => $location,
                        "maxResults" => 25]
                );
            }
            if ($location == null) {
                $popularMedia = $this->youtube->videos->listVideos(
                    "snippet,statistics",
                    ["chart" => "mostPopular",
                        "maxResults" => 25]
                );
            }
            return $this->encodeJSON($popularMedia);
        } catch (\Google_IO_Exception $ioException) {
            return null;
        }
    }

    public function encodeJSON($json)
    {
        $feed = [];

        foreach ($json->items as $item) {

            $data = [];
            $data['type'] = "video";
            $data['tags'] = $item->snippet->tags;
            $data['createdTime'] = parent::formatTime(strtotime($item->snippet->publishedAt));

            if (isset($item->id->videoId)) {
                $id = $item->id->videoId;
            }
            if (!isset($item->id->videoId)) {
                $id = $item->id;
            }

            $data['link'] = "https://www.youtube.com/watch?v=" . $id;
            $data['author'] = $item->snippet->channelTitle;
            $channel = $this->youtube->channels->listChannels("contentDetails", ["id" => $item->snippet->channelId]);
            $profile = null;
            if (isset($channel->items[0]->contentDetails->googlePlusUserId)) {
                try {
                    $profile = $this->googlePlus->people->get($channel->items[0]->contentDetails->googlePlusUserId);
                } catch (\Google_Service_Exception $e) {
                    $profile = null;
                }
            }
            $data['authorPicture'] = null;
            if (isset($profile->image)) {
                $data['authorPicture'] = $profile->image->url;
            }
            $data['numberOfLikes'] = null;
            if (isset($item->statistics)) {
                $data['numberOfLikes'] = $item->statistics->likeCount;
            }

            $data['media'] = "https://www.youtube.com/embed/" . $id;
            $data['thumb'] = $item->snippet->thumbnails->default->url;
            $data['title'] = $item->snippet->title;
            $data['text'] = $item->snippet->description;

            $feed[] = $data;
        }

        $newJSON = array('name' => parent::getName(),
            'icon' => parent::getIcon(),
            'color' => parent::getColor(),
            'feed' => $feed);

        return json_encode($newJSON);
    }
}
