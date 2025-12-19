-- Tabla para items de receta (detalles de la fórmula)
CREATE TABLE IF NOT EXISTS public.receta_items (
    id SERIAL PRIMARY KEY,
    id_formula INTEGER NOT NULL,
    medicamento_id INTEGER, -- FK a catalogo medicamentos. Nullable si es texto libre? Idealmente FK.
    nombre_medicamento TEXT, -- Respaldo de texto si no select
    dosis TEXT,
    frecuencia TEXT,
    via_administracion TEXT,
    duracion TEXT,
    cantidad_total INTEGER,
    presentacion TEXT,
    observaciones TEXT,
    
    CONSTRAINT fk_receta FOREIGN KEY (id_formula) REFERENCES formulas_medicas(id_formula) ON DELETE CASCADE
    -- CONSTRAINT fk_medicamento FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) -- Opcional, si queremos estricto
);

-- Indice para búsquedas rápidas
CREATE INDEX IF NOT EXISTS idx_receta_items_formula ON public.receta_items (id_formula);
