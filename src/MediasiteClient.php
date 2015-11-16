<?php

class MediasiteClient {
    function __construct($args) {
        $this->client = new GuzzleHttp\Client(
            [
                'base_url' => $args['api_url'],
                'defaults' => [
                    'headers' => [
                        'Accept' => 'application/json',
                        'sfapikey' => $args['api_key']
                    ],
                    'auth' => [$args['user'], $args['pass']]
                ]
            ]
        );
    }

    function getClient() {
        return $this->client;
    }

    function request($method, $url, $params) {
        $request = $this->client->createRequest($method, $url, $params);

        echo "{$request->getMethod()} {$request->getUrl()}\n";

        if ($request->getMethod() != 'GET') {
            echo "Dry run, doing nothing.\n";
            return NULL;
        } else {
            return $this->client->send($request)->json()['value'];
        }
    }

    function get($url, $query = []) {
        return $this->request('GET', $url, ['query' => $query]);
    }

    function getAll($objects) {
        return $this->get($objects.'?$top=100000');
    }

    function getRecorder($id) {
        return $this->get("Recorders[$id]");
    }

    function getRecurrences($id) {
        return $this->getAll("Schedules($id)/Recurrences");
    }

    function getSchedules() {
        if (!isset($this->schedules)) {
            $raw_schedules = $this->getAll('Schedules');
            $this->schedules = [];
            foreach ($raw_schedules as $schedule) {
                $this->schedules[$schedule['Id']] = $schedule;
            }
        }
        return $this->schedules;
    }

    function getSchedule($id) {
        return $this->getSchedules()[$id];
    }

    function scheduleExists($id) {
        return isset($this->getSchedules()[$id]);
    }

    function findFolder($name, $parent_id) {
        if (!isset($this->folders)) {
            $this->folders = $this->getAll('Folders');
        }

        foreach ($this->folders as $folder) {
            if ($folder['Name'] == $name &&
                (empty($parent_id) || $folder['ParentFolderId'] == $parent_id)) {
                return $folder['Id'];
            }
        }
        return NULL;
    }

    function createFolder($name, $parent_id) {
        return $this->request('POST', 'Folders', ['json' => [
                                      'Name' => $name,
                                      'ParentFolderId' => $parent_id,
                                      'IsShared' => true
                                  ]])->json()['Id'];
    }

    function updateSchedule($id, $params) {
        return $this->request('PATCH', "Schedules('$id')", [ 'json' => $params ]);
    }

    function getScheduleTags($schedule_id) {
        return get("Schedules('$schedule_id')/Tags");
    }

    function addScheduleTag($schedule_id, $tag) {
        $this->request('POST', "Schedules('$scheduleid')/Tags", ['json' => [
                               'Tag' => $tag
                           ]]);
    }

    function removeScheduleTag($schedule_id, $tag_id) {
        $this->request('DELETE', "Schedules('$schedule_id')/Tags('$tag_id')");
    }
}
