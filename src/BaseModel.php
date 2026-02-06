<?php

namespace App;

use App\Interfaces\DatabaseAdapterInterface;
use App\DatabaseFactory;
use App\Validator;

/**
 * Clase Base para todos los modelos
 */
abstract class BaseModel
{
    /** @var DatabaseAdapterInterface */
    protected $supabase; // Keeping name $supabase for backward compat in child classes, but type is generic
    protected $validator;

    public function __construct(?DatabaseAdapterInterface $db = null)
    {
        if (!$db) {
            $this->supabase = DatabaseFactory::create();
        } else {
            $this->supabase = $db;
        }
        $this->validator = new Validator();
    }
}
