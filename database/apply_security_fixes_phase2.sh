#!/bin/bash
# Script para FASE 2: Billing y Print views

VIEWS_DIR="/var/www/html/pooconphp/views"
LOG_FILE="/tmp/security_fixes_phase2.log"

echo "=== APLICANDO CORRECCIONES DE SEGURIDAD FASE 2 ===" | tee "$LOG_FILE"
echo "Fecha: $(date)" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# Función para agregar auth_helper si no existe
ensure_auth_helper() {
    local file=$1
    if ! grep -q "auth_helper.php" "$file"; then
        sed -i "1a require_once __DIR__ . '/../includes/auth_helper.php';" "$file"
        echo "  → Agregado auth_helper.php" | tee -a "$LOG_FILE"
    fi
}

# Función para reemplazar requir eLogin con requirePermission
upgrade_protection() {
    local file="$VIEWS_DIR/$1"
    local permission=$2
    local description=$3
    
    if [ ! -f "$file" ]; then
        echo "❌ $1 - Archivo no existe" | tee -a "$LOG_FILE"
        return 1
    fi
    
    # Crear backup
    cp "$file" "${file}.backup_$(date +%Y%m%d)"
    
    # Verificar si ya tiene requirePermission
    if grep -q "requirePermission('$permission')" "$file"; then
        echo "⏭️  $1 - Ya tiene requirePermission" | tee -a "$LOG_FILE"
        return 0
    fi
    
    # Asegurar auth_helper
    ensure_auth_helper "$file"
    
    # Reemplazar requireLogin() con requirePermission()
    if grep -q "requireLogin()" "$file"; then
        sed -i "s/requireLogin();/requirePermission('$permission'); \\/\\/ $description/" "$file"
        echo "✅ $1 → Upgraded requireLogin to requirePermission('$permission')" | tee -a "$LOG_FILE"
    else
        # Insertar después de auth_helper
        insert_line=$(grep -n "require_once.*auth_helper" "$file" | head -1 | cut -d: -f1)
        [ -z "$insert_line" ] && insert_line=2
        
        sed -i "${insert_line}a\\
\\
// $description\\
requirePermission('$permission');" "$file"
        
        echo "✅ $1 → Added requirePermission('$permission')" | tee -a "$LOG_FILE"
    fi
}

echo "--- FASE 2: Billing & Payment Views ---" | tee -a "$LOG_FILE"

upgrade_protection "facturar_paciente.php" "generar_factura" "Verificar permiso para generar facturas"
upgrade_protection "listar_facturas.php" "ver_facturas" "Verificar permiso para ver facturas"
upgrade_protection "registrar_pago.php" "registrar_pago" "Verificar permiso para registrar pagos"
upgrade_protection "resumen_facturacion.php" "ver_facturas" "Verificar permiso para ver resumen de facturación"

echo "" | tee -a "$LOG_FILE"
echo "--- FASE 2: Print & Document Views ---" | tee -a "$LOG_FILE"

upgrade_protection "imprimir_factura.php" "ver_facturas" "Verificar permiso para imprimir facturas"
upgrade_protection "imprimir_formula.php" "ver_historia" "Verificar permiso para ver historias (imprimir fórmula)"
upgrade_protection "imprimir_historia.php" "ver_historia" "Verificar permiso para ver historias (imprimir)"
upgrade_protection "imprimir_solicitud.php" "ver_historia" "Verificar permiso para ver historias (imprimir solicitud)"

echo "" | tee -a "$LOG_FILE"
echo "--- FASE 2: Other Views ---" | tee -a "$LOG_FILE"

upgrade_protection "listar_pacientes.php" "ver_pacientes" "Verificar permiso para ver lista de pacientes"
upgrade_protection "ver_consulta.php" "ver_historia" "Verificar permiso para ver consultas"
upgrade_protection "sincronizar.php" "configurar_sistema" "Verificar permiso para configurar sistema"

echo "" | tee -a "$LOG_FILE"
echo "=== COMPLETADO ===" | tee -a "$LOG_FILE"
echo "Backups guardados en: ${VIEWS_DIR}/*.backup_*" | tee -a "$LOG_FILE"
echo "Log completo en: $LOG_FILE" | tee -a "$LOG_FILE"

# Contar archivos protegidos
total_protected=$(grep -l "requirePermission\|requireRole" "$VIEWS_DIR"/*.php 2>/dev/null | wc -l)
echo "" | tee -a "$LOG_FILE"
echo "📊 Resumen Total: $total_protected archivos tienen protección" | tee -a "$LOG_FILE"
