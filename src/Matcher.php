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

    private function process($presentation) {
        //print_r($presentation);
        $room = $this->findRoom($presentation);

        if (null === $room) {
            echo "Could not match room for " . $presentation['Title'] . "\n";
            return;
        }

        $start_time = \DateTime::createFromFormat(
            'Y-m-d\TH:i:s',
            $presentation['RecordDateLocal']
        );

        $seconds = intval(round(intval($presentation['Duration']) / 1000));
        $end_time = clone $start_time;
        $end_time->add(new \DateInterval("PT" . $seconds . "S"));

        echo "# Presentation\n";
        echo "Room: " . $room['name'] . "\n";
        echo "Start time: " . $start_time->format('r') . "\n";
        echo "End time: " . $end_time->format('r') . "\n";
    }

    public function run()
    {
        $dropoff_presentations = Mediasite::getPresentationsInFolder(
            $this->config['dropoff_folder']
        );

        foreach ($dropoff_presentations as $presentation) {
            $this->process($presentation);
        }
    }
}
