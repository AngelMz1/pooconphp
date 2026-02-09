# 🗄️ Script de Base de Datos PostgreSQL Local

## Descripción

Este script configura completamente la base de datos PostgreSQL local para el sistema de gestión médica.

## ✅ Contenido del Script

### 1. Schema Completo (create_database_complete.sql)
- 20 tablas del sistema
- Constraints y relaciones
- Índices optimizados

### 2. Datos de Referencia (insert_reference_data.sql)
- **Tipos de Documento**: CC, TI, RC, CE, PA, MS, AS
- **EPS**: 19 EPS colombianas + Particular

### 3. Datos Geográficos de Nariño (insert_narino_geographic_data.sql)
- **26 municipios** de Nariño con códigos DANE
- **51 barrios** de Pasto organizados por zonas

### 4. Usuarios del Sistema (insert_users_complete.sql)
- **admin** / admin123 (Administrador)
- **medico** / admin123 (Médico)
- **cajero** / admin123 (Cajero)
- **facturador** / admin123 (Facturador/Cajero)

## 🚀 Uso

### Opción 1: Script Automático (Recomendado)

```bash
cd /var/www/html/pooconphp/database
sudo bash setup_local_pg.sh
```

### Opción 2: Manual

```bash
# 1. Crear base de datos
sudo -u postgres psql -c "DROP DATABASE IF EXISTS pooconphp;"
sudo -u postgres psql -c "CREATE DATABASE pooconphp;"

# 2. Crear usuario
sudo -u postgres psql -c "DROP USER IF EXISTS pooconphp_user;"
sudo -u postgres psql -c "CREATE USER pooconphp_user WITH PASSWORD 'pooconphp_2024!';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE pooconphp TO pooconphp_user;"

# 3. Ejecutar script completo
sudo -u postgres psql -d pooconphp -f setup_database_complete.sql
```

## 📊 Datos Incluidos

### Tipos de Documento
```
CC  - Cédula de Ciudadanía
TI  - Tarjeta de Identidad  
RC  - Registro Civil
CE  - Cédula de Extranjería
PA  - Pasaporte
MS  - Menor Sin Identificación
AS  - Adulto Sin Identificación
```

### EPS Principales
```
Nueva EPS, Sura, Sanitas, Salud Total, Compensar, Famisanar,
Aliansalud, Coosalud, Mutual Ser, Cafesalud, Comfenalco,
Comfandi, Cruz Blanca, Medimás, Capital Salud, Coomeva,
Emssanar, Particular, Otra EPS
```

### Municipios de Nariño (26)
```
Pasto, Ipiales, Tumaco, Túquerres, Sandoná, La Florida,
Consacá, Tangua, Yacuanquer, Buesaco, Funes, La Cruz,
Taminango, Nariño, Sapuyes, Guaitarilla, Ospina, Pupiales,
Piedrancha, Policarpa, Ricaurte, Samaniego, El Tambo,
La Unión, La Llanada, Cumbal
```

### Barrios de Pasto (51 por zonas)
- Centro (7)
- Norte (14)
- Sur (11)
- Oriente (7)
- Occidente (7)

## 🔐 Credenciales de Acceso

### Base de Datos
```
Host: 127.0.0.1
Puerto: 5432
Database: pooconphp
Usuario: pooconphp_user
Contraseña: pooconphp_2024!
```

### Usuarios del Sistema
```
admin      / admin123  (Acceso total)
medico     / admin123  (Consultas, historias)
cajero     / admin123  (Facturación, citas)
facturador / admin123  (Facturación, citas)
```

## 📝 Archivos

| Archivo | Descripción | Líneas |
|---------|-------------|--------|
| `setup_local_pg.sh` | Script bash de instalación | 95 |
| `setup_database_complete.sql` | SQL consolidado completo | 689 |
| `create_database_complete.sql` | Schema de base de datos | 373 |
| `insert_reference_data.sql` | Tipos de documento y EPS | 57 |
| `insert_narino_geographic_data.sql` | Municipios y barrios | 220 |
| `insert_users_complete.sql` | Usuarios y perfiles | 39 |

## ⚠️ Notas Importantes

1. **Contraseñas**: Todos los usuarios usan `admin123` - cambiar en producción
2. **Permisos**: El script requiere permisos sudo
3. **PostgreSQL**: Debe estar instalado y corriendo
4. **Backup**: El script elimina la BD existente - hacer backup primero

## ✅ Verificación

Después de ejecutar el script:

```bash
# Ver usuarios
psql -U pooconphp_user -d pooconphp -h 127.0.0.1 -c "SELECT * FROM users;"

# Ver tipos de documento
psql -U pooconphp_user -d pooconphp -h 127.0.0.1 -c "SELECT * FROM tipo_documento;"

# Ver municipios de Nariño
psql -U pooconphp_user -d pooconphp -h 127.0.0.1 -c "SELECT COUNT(*) FROM ciudades WHERE departamento = 'Nariño';"

# Ver barrios de Pasto
psql -U pooconphp_user -d pooconphp -h 127.0.0.1 -c "SELECT COUNT(*) FROM barrio;"
```

## 🎯 Contenido Total

- ✅ 20 tablas
- ✅ 4 usuarios del sistema
- ✅ 7 tipos de documento
- ✅ 19 EPS
- ✅ 26 municipios de Nariño
- ✅ 51 barrios de Pasto
- ✅ 1 perfil médico completo
