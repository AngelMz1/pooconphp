<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Cliente mejorado para interactuar con la API de Supabase
 */
class SupabaseClient
{
    private $client;
    private $baseUrl;
    private $apiKey;
    private $logger;
    private $maxRetries = 3;

    /**
     * Constructor del cliente Supabase
     * 
     * @param string $url URL base de Supabase
     * @param string $key API Key de Supabase
     * @param Logger|null $logger Logger opcional para registrar operaciones
     */
    public function __construct($url, $key, $logger = null)
    {
        if (empty($url) || empty($key)) {
            throw new \Exception("URL y API Key son requeridos");
        }
        
        $this->baseUrl = $url;
        $this->apiKey = $key;
        $this->logger = $logger;
        
        $this->client = new Client([
            'base_uri' => $url,
            'timeout' => 10,
            'headers' => [
                'apikey' => $key,
                'Authorization' => "Bearer $key",
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if ($this->logger) {
            $this->logger->info("SupabaseClient inicializado", ['url' => $url]);
        }
    }

    /**
     * Realizar consulta SELECT en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param string $columns Columnas a seleccionar (por defecto *)
     * @param string $filter Filtros en formato Supabase (ej: "id=eq.1")
     * @param string $order Ordenamiento (ej: "created_at.desc")
     * @return array Resultados de la consulta
     * @throws \Exception Si la consulta falla
     */
    public function select($table, $columns = '*', $filter = '', $order = '', $limit = null)
    {
        $this->validateTable($table);
        
        try {
            $url = "/rest/v1/$table?select=$columns";
            if ($filter) {
                $url .= "&$filter";
            }
            if ($order) {
                $url .= "&order=$order";
            }
            if ($limit) {
                $url .= "&limit=$limit";
            }
            
            $response = $this->executeWithRetry('GET', $url);
            $data = json_decode($response->getBody(), true);
            
            if ($this->logger) {
                $this->logger->debug("SELECT exitoso", ['table' => $table, 'count' => count($data)]);
            }
            
            return $data;
        } catch (RequestException $e) {
            return $this->handleError($e, "SELECT", $table);
        }
    }

    /**
     * Insertar datos en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Datos a insertar
     * @return array Datos insertados
     * @throws \Exception Si la inserción falla
     */
    public function insert($table, $data)
    {
        $this->validateTable($table);
        $this->validateData($data);
        
        try {
            $response = $this->client->post("/rest/v1/$table", [
                'json' => $data,
                'headers' => [
                    'Prefer' => 'return=representation'
                ]
            ]);
            
            $result = json_decode($response->getBody(), true);
            
            if ($this->logger) {
                $this->logger->info("INSERT exitoso", ['table' => $table]);
            }
            
            return $result;
        } catch (RequestException $e) {
            return $this->handleError($e, "INSERT", $table);
        }
    }

    /**
     * Actualizar datos en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Datos a actualizar
     * @param string $filter Filtro para identificar registros (ej: "id=eq.1")
     * @return array Datos actualizados
     * @throws \Exception Si la actualización falla
     */
    public function update($table, $data, $filter)
    {
        $this->validateTable($table);
        $this->validateData($data);
        
        if (empty($filter)) {
            throw new \Exception("El filtro es requerido para UPDATE");
        }
        
        try {
            $response = $this->client->patch("/rest/v1/$table?$filter", [
                'json' => $data,
                'headers' => [
                    'Prefer' => 'return=representation'
                ]
            ]);
            
            $result = json_decode($response->getBody(), true);
            
            if ($this->logger) {
                $this->logger->info("UPDATE exitoso", ['table' => $table, 'filter' => $filter]);
            }
            
            return $result;
        } catch (RequestException $e) {
            return $this->handleError($e, "UPDATE", $table);
        }
    }

    /**
     * Eliminar datos de una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param string $filter Filtro para identificar registros
     * @return bool True si la eliminación fue exitosa
     * @throws \Exception Si la eliminación falla
     */
    public function delete($table, $filter)
    {
        $this->validateTable($table);
        
        if (empty($filter)) {
            throw new \Exception("El filtro es requerido para DELETE");
        }
        
        try {
            $response = $this->client->delete("/rest/v1/$table?$filter");
            $success = $response->getStatusCode() === 204;
            
            if ($this->logger && $success) {
                $this->logger->info("DELETE exitoso", ['table' => $table, 'filter' => $filter]);
            }
            
            return $success;
        } catch (RequestException $e) {
            return $this->handleError($e, "DELETE", $table);
        }
    }

    /**
     * Validar nombre de tabla
     */
    private function validateTable($table)
    {
        if (empty($table) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \Exception("Nombre de tabla inválido: $table");
        }
    }

    /**
     * Validar datos
     */
    private function validateData($data)
    {
        if (empty($data) || !is_array($data)) {
            throw new \Exception("Los datos deben ser un array no vacío");
        }
    }

    /**
     * Ejecutar petición con reintentos
     */
    private function executeWithRetry($method, $url, $retries = 0)
    {
        try {
            return $this->client->request($method, $url);
        } catch (RequestException $e) {
            if ($retries < $this->maxRetries && $this->isRetriableError($e)) {
                usleep(100000 * ($retries + 1)); // Exponential backoff
                return $this->executeWithRetry($method, $url, $retries + 1);
            }
            throw $e;
        }
    }

    /**
     * Verificar si el error es recuperable
     */
    private function isRetriableError(RequestException $e)
    {
        if (!$e->hasResponse()) {
            return true; // Errores de red
        }
        
        $statusCode = $e->getResponse()->getStatusCode();
        return in_array($statusCode, [429, 500, 502, 503, 504]);
    }

    /**
     * Manejar errores de manera unificada
     */
    private function handleError(RequestException $e, $operation, $table)
    {
        $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
        $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
        
        $errorMessage = "Error en $operation de $table";
        
        if ($statusCode === 404) {
            $errorMessage .= ": Tabla no encontrada";
        } elseif ($statusCode === 401 || $statusCode === 403) {
            $errorMessage .= ": Sin permisos o credenciales inválidas";
        } elseif ($statusCode === 409) {
            $errorMessage .= ": Conflicto (posible duplicado)";
        }
        
        $errorMessage .= " - " . $e->getMessage();
        
        if (!empty($errorBody)) {
            $errorMessage .= " - Detalles: " . $errorBody;
        }
        
        if ($this->logger) {
            $this->logger->error($errorMessage, [
                'operation' => $operation,
                'table' => $table,
                'status_code' => $statusCode
            ]);
        }
        
        throw new \Exception($errorMessage);
    }
}