<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase Base para todos los modelos
 */
abstract class BaseModel
{
    protected $supabase;
    protected $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }
}
