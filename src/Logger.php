<?php

namespace App;

class Logger
{
    private $logFile;
    private $logLevel;
    
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';

    public function __construct($logFile = null, $logLevel = self::INFO)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../logs/app.log';
        $this->logLevel = $logLevel;
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Escribir mensaje en el log
     */
    private function write($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        $logMessage = sprintf(
            "[%s] [%s] %s %s\n",
            $timestamp,
            $level,
            $message,
            $contextStr
        );
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Log de error
     */
    public function error($message, $context = [])
    {
        $this->write(self::ERROR, $message, $context);
    }

    /**
     * Log de advertencia
     */
    public function warning($message, $context = [])
    {
        $this->write(self::WARNING, $message, $context);
    }

    /**
     * Log de información
     */
    public function info($message, $context = [])
    {
        $this->write(self::INFO, $message, $context);
    }

    /**
     * Log de debug
     */
    public function debug($message, $context = [])
    {
        if ($this->logLevel === self::DEBUG) {
            $this->write(self::DEBUG, $message, $context);
        }
    }

    /**
     * Log de excepción
     */
    public function logException(\Exception $e)
    {
        $this->error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs($days = 30)
    {
        $logDir = dirname($this->logFile);
        $files = glob($logDir . '/*.log');
        
        foreach ($files as $file) {
            if (filemtime($file) < strtotime("-{$days} days")) {
                unlink($file);
            }
        }
    }
}
