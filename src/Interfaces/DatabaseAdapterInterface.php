<?php

namespace App\Interfaces;

interface DatabaseAdapterInterface
{
    /**
     * Select records from a table
     */
    public function select($table, $columns = '*', $filter = '', $order = '', $limit = null);

    /**
     * Insert records into a table
     */
    public function insert($table, $data);

    /**
     * Update records in a table
     */
    public function update($table, $data, $filter);

    /**
     * Delete records from a table
     */
    public function delete($table, $filter);
    
    /**
     * Execute a raw query (mostly for local DB reports or complex joins not supported by simple adapter)
     */
    public function query($sql, $params = []);
}
