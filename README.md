# POO con PHP - Aplicativo Supabase

Aplicativo de ejemplo que demuestra Programación Orientada a Objetos en PHP conectado a Supabase.

## 🚀 Instalación

### Prerrequisitos
- XAMPP con PHP 7.4+
- Composer
- Cuenta en Supabase

### Pasos de instalación

1. **Clonar/descargar el proyecto** en `c:\xampp\htdocs\pooconphp`

2. **Instalar dependencias:**
   ```bash
   cd c:\xampp\htdocs\pooconphp
   composer install
   ```

3. **Configurar Supabase:**
   - Crear proyecto en [supabase.com](https://supabase.com)
   - Copiar URL y API Key
   - Verificar que el archivo `.env` tenga las credenciales correctas

4. **Crear tablas en Supabase:**
   - Ir al SQL Editor en Supabase
   - Ejecutar el contenido de `crear_tablas.sql`

5. **Probar la aplicación:**
   - Iniciar XAMPP
   - Visitar: `http://localhost/pooconphp`

## 📋 Estructura del Proyecto

```
pooconphp/
├── src/
│   └── SupabaseClient.php    # Cliente principal para Supabase
├── vendor/                   # Dependencias de Composer
├── .env                      # Variables de entorno
├── composer.json            # Configuración de dependencias
├── index.php               # Página principal
├── test_conexion.php       # Prueba de conexión
├── test_paciente.php       # Prueba de pacientes
├── check_tables.php        # Verificación de tablas
└── crear_tablas.sql        # Script para crear tablas
```

## 🔧 Uso

### Cliente Supabase

```php
use App\SupabaseClient;

$supabase = new SupabaseClient($url, $key);

// Consultar datos
$pacientes = $supabase->select('pacientes', '*', 'documento_id=eq.1000000246');

// Insertar datos
$nuevo_paciente = [
    'documento_id' => '1234567890',
    'primer_nombre' => 'Juan',
    'primer_apellido' => 'Pérez'
];
$resultado = $supabase->insert('pacientes', $nuevo_paciente);

// Actualizar datos
$supabase->update('pacientes', ['telefono' => '3001234567'], 'id_paciente=eq.1');

// Eliminar datos
$supabase->delete('pacientes', 'id_paciente=eq.1');
```

## 🛠️ Solución de Problemas

### Error: "Class not found"
- Ejecutar: `composer install`
- Verificar que existe `vendor/autoload.php`

### Error de conexión a Supabase
- Verificar credenciales en `.env`
- Comprobar que las tablas existen
- Revisar políticas RLS en Supabase

### Tabla no encontrada
- Ejecutar el script `crear_tablas.sql` en Supabase
- Verificar que las políticas RLS permiten acceso

## 📚 Tecnologías Utilizadas

- **PHP 7.4+** - Lenguaje principal
- **Supabase** - Base de datos y API
- **Composer** - Gestión de dependencias
- **Guzzle HTTP** - Cliente HTTP
- **vlucas/phpdotenv** - Variables de entorno