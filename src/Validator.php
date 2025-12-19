<?php

namespace App;

class Validator
{
    private $errors = [];

    /**
     * Validar que un campo no esté vacío
     */
    public function required($value, $fieldName)
    {
        if (empty($value) && $value !== '0') {
            $this->errors[] = "El campo {$fieldName} es requerido";
            return false;
        }
        return true;
    }

    /**
     * Validar documento de identidad colombiano
     */
    public function documentoId($documento)
    {
        // Documento debe tener entre 6 y 10 dígitos
        if (!preg_match('/^\d{6,10}$/', $documento)) {
            $this->errors[] = "El documento debe contener entre 6 y 10 dígitos";
            return false;
        }
        return true;
    }

    /**
     * Validar email
     */
    public function email($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "El email no es válido";
            return false;
        }
        return true;
    }

    /**
     * Validar teléfono colombiano
     */
    public function telefono($telefono)
    {
        // Acepta números de 7 o 10 dígitos
        if (!preg_match('/^\d{7}$|^\d{10}$/', $telefono)) {
            $this->errors[] = "El teléfono debe tener 7 o 10 dígitos";
            return false;
        }
        return true;
    }

    /**
     * Validar longitud mínima
     */
    public function minLength($value, $min, $fieldName)
    {
        if (strlen($value) < $min) {
            $this->errors[] = "El campo {$fieldName} debe tener al menos {$min} caracteres";
            return false;
        }
        return true;
    }

    /**
     * Validar longitud máxima
     */
    public function maxLength($value, $max, $fieldName)
    {
        if (strlen($value) > $max) {
            $this->errors[] = "El campo {$fieldName} no debe exceder {$max} caracteres";
            return false;
        }
        return true;
    }

    /**
     * Validar rango numérico
     */
    public function numberRange($value, $min, $max, $fieldName)
    {
        if (!is_numeric($value)) {
            $this->errors[] = "El campo {$fieldName} debe ser numérico";
            return false;
        }
        
        if ($value < $min || $value > $max) {
            $this->errors[] = "El campo {$fieldName} debe estar entre {$min} y {$max}";
            return false;
        }
        return true;
    }

    /**
     * Validar estrato (1-6)
     */
    public function estrato($estrato)
    {
        return $this->numberRange($estrato, 1, 6, 'estrato');
    }

    /**
     * Sanitizar string para prevenir XSS
     */
    public function sanitize($value)
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtener todos los errores
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Verificar si hay errores
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Limpiar errores
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * Validar datos de paciente
     */
    public function validarPaciente($datos)
    {
        $this->clearErrors();
        
        $this->required($datos['documento_id'] ?? '', 'documento_id');
        $this->documentoId($datos['documento_id'] ?? '');
        $this->required($datos['primer_nombre'] ?? '', 'primer_nombre');
        $this->required($datos['primer_apellido'] ?? '', 'primer_apellido');
        
        if (isset($datos['estrato'])) {
            $this->estrato($datos['estrato']);
        }
        
        if (isset($datos['email']) && !empty($datos['email'])) {
            $this->email($datos['email']);
        }
        
        if (isset($datos['telefono']) && !empty($datos['telefono'])) {
            $this->telefono($datos['telefono']);
        }
        
        return !$this->hasErrors();
    }

    /**
     * Validar datos de historia clínica
     */
    public function validarHistoriaClinica($datos)
    {
        $this->clearErrors();
        
        $this->required($datos['id_paciente'] ?? '', 'id_paciente');
    // $this->required($datos['motivo_consulta'] ?? '', 'motivo_consulta'); // Moved to Consultas
    
        return !$this->hasErrors();
    }
}
