<?php
namespace Strebo;

use Strebo;

class DataCollector extends \Thread
{
    private $socialNetworks = [];
    private $publicFeed;

    public function __construct()
    {
        $pattern = '/[A-Za-z]*/';
        $match = [];
        foreach (scandir(__DIR__ . '/SocialNetworks') as $file) {
            preg_match($pattern, $file, $match);

            if ($match[0] != "") {
                $this->socialNetworks[$match[0]] = (array)[];
            }

        }
        foreach ($this->socialNetworks as $network => $value) {
            $createInstance = "Strebo\\SocialNetworks\\" . $network;
            $this->socialNetworks[$network] = new $createInstance();
        }
        $this->publicFeed = (array)["DE" => (array)[], "US" => (array)[], "W" => (array)[]];
        $this->start();
    }

    public function collectPublicFeed()
    {
        foreach ($this->publicFeed as $location => $value) {
            foreach ($this->socialNetworks as $network) {
                $locationString = "getLocation" . $location;
                $value = json_decode($network->getPublicFeed($network->$locationString()));
            }
        }
    }

    public function getPublicFeed($location)
    {
        return json_encode(["type" => "data", "json" => $this->publicFeed[$location]]);
    }

    public function collectPersonalFeed()
    {
        $personalFeed = [];

        foreach ($this->socialNetworks as $network) {
            $personalFeed[] = json_decode($this->$network->getPersonalFeed());
        }

        return json_encode($personalFeed);
    }

    public function search($tag)
    {
        $results = [];
        foreach ($this->socialNetworks as $network) {
            $results[] = json_decode($network->search($tag));
        }

        return json_encode($results);

    }

    public function run()
    {
        while (true) {
            $this->collectPublicFeed();
            sleep(90);
        }
    }

}
