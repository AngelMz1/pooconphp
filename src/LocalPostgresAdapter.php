<?php

namespace App;

use App\Interfaces\DatabaseAdapterInterface;
use \PDO;
use \PDOException;

class LocalPostgresAdapter implements DatabaseAdapterInterface
{
    private $pdo;
    private $logger;

    public function __construct($host, $db, $user, $pass, $port = 5432, $logger = null)
    {
        $this->logger = $logger;
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Local DB Connection failed: " . $e->getMessage());
        }
    }

    public function select($table, $columns = '*', $filter = '', $order = '', $limit = null)
    {
        // Simple select builder
        // Columns
        if ($columns !== '*') {
            // Remove Supabase join syntax (e.g., patients(*)) to prevent local SQL errors
            // We just keep the simple columns for now.
            // Regex to remove 'table(*)' or 'table(col,col)' sections
            $cleanColumns = preg_replace('/,\s*[a-zA-Z0-9_]+\s*\(.*?\)/', '', $columns);
            
            // Also handle if it's the first item: 'table(*), *'
            $cleanColumns = preg_replace('/^[a-zA-Z0-9_]+\s*\(.*?\),?/', '', $cleanColumns);
            
            // Basic sanitization on what's left
            $cleanColumns = preg_replace('/[^a-zA-Z0-9_,*]/', '', $cleanColumns);
            
            // If we ended up with nothing (or just comma), revert to *
            if (trim($cleanColumns, ', ') === '') {
                $columns = '*';
            } else {
                $columns = trim($cleanColumns, ', ');
            }
        }
        
        $sql = "SELECT $columns FROM \"$table\"";
        $params = [];

        // Filter Parsing (Simplified Supabase to SQL)
        if ($filter) {
            $where = $this->parseFilter($filter, $params);
            if ($where) {
                $sql .= " WHERE $where";
            }
        }

        // Order
        if ($order) {
            // format: col.asc or col.desc
            $parts = explode('.', $order);
            if (count($parts) == 2) {
                $col = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[0]);
                $dir = strtoupper($parts[1]) === 'DESC' ? 'DESC' : 'ASC';
                $sql .= " ORDER BY \"$col\" $dir";
            }
        }

        // Limit
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if ($this->logger) $this->logger->error("Select Failed: $sql", ['error' => $e->getMessage()]);
            throw new \Exception("Database error: " . $e->getMessage());
        }
    }

    private function addToSyncQueue($table, $action, $data, $pkValue = null) {
        // Skip sync table itself to prevent loops
        if ($table === 'sync_queue') return;

        $syncData = [
            'table_name' => $table,
            'action' => $action,
            'data' => json_encode($data),
            'pk_value' => $pkValue,
            'status' => 'pending'
        ];
        
        // Manual insertion to avoid infinite loop by calling this->insert recursively if we were naive
        // using raw PDO prepare
        $sql = "INSERT INTO sync_queue (table_name, action, data, pk_value, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$table, $action, json_encode($data), $pkValue]);
    }

    public function insert($table, $data)
    {
        // Check if this is a batch insert (array of arrays)
        $isBatch = isset($data[0]) && is_array($data[0]);
        
        if ($isBatch) {
            // Batch insert
            if (empty($data)) {
                return [];
            }
            
            $keys = array_keys($data[0]);
            $fields = '"' . implode('", "', $keys) . '"';
            $placeholderSet = '(' . implode(', ', array_fill(0, count($keys), '?')) . ')';
            $allPlaceholders = implode(', ', array_fill(0, count($data), $placeholderSet));
            
            $sql = "INSERT INTO \"$table\" ($fields) VALUES $allPlaceholders RETURNING *";
            
            // Flatten all values
            $allValues = [];
            foreach ($data as $row) {
                $allValues = array_merge($allValues, array_values($row));
            }
            
            try {
                $this->pdo->beginTransaction();
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($allValues);
                $result = $stmt->fetchAll();
                
                // For batch, we could queue each row but that's expensive
                // Skip sync queue for bulk imports for now
                
                $this->pdo->commit();
                return $result;
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                if ($this->logger) $this->logger->error("Batch Insert Failed", ['error' => $e->getMessage()]);
                throw $e;
            }
        } else {
            // Single insert (original logic)
            $keys = array_keys($data);
            $fields = '"' . implode('", "', $keys) . '"';
            $placeholders = implode(', ', array_fill(0, count($keys), '?'));
            
            $sql = "INSERT INTO \"$table\" ($fields) VALUES ($placeholders) RETURNING *";
            
            try {
                $this->pdo->beginTransaction();
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(array_values($data));
                $result = $stmt->fetchAll();
                
                // Capture for Sync
                if (!empty($result)) {
                    $pk = reset($result[0]);
                    $this->addToSyncQueue($table, 'INSERT', $result[0], $pk);
                }
                
                $this->pdo->commit();
                return $result;
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                if ($this->logger) $this->logger->error("Insert Failed", ['error' => $e->getMessage()]);
                // Check for specific violations
                if ($e->getCode() == '23505') { // Unique violation
                    return ['error' => ['message' => 'Duplicate key violation', 'code' => 409]]; 
                }
            throw new \Exception("Insert error: " . $e->getMessage());
            }
        }
    }

    public function update($table, $data, $filter)
    {
        if (empty($filter)) throw new \Exception("Update requires a filter");

        $sets = [];
        $params = [];
        foreach ($data as $key => $val) {
            $sets[] = "\"$key\" = ?";
            $params[] = $val;
        }
        
        $sql = "UPDATE \"$table\" SET " . implode(', ', $sets);
        
        $whereParams = [];
        $where = $this->parseFilter($filter, $whereParams);
        
        if (!$where) throw new \Exception("Invalid filter for update");
        
        $sql .= " WHERE $where RETURNING *";
        $params = array_merge($params, $whereParams);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            // Capture Sync for EACH affected row
            foreach ($result as $row) {
                // We need ID. Assume first col.
                $pk = reset($row);
                $this->addToSyncQueue($table, 'UPDATE', $row, $pk);
            }

            $this->pdo->commit();
            return $result;
        } catch (PDOException $e) {
             $this->pdo->rollBack();
             if ($this->logger) $this->logger->error("Update Failed", ['error' => $e->getMessage()]);
             throw new \Exception("Update error: " . $e->getMessage());
        }
    }

    public function delete($table, $filter)
    {
        if (empty($filter)) throw new \Exception("Delete requires a filter");
        
        $params = [];
        $where = $this->parseFilter($filter, $params);
        
        // Postgres DELETE RETURNING is supported to get IDs of deleted rows
        $sql = "DELETE FROM \"$table\" WHERE $where RETURNING *";
        
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            foreach ($result as $row) {
                $pk = reset($row);
                // For DELETE, we only strictly need ID, but storing row help audit
                $this->addToSyncQueue($table, 'DELETE', $row, $pk); 
            }

            $this->pdo->commit();
            return true; 
        } catch (PDOException $e) {
             $this->pdo->rollBack();
             throw new \Exception("Delete error: " . $e->getMessage());
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Parses Supabase filter syntax to SQL
     * Supports: eq, neq, gt, lt, ilike, in, is
     * Basic AND support (&), basic OR support (or=(...))
     */
    private function parseFilter($filterStr, &$params)
    {
        // Handle OR: or=(req,req)
        if (strpos($filterStr, 'or=(') === 0) {
            $content = substr($filterStr, 4, -1);
            
            // Smart split respecting parentheses
            $parts = [];
            $buffer = '';
            $parenDepth = 0;
            $len = strlen($content);
            
            for ($i = 0; $i < $len; $i++) {
                $char = $content[$i];
                if ($char === '(') $parenDepth++;
                if ($char === ')') $parenDepth--;
                
                if ($char === ',' && $parenDepth === 0) {
                    $parts[] = $buffer;
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
            }
            if ($buffer !== '') $parts[] = $buffer;

            $clauses = [];
            foreach ($parts as $part) {
                // Ensure part is valid
                $parsed = $this->parseClause(trim($part), $params);
                if ($parsed) $clauses[] = $parsed;
            }
            
            if (empty($clauses)) return null; 
            
            return '(' . implode(' OR ', $clauses) . ')';
        }

        // Handle AND: &
        $parts = explode('&', $filterStr);
        $clauses = [];
        foreach ($parts as $part) {
            $clauses[] = $this->parseClause($part, $params);
        }
        return implode(' AND ', array_filter($clauses));
    }

    private function parseClause($clause, &$params)
    {
        // format: col.op.val OR col=op.val
        
        // Check for '=' separator first (standard Supabase)
        $sepPos = strpos($clause, '=');
        if ($sepPos === false) {
            // Fallback to dot separator
            $sepPos = strpos($clause, '.');
        }
        
        if ($sepPos === false) return null;
        
        $col = substr($clause, 0, $sepPos);
        $rest = substr($clause, $sepPos + 1); // eq.val or op.val
        
        // Now find the operator in the rest (must be dot separated like eq.123)
        $dot2 = strpos($rest, '.');
        if ($dot2 === false) return null;
        
        $op = substr($rest, 0, $dot2);
        $val = substr($rest, $dot2 + 1);
        
        // Sanitize col
        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);

        switch ($op) {
            case 'eq':
                $params[] = $val;
                return "\"$col\" = ?";
            case 'neq':
                $params[] = $val;
                return "\"$col\" != ?";
            case 'gt':
                $params[] = $val;
                return "\"$col\" > ?";
            case 'lt':
                $params[] = $val;
                return "\"$col\" < ?";
            case 'gte':
                $params[] = $val;
                return "\"$col\" >= ?";
            case 'lte':
                $params[] = $val;
                return "\"$col\" <= ?";
            case 'ilike':
                // Supabase uses * as wildcard, SQL uses %
                $val = str_replace('*', '%', $val);
                $params[] = $val;
                return "\"$col\" ILIKE ?";
            case 'like':
                $val = str_replace('*', '%', $val);
                $params[] = $val;
                return "\"$col\" LIKE ?";
            case 'is':
                if ($val === 'null') return "\"$col\" IS NULL";
                if ($val === 'true') return "\"$col\" IS TRUE";
                if ($val === 'false') return "\"$col\" IS FALSE";
                return null;
            case 'in':
                // val is (1,2,3)
                $valRef = trim($val, '()');
                $values = explode(',', $valRef);
                $inParams = [];
                foreach ($values as $v) {
                    $inParams[] = '?';
                    $params[] = trim($v); // Warning: strings need quotes? PDO handles it?
                }
                return "\"$col\" IN (" . implode(', ', $inParams) . ")";
            default:
                return null;
        }
    }
}
