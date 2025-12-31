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

## Tabla: consultas

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id_consulta` | SERIAL | PRIMARY KEY | ID único autogenerado |
| `id_paciente` | INTEGER | REFERENCES pacientes | ID del paciente |
| `motivo_consulta` | TEXT | NULL | Motivo de consulta |
| `enfermedad_actual`| TEXT | NULL | Enfermedad actual |
| `medico_id` | INTEGER | REFERENCES users | ID del médico que atiende |
| `estado` | VARCHAR | NOT NULL | 'pendiente', 'en_proceso', 'finalizada' |

---

## Tabla: historias_clinicas

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id_historia` | SERIAL | PRIMARY KEY | ID único autogenerado |
| `id_paciente` | INTEGER | REFERENCES pacientes | ID del paciente (FK) |
| `id_consulta` | INTEGER | REFERENCES consultas | ID de la consulta asociada |
| `fecha_ingreso` | TIMESTAMP | DEFAULT NOW() | Fecha y hora de ingreso |
| `motivo_consulta` | TEXT | NULL | Motivo (copiado de consulta) |
| `analisis_plan` | TEXT | NULL | Análisis y plan de tratamiento |
| `diagnostico` | TEXT | NULL | Diagnóstico médico |
| `tratamiento` | TEXT | NULL | Tratamiento prescrito |
| `observaciones` | TEXT | NULL | Observaciones adicionales |

---

## Tabla: signos_vitales

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | SERIAL | PRIMARY KEY | ID único |
| `id_historia` | INTEGER | REFERENCES historias_clinicas | Historia clínica asociada |
| `ta` | VARCHAR | NULL | Tensión Arterial |
| `pulso` | INTEGER | NULL | Pulso |
| `f_res` | INTEGER | NULL | Frecuencia Respiratoria |
| `temperatura` | DECIMAL | NULL | Temperatura |
| `peso` | DECIMAL | NULL | Peso |
| `talla` | INTEGER | NULL | Talla (cm) |
| `sp02` | INTEGER | NULL | Saturación de Oxígeno |

---

## Tabla: formulas_medicas

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id_formula` | SERIAL | PRIMARY KEY | ID único |
| `id_historia` | INTEGER | REFERENCES historias_clinicas | Historia clínica asociada |
| `medicamento_id` | INTEGER | REFERENCES medicamentos | ID del medicamento (Catálogo) |
| `dosis` | VARCHAR | NULL | Dosis prescrita |
| `cantidad` | INTEGER | NULL | Cantidad total |
| `fecha_hora` | TIMESTAMP | DEFAULT NOW() | Fecha de prescripción |

---

## Tabla: procedimientos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | SERIAL | PRIMARY KEY | ID único |
| `procedimiento` | VARCHAR | NULL | Nombre del procedimiento |
| `descripcion` | TEXT | NULL | Descripción |
| `codigo` | VARCHAR | NULL | Código CUPS/CIE10 |

---

## Relaciones

```
pacientes (1) ----< (N) consultas
consultas (1) ----< (1) historias_clinicas
historias (1) ----< (N) signos_vitales
historias (1) ----< (N) formulas_medicas
```
