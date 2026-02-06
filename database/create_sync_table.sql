-- Tabla de cola de sincronización (Offline -> Cloud)
CREATE TABLE IF NOT EXISTS sync_queue (
    id SERIAL PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    action VARCHAR(10) NOT NULL CHECK (action IN ('INSERT', 'UPDATE', 'DELETE')),
    data JSONB, -- Datos completos para INSERT/UPDATE
    pk_value VARCHAR(255), -- ID del registro afectado (para DELETE/UPDATE)
    status VARCHAR(20) DEFAULT 'pending', -- pending, synced, error
    error_message TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    synced_at TIMESTAMP
);

-- Indices
CREATE INDEX IF NOT EXISTS idx_sync_status ON sync_queue(status);
CREATE INDEX IF NOT EXISTS idx_sync_created ON sync_queue(created_at);
