<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SupabaseClient
{
    private $client;
    private $baseUrl;
    private $apiKey;

    public function __construct($url, $key)
    {
        $this->baseUrl = $url;
        $this->apiKey = $key;
        $this->client = new Client([
            'base_uri' => $url,
            'timeout' => 10,
            'headers' => [
                'apikey' => $key,
                'Authorization' => "Bearer $key",
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function select($table, $columns = '*', $filter = '')
    {
        try {
            $url = "/rest/v1/$table?select=$columns";
            if($filter) {
                $url .= "&$filter";
            }
            $response = $this->client->get($url);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception("Error al consultar: " . $e->getMessage());
        }
    }

    public function insert($table, $data)
    {
        try {
            $response = $this->client->post("/rest/v1/$table", [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception("Error al insertar: " . $e->getMessage());
        }
    }
}