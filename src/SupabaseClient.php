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

    public function select($table, $columns = '*', $filter = '', $order = '')
    {
        try {
            $url = "/rest/v1/$table?select=$columns";
            if($filter) {
                $url .= "&$filter";
            }
            if($order) {
                $url .= "&order=$order";
            }
            $response = $this->client->get($url);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            throw new \Exception("Error al consultar $table: " . $e->getMessage() . " - " . $errorBody);
        }
    }

    public function insert($table, $data)
    {
        try {
            $response = $this->client->post("/rest/v1/$table", [
                'json' => $data,
                'headers' => [
                    'Prefer' => 'return=representation'
                ]
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            throw new \Exception("Error al insertar en $table: " . $e->getMessage() . " - " . $errorBody);
        }
    }

    public function update($table, $data, $filter)
    {
        try {
            $response = $this->client->patch("/rest/v1/$table?$filter", [
                'json' => $data,
                'headers' => [
                    'Prefer' => 'return=representation'
                ]
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            throw new \Exception("Error al actualizar $table: " . $e->getMessage() . " - " . $errorBody);
        }
    }

    public function delete($table, $filter)
    {
        try {
            $response = $this->client->delete("/rest/v1/$table?$filter");
            return $response->getStatusCode() === 204;
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            throw new \Exception("Error al eliminar de $table: " . $e->getMessage() . " - " . $errorBody);
        }
    }
}