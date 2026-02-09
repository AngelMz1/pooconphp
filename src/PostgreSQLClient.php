<?php

namespace App;

use PDO;
use PDOException;
use App\Interfaces\DatabaseAdapterInterface;

/**
 * Cliente para PostgreSQL Local usando PDO
 * Implementa la misma interfaz que SupabaseClient para compatibilidad
 */
class PostgreSQLClient implements DatabaseAdapterInterface
{
    private $pdo;
    private $logger;
    
    /**
     * Constructor del cliente PostgreSQL
     * 
     * @param string $host Host de PostgreSQL
     * @param string $database Nombre de la base de datos
     * @param string $username Usuario de PostgreSQL
     * @param string $password Contraseña
     * @param int $port Puerto (default: 5432)
     * @param Logger|null $logger Logger opcional
     */
    public function __construct($host, $database, $username, $password, $port = 5432, $logger = null)
    {
        $this->logger = $logger;
        
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$database";
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            if ($this->logger) {
                $this->logger->info("PostgreSQLClient conectado", ['host' => $host, 'database' => $database]);
            }
        } catch (PDOException $e) {
            if ($this->logger) {
                $this->logger->error("Error conectando a PostgreSQL", ['error' => $e->getMessage()]);
            }
            throw new \Exception("Error de conexión a PostgreSQL: " . $e->getMessage());
        }
    }
    
    /**
     * Realizar consulta SELECT
     * 
     * @param string $table Nombre de la tabla
     * @param string $columns Columnas a seleccionar (por defecto *)
     * @param string $filter Filtros en formato Supabase (ej: "id=eq.1")
     * @param string $order Ordenamiento (ej: "created_at.desc")
     * @param int|null $limit Límite de resultados
     * @return array Resultados de la consulta
     */
    public function select($table, $columns = '*', $filter = '', $order = '', $limit = null)
    {
        try {
            $sql = "SELECT $columns FROM \"$table\"";
            $params = [];
            
            // Convertir filtros de formato Supabase a SQL
            if ($filter) {
                list($whereClause, $params) = $this->parseSupabaseFilter($filter);
                if ($whereClause) {
                    $sql .= " WHERE $whereClause";
                }
            }
            
            // Convertir ordenamiento de formato Supabase a SQL
            if ($order) {
                $orderClause = $this->parseSupabaseOrder($order);
                if ($orderClause) {
                    $sql .= " ORDER BY $orderClause";
                }
            }
            
            if ($limit) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = $limit;
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll();
            
            if ($this->logger) {
                $this->logger->debug("SELECT exitoso", ['table' => $table, 'count' => count($result)]);
            }
            
            return $result;
        } catch (PDOException $e) {
            return $this->handleError($e, "SELECT", $table);
        }
    }
    
    /**
     * Insertar datos en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Datos a insertar
     * @return array Datos insertados
     */
    public function insert($table, $data)
    {
        try {
            $columns = array_keys($data);
            $placeholders = array_map(fn($col) => ":$col", $columns);
            
            $sql = sprintf(
                "INSERT INTO \"%s\" (%s) VALUES (%s) RETURNING *",
                $table,
                '"' . implode('", "', $columns) . '"',
                implode(', ', $placeholders)
            );
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll();
            
            if ($this->logger) {
                $this->logger->info("INSERT exitoso", ['table' => $table]);
            }
            
            return $result;
        } catch (PDOException $e) {
            return $this->handleError($e, "INSERT", $table);
        }
    }
    
    /**
     * Actualizar datos en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Datos a actualizar
     * @param string $filter Filtro en formato Supabase
     * @return array Datos actualizados
     */
    public function update($table, $data, $filter)
    {
        try {
            $setClauses = [];
            foreach (array_keys($data) as $column) {
                $setClauses[] = "\"$column\" = :$column";
            }
            
            $sql = sprintf(
                "UPDATE \"%s\" SET %s",
                $table,
                implode(', ', $setClauses)
            );
            
            $params = [];
            foreach ($data as $key => $value) {
                $params[":$key"] = $value;
            }
            
            // Agregar WHERE
            if ($filter) {
                list($whereClause, $whereParams) = $this->parseSupabaseFilter($filter);
                if ($whereClause) {
                    $sql .= " WHERE $whereClause";
                    $params = array_merge($params, $whereParams);
                }
            }
            
            $sql .= " RETURNING *";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll();
            
            if ($this->logger) {
                $this->logger->info("UPDATE exitoso", ['table' => $table]);
            }
            
            return $result;
        } catch (PDOException $e) {
            return $this->handleError($e, "UPDATE", $table);
        }
    }
    
    /**
     * Eliminar datos de una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param string $filter Filtro en formato Supabase
     * @return bool True si la eliminación fue exitosa
     */
    public function delete($table, $filter)
    {
        try {
            $sql = "DELETE FROM \"$table\"";
            $params = [];
            
            if ($filter) {
                list($whereClause, $params) = $this->parseSupabaseFilter($filter);
                if ($whereClause) {
                    $sql .= " WHERE $whereClause";
                }
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $success = $stmt->execute();
            
            if ($this->logger && $success) {
                $this->logger->info("DELETE exitoso", ['table' => $table]);
            }
            
            return $success;
        } catch (PDOException $e) {
            return $this->handleError($e, "DELETE", $table);
        }
    }
    
    /**
     * Ejecutar query SQL personalizado
     * 
     * @param string $sql Query SQL
     * @param array $params Parámetros
     * @return array Resultados
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Error en query: " . $e->getMessage());
        }
    }
    
    /**
     * Convertir filtro formato Supabase a SQL WHERE clause
     * Formatos soportados:
     * - "id=eq.1" => "id = :id"
     * - "name=like.*John*" => "name LIKE :name"
     * - "age=gt.18" => "age > :age"
     * 
     * @param string $filter Filtro en formato Supabase
     * @return array [whereClause, params]
     */
    private function parseSupabaseFilter($filter)
    {
        $parts = explode('&', $filter);
        $whereClauses = [];
        $params = [];
        $paramIndex = 0;
        
        foreach ($parts as $part) {
            if (strpos($part, '=') === false) continue;
            
            list($column, $condition) = explode('=', $part, 2);
            
            // Parsear operador y valor
            if (preg_match('/^(eq|neq|gt|gte|lt|lte|like|ilike|is|in)\.(.*)$/', $condition, $matches)) {
                $operator = $matches[1];
                $value = $matches[2];
                
                $paramName = ":param_$paramIndex";
                $paramIndex++;
                
                switch ($operator) {
                    case 'eq':
                        $whereClauses[] = "\"$column\" = $paramName";
                        $params[$paramName] = $value;
                        break;
                    case 'neq':
                        $whereClauses[] = "\"$column\" != $paramName";
                        $params[$paramName] = $value;
                        break;
                    case 'gt':
                        $whereClauses[] = "\"$column\" > $paramName";
                        $params[$paramName] = $value;
                        break;
                    case 'gte':
                        $whereClauses[] = "\"$column\" >= $paramName";
                        $params[$paramName] = $value;
                        break;
                    case 'lt':
                        $whereClauses[] = "\"$column\" < $paramName";
                        $params[$paramName] = $value;
                        break;
                    case 'lte':
                        $whereClauses[] = "\"$column\" <= $paramName";
                        $params[$paramName] = $value;
                        break;
                    case 'like':
                    case 'ilike':
                        $whereClauses[] = "\"$column\" ILIKE $paramName";
                        $params[$paramName] = str_replace('*', '%', $value);
                        break;
                    case 'is':
                        if ($value === 'null') {
                            $whereClauses[] = "\"$column\" IS NULL";
                        } else {
                            $whereClauses[] = "\"$column\" = $paramName";
                            $params[$paramName] = $value;
                        }
                        break;
                    case 'in':
                        $values = explode(',', str_replace(['(', ')'], '', $value));
                        $inParams = [];
                        foreach ($values as $i => $v) {
                            $inParamName = "{$paramName}_$i";
                            $inParams[] = $inParamName;
                            $params[$inParamName] = trim($v);
                        }
                        $whereClauses[] = "\"$column\" IN (" . implode(', ', $inParams) . ")";
                        break;
                }
            }
        }
        
        return [implode(' AND ', $whereClauses), $params];
    }
    
    /**
     * Convertir ordenamiento formato Supabase a SQL ORDER BY
     * Ejemplo: "created_at.desc" => "created_at DESC"
     * 
     * @param string $order Ordenamiento en formato Supabase
     * @return string ORDER BY clause
     */
    private function parseSupabaseOrder($order)
    {
        $parts = explode(',', $order);
        $orderClauses = [];
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '.') !== false) {
                list($column, $direction) = explode('.', $part);
                $direction = strtoupper($direction);
                $orderClauses[] = "\"$column\" $direction";
            } else {
                $orderClauses[] = "\"$part\" ASC";
            }
        }
        
        return implode(', ', $orderClauses);
    }
    
    /**
     * Manejar errores de PDO
     */
    private function handleError(PDOException $e, $operation, $table)
    {
        $errorMessage = "Error en $operation de $table: " . $e->getMessage();
        
        if ($this->logger) {
            $this->logger->error($errorMessage, [
                'operation' => $operation,
                'table' => $table,
                'code' => $e->getCode()
            ]);
        }
        
        throw new \Exception($errorMessage);
    }
    
    /**
     * Obtener conexión PDO (para casos especiales)
     */
    public function getPDO()
    {
        return $this->pdo;
    }
    
    /**
     * Comenzar transacción
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }
}
