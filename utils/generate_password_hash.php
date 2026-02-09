<?php
/**
 * Generador de Hash de Contraseña
 * Usar cuando necesites crear un nuevo hash para usuarios
 */

// Contraseña a hashear
$password = $argv[1] ?? 'admin123';

// Generar hash
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "========================================\n";
echo "GENERADOR DE HASH DE CONTRASEÑA\n";
echo "========================================\n\n";
echo "Contraseña: " . $password . "\n";
echo "Hash generado:\n";
echo $hash . "\n\n";
echo "========================================\n";
echo "Usar en SQL así:\n";
echo "UPDATE users SET password_hash = '" . $hash . "' WHERE username = 'admin';\n";
echo "========================================\n";
