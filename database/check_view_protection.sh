#!/bin/bash
# Script para verificar protección de acceso en vistas PHP

echo "=== ANÁLISIS DE PROTECCIÓN DE VISTAS ==="
echo ""
echo "Formato: [✓/✗] archivo - (requireRole/hasPermission/sin_proteccion)"
echo ""

cd /var/www/html/pooconphp/views

for file in *.php; do
    if [ "$file" = "login.php" ] || [ "$file" = "logout.php" ]; then
        echo "[~] $file - (página pública)"
        continue
    fi
    
    has_require=$(grep -l "requireRole" "$file" 2>/dev/null)
    has_permission=$(grep -l "hasPermission" "$file" 2>/dev/null)
    has_require_login=$(grep -l "requireLogin" "$file" 2>/dev/null)
    
    if [ -n "$has_require" ]; then
        role=$(grep "requireRole" "$file" | head -1)
        echo "[R] $file - requireRole"
    elif [ -n "$has_permission" ]; then
        echo "[P] $file - hasPermission"
    elif [ -n "$has_require_login" ]; then
        echo "[L] $file - requireLogin (solo autenticación)"
    else
        echo "[✗] $file - SIN PROTECCIÓN"
    fi
done

echo ""
echo "Leyenda:"
echo "  [R] = Protegido con requireRole (hardcoded)"
echo "  [P] = Protegido con hasPermission (correcto)"
echo "  [L] = Solo requireLogin (cualquier usuario autenticado)"
echo "  [✗] = Sin protección de acceso"
echo "  [~] = Página pública"
