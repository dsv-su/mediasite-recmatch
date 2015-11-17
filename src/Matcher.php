<?php

class Matcher
{
    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    private function findRoom($presentation) {
        foreach ($this->config['rooms'] as $room) {
            if (strpos($presentation['Title'], $room['name']) === 0) {
                return $room;
            }
        }
        return null;
    }

    public function run()
    {
        $dropoff_presentations = Mediasite::getPresentationsInFolder(
            $this->config['dropoff_folder']
        );

        foreach ($dropoff_presentations as $presentation) {
            //print_r($presentation);
            $room = $this->findRoom($presentation);

            if (null === $room) {
                echo "Could not match room for " . $presentation['Title'] . "\n";
            }

            
        }
    }
}
