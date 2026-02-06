# Políticas de Seguridad y Privacidad de Datos
**Sistema de Gestión Médica (POO con PHP)**

Este documento detalla los controles de seguridad implementados para proteger la integridad, confidencialidad y disponibilidad de los datos.

## 1. Control de Acceso y Autenticación
### Política
El acceso al sistema está estrictamente restringido a usuarios autorizados con credenciales válidas.
### Implementación (Verificada)
- **Hashing de Contraseñas**: Las contraseñas se almacenan mediante `bcrypt` (vía `password_hash/password_verify`). Nunca se guardan en texto plano.
- **Gestión de Sesiones**: Se utiliza `session_start()` de PHP. Si no existe una sesión válida (`user_id`), el sistema redirige automáticamente al Login (`requireLogin` en `auth_helper.php`).
- **Roles y Permisos**: Implementación de RBAC (Role-Based Access Control).
  - Admin: Acceso total.
  - Médico: Acceso restringido a sus citas y pacientes.
  - Cajero: Acceso a gestión de citas y pagos.

## 2. Protección de Datos (Seguridad de la Información)
### Política
Los datos sensibles (historias clínicas, documentos de identidad) deben protegerse contra accesos no autorizados y fugas.
### Implementación (Verificada)
- **Offline-First Seguro**: La base de datos local (`pooconphp_local`) reside en el servidor/PC del usuario, reduciendo la exposición a internet directa.
- **Sincronización Controlada**: La subida de datos a la nube (Supabase) solo ocurre bajo demanda explícita del administrador (Queue-based replication).
- **Prevención de Fugas en Listados**:
  - Los listados de pacientes e historias clínicas **NO** cargan datos por defecto al abrir la página.
  - Se requiere que el usuario introduzca un término de búsqueda para visualizar registros, minimizando la exposición visual accidental ("Safety by Default").

## 3. Seguridad en el Desarrollo (Code Security)
### Política
El código debe seguir prácticas seguras para prevenir vulnerabilidades comunes (OWASP Top 10).
### Implementación (Verificada)
- **SQL Injection**: Uso estricto de **Prepared Statements** (PDO) en `LocalPostgresAdapter.php`. No se concatenan variables directamente en las consultas SQL.
- **Cross-Site Scripting (XSS)**: Sanitización de salida mediante `htmlspecialchars()` implementada en `Validator.php` y en las vistas críticas.
- **Validación de Entradas**: La clase `Validator.php` aplica reglas estrictas (longitud, tipo de dato, formato de email/teléfono) antes de procesar cualquier formulario.

## 4. Auditoría y Trazabilidad
### Política
Todas las modificaciones críticas de datos deben ser registradas.
### Implementación (Verificada)
- **Cola de Sincronización (`sync_queue`)**: Actúa como un log de auditoría inmutable localmente, registrando cada creación, edición o eliminación (`INSERT`, `UPDATE`, `DELETE`), la fecha y el contenido afectado.

## Estado de Cumplimiento
| Control | Estado | Observación |
| :--- | :---: | :--- |
| Encriptación de Contraseñas | ✅ CUMPLE | Bcrypt activo. |
| Protección contra SQL Injection | ✅ CUMPLE | PDO Prepared Statements en uso. |
| Protección contra XSS | ✅ CUMPLE | Sanitización en vistas. |
| Control de Acceso por Roles | ✅ CUMPLE | Middleware de roles activo. |
| Privacidad en Listados | ✅ CUMPLE | Carga diferida (Lazy Loading) por búsqueda. |
| Respaldo de Datos | ✅ CUMPLE | Sincronización Cloud disponible. |

---
*Última Auditoría: 04 de Febrero de 2026*
*Auditado por: Asistente de IA (Antigravity)*
