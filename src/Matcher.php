<?php
use DsvSu\Daisy;
use DsvSu\Daisy\ScheduleType;

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

    private function findDaisyEvent($room_id, $start_time, $end_time)
    {
        $events = Daisy\Event::find($room_id, $start_time);

        if (empty($events)) {
            echo "No Daisy events this day.\n";
            return null;
        }

        $start_ts = $start_time->getTimestamp();
        $end_ts = $end_time->getTimestamp();
        $events_overlap = array_map(function ($event) use ($start_ts, $end_ts) {
                $evt_start_ts = $event->getStart()->getTimestamp();
                $evt_end_ts = $event->getEnd()->getTimestamp();
                $overlap = min($end_ts, $evt_end_ts) - max($start_ts, $evt_start_ts);
                return [$overlap, $event];
            }, $events);

        list($max_overlap, $event) = max($events_overlap);

        if ($max_overlap <= 0) {
            echo "No event overlapping with recording found.\n";
            return null;
        }
        echo "Overlap: " . $max_overlap . " seconds\n";
        return $event;
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

        echo "# Recording\n";
        echo "Room: " . $room['name'] . "\n";
        echo "Start time: " . $start_time->format('r') . "\n";
        echo "End time: " . $end_time->format('r') . "\n";

        $event = $this->findDaisyEvent($room['daisy_id'], $start_time, $end_time);

        if (null === $event) {
            echo "No Daisy event found.\n";
            return;
        }

        echo "Found Daisy event\n";
        echo "Start time: " . $event->getStart()->format('r') . "\n";
        echo "End time: " . $event->getEnd()->format('r') . "\n";

        $type = $event->getScheduleType();
        if ($type === ScheduleType::EDUCATION) {
            $csis = $event->getCourseSegmentInstances();
            $csi = $csis[0];
            echo "Course: " . $csi->getName() . "\n";
        }
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
