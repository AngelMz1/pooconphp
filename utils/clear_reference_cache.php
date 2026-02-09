<?php
/**
 * Script para limpiar el caché de datos de referencia
 * Ejecutar cuando se actualicen ciudades, barrios u otros datos de catálogo
 */

session_start();

// Limpiar caché de datos de referencia
if (isset($_SESSION['ref_cache'])) {
    unset($_SESSION['ref_cache']);
    echo "✅ Caché de datos de referencia limpiado exitosamente.\n";
    echo "Por favor, recarga la página para ver los nuevos datos.\n";
} else {
    echo "ℹ️ No había caché para limpiar.\n";
}

// Opcional: Limpiar toda la sesión (comentado por seguridad)
// session_destroy();
// echo "✅ Sesión completa destruida.\n";
