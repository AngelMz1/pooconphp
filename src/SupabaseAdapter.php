<?php

namespace App;

use App\Interfaces\DatabaseAdapterInterface;
use App\SupabaseClient;

class SupabaseAdapter implements DatabaseAdapterInterface
{
    private $client;

    public function __construct(SupabaseClient $client)
    {
        $this->client = $client;
    }

    public function select($table, $columns = '*', $filter = '', $order = '', $limit = null)
    {
        return $this->client->select($table, $columns, $filter, $order, $limit);
    }

    public function insert($table, $data)
    {
        return $this->client->insert($table, $data);
    }

    public function update($table, $data, $filter)
    {
        return $this->client->update($table, $data, $filter);
    }

    public function delete($table, $filter)
    {
        return $this->client->delete($table, $filter);
    }

    public function query($sql, $params = [])
    {
        // Supabase REST API doesn't support raw SQL easily.
        // We might implementation RPC calls here if needed.
        throw new \Exception("Raw Query not supported in SupabaseAdapter");
    }
}
