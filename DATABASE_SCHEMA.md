# Esquema de Base de Datos - Sistema de Gestión Médica

## Tabla: pacientes

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id_paciente` | SERIAL | PRIMARY KEY | ID único autogenerado |
| `documento_id` | VARCHAR(20) | UNIQUE NOT NULL | Documento de identidad |
| `primer_nombre` | VARCHAR(50) | NOT NULL | Primer nombre |
| `segundo_nombre` | VARCHAR(50) | NULL | Segundo nombre (opcional) |
| `primer_apellido` | VARCHAR(50) | NOT NULL | Primer apellido |
| `segundo_apellido` | VARCHAR(50) | NULL | Segundo apellido (opcional) |
| `fecha_nacimiento` | DATE | NULL | Fecha de nacimiento |
| `telefono` | VARCHAR(15) | NULL | Número de teléfono |
| `email` | VARCHAR(100) | NULL | Correo electrónico |
| `direccion` | TEXT | NULL | Dirección de residencia |
| `estrato` | INTEGER | NULL | Estrato socioeconómico (1-6) |
| `created_at` | TIMESTAMP | DEFAULT NOW() | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT NOW() | Fecha de actualización |

**Nota**: El campo `estrato` se añadió después de la creación inicial y debe estar presente en la base de datos actual.

---

## Tabla: historias_clinicas

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id_historia` | SERIAL | PRIMARY KEY | ID único autogenerado |
| `id_paciente` | INTEGER | REFERENCES pacientes | ID del paciente (FK) |
| `fecha_ingreso` | TIMESTAMP | DEFAULT NOW() | Fecha y hora de ingreso |
| `fecha_egreso` | TIMESTAMP | NULL | Fecha y hora de egreso (si aplica) |
| `motivo_consulta` | TEXT | NULL | Motivo de la consulta |
| `analisis_plan` | TEXT | NULL | Análisis y plan de tratamiento |
| `diagnostico` | TEXT | NULL | Diagnóstico médico |
| `tratamiento` | TEXT | NULL | Tratamiento prescrito |
| `observaciones` | TEXT | NULL | Observaciones adicionales |
| `created_at` | TIMESTAMP | DEFAULT NOW() | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT NOW() | Fecha de actualización |

---

## Relaciones

```
pacientes (1) ----< (N) historias_clinicas
   ↑                        ↓
   |                        |
   └── ON DELETE CASCADE ───┘
```

**Descripción**: 
- Un paciente puede tener múltiples historias clínicas
- Al eliminar un paciente, se eliminan automáticamente sus historias clínicas (CASCADE)

---

## Índices Recomendados

```sql
-- Índice único en documento_id (ya existe por UNIQUE constraint)
CREATE UNIQUE INDEX idx_pacientes_documento ON pacientes(documento_id);

-- Índice en id_paciente para historias_clinicas (ya existe por FK)
CREATE INDEX idx_historias_paciente ON historias_clinicas(id_paciente);

-- Índice en fecha_ingreso para ordenamiento rápido
CREATE INDEX idx_historias_fecha ON historias_clinicas(fecha_ingreso DESC);
```

---

## Políticas RLS (Row Level Security)

```sql
-- Permitir todo para usuarios autenticados
ALTER TABLE pacientes ENABLE ROW LEVEL SECURITY;
ALTER TABLE historias_clinicas ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Permitir todo en pacientes" ON pacientes FOR ALL USING (true);
CREATE POLICY "Permitir todo en historias" ON historias_clinicas FOR ALL USING (true);
```

**Nota**: En producción, estas políticas deberían ser más restrictivas según roles de usuarios.
