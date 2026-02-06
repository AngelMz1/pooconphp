#!/bin/bash
# Script automatizado para agregar protección a vistas críticas

VIEWS_DIR="/var/www/html/pooconphp/views"
LOG_FILE="/tmp/security_fixes.log"

echo "=== APLICANDO CORRECCIONES DE SEGURIDAD FASE 1 ===" | tee "$LOG_FILE"
echo "Fecha: $(date)" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# Función para agregar auth_helper si no existe
ensure_auth_helper() {
    local file=$1
    if ! grep -q "auth_helper.php" "$file"; then
        # Insertar después del primer require o al inicio
        sed -i "1a require_once __DIR__ . '/../includes/auth_helper.php';" "$file"
        echo "  → Agregado auth_helper.php" | tee -a "$LOG_FILE"
    fi
}

# Función para agregar protección
add_protection() {
    local file="$VIEWS_DIR/$1"
    local permission=$2
    local description=$3
    
    if [ ! -f "$file" ]; then
        echo "❌ $1 - Archivo no existe" | tee -a "$LOG_FILE"
        return 1
    fi
    
    # Crear backup
    cp "$file" "${file}.backup_$(date +%Y%m%d)"
    
    # Verificar si ya tiene protección
    if grep -q "requirePermission\|requireRole\|hasPermission.*die" "$file"; then
        echo "⏭️  $1 - Ya tiene protección" | tee -a "$LOG_FILE"
        return 0
    fi
    
    # Asegurar que tiene auth_helper
    ensure_auth_helper "$file"
    
    # Buscar línea después de requires
    local insert_line=$(grep -n "require_once.*auth_helper" "$file" | head -1 | cut -d: -f1)
    
    if [ -z "$insert_line" ]; then
        insert_line=2
    fi
    
    # Insertar protección
    sed -i "${insert_line}a\\
\\
// $description\\
requirePermission('$permission');" "$file"
    
    echo "✅ $1 → $permission" | tee -a "$LOG_FILE"
}

# FASE 1: ARCHIVOS CRÍTICOS (gestión de datos médicos)
echo "--- FASE 1: Protecciones Críticas ---" | tee -a "$LOG_FILE"

add_protection "gestionar_pacientes.php" "gestionar_pacientes" "Verificar permiso para gestionar pacientes"
add_protection "gestionar_pacientes_completo.php" "gestionar_pacientes" "Verificar permiso para gestionar pacientes"
add_protection "historias_clinicas.php" "ver_historia" "Verificar permiso para ver historias clínicas"
add_protection "listar_historias.php" "ver_historia" "Verificar permiso para ver historias clínicas"
add_protection "ver_historia.php" "ver_historia" "Verificar permiso para ver historia clínica"
add_protection "ver_paciente.php" "ver_pacientes" "Verificar permiso para ver pacientes"
add_protection "ver_paciente_completo.php" "ver_pacientes" "Verificar permiso para ver pacientes"
add_protection "nueva_consulta.php" "atender_consulta" "Verificar permiso para atender consultas"
add_protection "registrar_examen.php" "atender_consulta" "Verificar permiso para atender consultas"
add_protection "registrar_ordenes.php" "solicitar_procedimientos" "Verificar permiso para solicitar procedimientos"

echo "" | tee -a "$LOG_FILE"
echo "--- Protección Condicional ---" | tee -a "$LOG_FILE"

# listar_consultas.php necesita verificación condicional
LISTAR_CONSULTAS="$VIEWS_DIR/listar_consultas.php"
if [ -f "$LISTAR_CONSULTAS" ] && ! grep -q "hasPermission.*ver_historia" "$LISTAR_CONSULTAS"; then
    cp "$LISTAR_CONSULTAS" "${LISTAR_CONSULTAS}.backup_$(date +%Y%m%d)"
    ensure_auth_helper "$LISTAR_CONSULTAS"
    
    insert_line=$(grep -n "require_once.*auth_helper" "$LISTAR_CONSULTAS" | head -1 | cut -d: -f1)
    [ -z "$insert_line" ] && insert_line=2
    
    sed -i "${insert_line}a\\
\\
// Verificar permiso para ver consultas/historias\\
if (!hasPermission('atender_consulta') && !hasPermission('ver_historia')) {\\
    die('<h1>Acceso Denegado</h1><p>No tiene permisos para ver consultas.</p>');\\
}" "$LISTAR_CONSULTAS"
    
    echo "✅ listar_consultas.php → atender_consulta OR ver_historia" | tee -a "$LOG_FILE"
fi

# buscar_cie10.php
BUSCAR_CIE="$VIEWS_DIR/buscar_cie10.php"
if [ -f "$BUSCAR_CIE" ] && ! grep -q "hasPermission" "$BUSCAR_CIE"; then
    cp "$BUSCAR_CIE" "${BUSCAR_CIE}.backup_$(date +%Y%m%d)"
    ensure_auth_helper "$BUSCAR_CIE"
    
    insert_line=$(grep -n "require_once.*auth_helper" "$BUSCAR_CIE" | head -1 | cut -d: -f1)
    [ -z "$insert_line" ] && insert_line=2
    
    sed -i "${insert_line}a\\
\\
// Verificar permiso para usar búsqueda CIE-10\\
if (!hasPermission('atender_consulta') && !hasPermission('ver_historia')) {\\
    die('<h1>Acceso Denegado</h1><p>No tiene permisos para buscar códigos CIE-10.</p>');\\
}" "$BUSCAR_CIE"
    
    echo "✅ buscar_cie10.php → atender_consulta OR ver_historia" | tee -a "$LOG_FILE"
fi

echo "" | tee -a "$LOG_FILE"
echo "=== COMPLETADO ===" | tee -a "$LOG_FILE"
echo "Backups guardados en: ${VIEWS_DIR}/*.backup_*" | tee -a "$LOG_FILE"
echo "Log completo en: $LOG_FILE" | tee -a "$LOG_FILE"

# Contar archivos protegidos
total_protected=$(grep -l "requirePermission\|requireRole" "$VIEWS_DIR"/*.php 2>/dev/null | wc -l)
echo "" | tee -a "$LOG_FILE"
echo "📊 Resumen: $total_protected archivos ahora tienen protección" | tee -a "$LOG_FILE"
