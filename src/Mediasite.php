<?php

use GuzzleHttp\Psr7;

class Mediasite
{
    private static $guzzle;
    private static $folders;

    public static function init(array $config)
    {
        if (!isset($config)) {
            $config = json_decode(file_get_contents('mediasite_api.json'));
        }

        self::$guzzle = new \GuzzleHttp\Client(
            [
                'base_uri' => $config['uri'],
                'headers' => [
                    'Accept' => 'application/json',
                    'sfapikey' => $config['key']
                ],
                'auth' => [$config['user'], $config['password']],
                'http_errors' => false
            ]
        );
    }

    public static function initUsingConfigFile($file = 'mediasite_api.json')
    {
        $config = json_decode(file_get_contents($file), true);
        self::init($config);
    }

    public static function getGuzzle()
    {
        if (!isset(self::$guzzle)) {
            self::initUsingConfigFile();
        }
        return self::$guzzle;
    }

    public static function request($method, $url, array $query = []) {
        echo "$method $url\n";

        if ($method != 'GET') {
            echo "Dry run, doing nothing.\n";
            return null;
        }

        $response = self::getGuzzle()->request(
            $method, $url, ['query' => $query]
        );

        switch ($response->getStatusCode()) {
            case 200:
                return json_decode($response->getBody(), true);
            case 404:
                return null;
            default:
                $uri = Psr7\Uri::resolve(
                    Psr7\uri_for(self::$guzzle->getConfig('base_uri')),
                    $path
                );
                $uri = $uri->withQuery($query);
                throw new ServerException(
                    $response->getStatusCode()
                    . " " . $response->getReasonPhrase()
                    . ". URI: " . $uri
                );
        }
    }

    public static function get($url, $query = []) {
        return self::request('GET', $url, $query);
    }

    public static function getAll($objects) {
        return self::get($objects, ['$top' => 100000]);
    }

    public static function findFolder($name, $parent_id) {
        if (!isset(self::$folders)) {
            self::$folders = self::getAll('Folders');
        }

        foreach (self::$folders as $folder) {
            if ($folder['Name'] == $name &&
                (empty($parent_id) || $folder['ParentFolderId'] == $parent_id)) {
                return $folder['Id'];
            }
        }
        return null;
    }

    public static function createFolder($name, $parent_id) {
        return self::request('POST', 'Folders', ['json' => [
                                     'Name' => $name,
                                     'ParentFolderId' => $parent_id,
                                     'IsShared' => true
                                 ]])['Id'];
    }
}
