#!/bin/bash

# ============================================
# Script de Configuración PostgreSQL Local
# Sistema de Gestión Médica
# ============================================

# Colores para mensajes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Configuración de PostgreSQL Local${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Configuración de base de datos
DB_NAME="pooconphp_local"
DB_USER="pooconphp_user"
DB_PASSWORD="pooconphp_2024!"
DB_HOST="127.0.0.1"
DB_PORT="5432"

# Archivo SQL actualizado
SQL_FILE="$(dirname "$0")/setup_database_complete.sql"

# 1. Verificar que PostgreSQL esté corriendo
echo -e "${BLUE}[1/5]${NC} Verificando PostgreSQL..."
if ! systemctl is-active --quiet postgresql; then
    echo -e "${RED}PostgreSQL no está corriendo. Iniciando...${NC}"
    sudo systemctl start postgresql
    if [ $? -ne 0 ]; then
        echo -e "${RED}Error: No se pudo iniciar PostgreSQL${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}✓ PostgreSQL está corriendo${NC}\n"

# 2. Eliminar base de datos existente (si existe)
echo -e "${BLUE}[2/5]${NC} Eliminando base de datos existente..."
sudo -u postgres psql -c "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null
echo -e "${GREEN}✓ Base de datos anterior eliminada${NC}\n"

# 3. Crear base de datos
echo -e "${BLUE}[3/5]${NC} Creando base de datos '$DB_NAME'..."
sudo -u postgres psql -c "CREATE DATABASE $DB_NAME;" 2>/dev/null
if [ $? -ne 0 ]; then
    echo -e "${RED}Error: No se pudo crear la base de datos${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Base de datos creada${NC}\n"

# 4. Crear usuario y otorgar privilegios
echo -e "${BLUE}[4/5]${NC} Configurando usuario '$DB_USER'..."
sudo -u postgres psql -c "DROP USER IF EXISTS $DB_USER;" 2>/dev/null
sudo -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';" 2>/dev/null
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;" 2>/dev/null
sudo -u postgres psql -d $DB_NAME -c "GRANT ALL ON SCHEMA public TO $DB_USER;" 2>/dev/null
sudo -u postgres psql -d $DB_NAME -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO $DB_USER;" 2>/dev/null
echo -e "${GREEN}✓ Usuario configurado${NC}\n"

# 5. Ejecutar script SQL
echo -e "${BLUE}[5/5]${NC} Ejecutando script de configuración completa..."
if [ ! -f "$SQL_FILE" ]; then
    echo -e "${RED}Error: No se encontró el archivo $SQL_FILE${NC}"
    exit 1
fi

sudo -u postgres psql -d $DB_NAME -f "$SQL_FILE"
if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Fallo al ejecutar el script SQL${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Script ejecutado exitosamente${NC}\n"

# Resumen
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✓ CONFIGURACIÓN COMPLETADA${NC}"
echo -e "${BLUE}========================================${NC}\n"

echo "Detalles de conexión:"
echo "  Host: $DB_HOST"
echo "  Puerto: $DB_PORT"
echo "  Base de datos: $DB_NAME"
echo "  Usuario: $DB_USER"
echo "  Contraseña: $DB_PASSWORD"
echo ""
echo "Usuarios del sistema:"
echo "  - admin / admin123 (Administrador)"
echo "  - medico / admin123 (Médico)"
echo "  - cajero / admin123 (Cajero)"
echo "  - facturador / admin123 (Facturador)"
echo ""
echo "Datos geográficos:"
echo "  - 26 municipios de Nariño"
echo "  - 51 barrios de Pasto"
echo ""
echo -e "${GREEN}El sistema está listo para usar${NC}\n"
