# VeciReport — README 

## Contexto del proyecto

Sistema web de gestión de incidencias vecinales para un fraccionamiento. Permite a los vecinos reportar problemas (luz, agua, trabajadores, otros) y al administrador (guardia) gestionarlos y asignar trabajadores.

Proyecto académico universitario — carrera: Desarrollo de Software Multiplataforma (DMS52), Universidad Tecnológica de Ciudad Juárez.

---

## Stack tecnológico

- **Frontend:** HTML5, CSS3, JavaScript vanilla
- **Backend:** PHP 8.2 (sin framework)
- **Base de datos:** MySQL 8.0 (puerto 3306, servidor independiente — NO el MySQL de XAMPP)
- **Servidor local:** Apache via XAMPP (solo Apache, MySQL corre independiente)
- **Ruta del proyecto:** `C:\xampp\htdocs\VeciReport\`
- **URL base:** `http://localhost/VeciReport/`

---

## Credenciales de base de datos

```php
DB_HOST = 'localhost'
DB_PORT = '3306'
DB_NAME = 'vecireport'
DB_USER = 'root'
DB_PASS = 'Murciaaquino1010'
```

---

## Estado del sistema

El sistema está **completamente convertido a PHP**. Todas las páginas internas leen datos reales de la BD y están protegidas por sesión.

### Páginas estáticas (HTML — sin conversión pendiente)

| Archivo | Rol |
|---------|-----|
| `index.html` | Landing page pública |
| `login.html` | Formulario de login (envía a `UsuarioController.php`) |
| `registro.html` | Formulario de registro de vecinos |

### Páginas PHP activas

| Archivo | Rol | Acceso |
|---------|-----|--------|
| `dashboard.php` | Panel del vecino con KPIs y últimos reportes | Vecino |
| `reporte.php` | Crear nuevo reporte con foto opcional | Vecino |
| `mis-reportes.php` | Historial de reportes del vecino con filtros | Vecino |
| `perfil.php` | Datos del perfil y estadísticas del vecino | Vecino |
| `directorio.php` | Directorio de trabajadores (solo ver y llamar) | Vecino |
| `admin.php` | Panel resumen: KPIs, reportes activos, trabajadores, vecinos recientes | Admin |
| `admin-reportes.php` | Todos los reportes con filtros, asignación y marcar atendido | Admin |
| `admin-vecinos.php` | Lista de vecinos con opciones de aprobar y bloquear | Admin |
| `admin-bitacora.php` | Bitácora completa de acciones del sistema | Admin |
| `directorio-admin.php` | Directorio de trabajadores con modal de asignación a reportes | Admin |

### Carpeta backup/

Contiene las versiones HTML estáticas originales de todas las páginas ya convertidas a PHP. Solo para referencia, no se sirven en producción.

---

## Estructura de carpetas

```
VeciReport/
│
│── Páginas estáticas (HTML):
├── index.html                    ← Landing page pública
├── login.html                    ← Formulario de login
├── registro.html                 ← Formulario de registro
│
│── Páginas PHP (conectadas a BD y sesión):
├── dashboard.php                 ← Panel del vecino
├── reporte.php                   ← Crear nuevo reporte
├── mis-reportes.php              ← Historial de reportes del vecino
├── perfil.php                    ← Perfil del vecino
├── directorio.php                ← Directorio de trabajadores (vecino)
├── admin.php                     ← Panel resumen del administrador
├── admin-reportes.php            ← Todos los reportes (admin)
├── admin-vecinos.php             ← Gestión de vecinos (admin)
├── admin-bitacora.php            ← Bitácora del sistema (admin)
├── directorio-admin.php          ← Directorio con asignación (admin)
│
├── App/
│   ├── config/
│   │   └── database.php          ← Conexión PDO (función conectar())
│   ├── controllers/
│   │   ├── UsuarioController.php ← login, logout, aprobar, bloquear
│   │   └── ReporteController.php ← crear, atender, asignar
│   └── helpers/
│       └── auth.php              ← requiereLogin, requiereAdmin, requiereVecino, iniciarSesion, cerrarSesion
│
├── Carpeta CSS/
│   ├── style.css                 ← Variables globales, navbar, hero, cards, footer (tema claro)
│   ├── dashboard.css             ← Sidebar, topbar, KPIs, layout base de páginas internas
│   ├── admin.css                 ← Estilos del panel admin, badge Admin, sidebar 270px
│   ├── admin-reportes.css        ← Cards de reportes admin, filtros, vecinos, bitácora
│   ├── login.css                 ← Layout 2 columnas, showcase decorativo
│   ├── registro.css              ← Formulario de registro, drag&drop comprobante
│   ├── reporte.css               ← Cards de categoría, selector tipo, preview, modal éxito
│   ├── mis-reportes.css          ← Cards mobile-first, grid 2 col desktop / 1 móvil
│   ├── directorio.css            ← Grid trabajadores, modal asignación
│   └── perfil.css                ← Layout perfil, stats, accesos
│
├── Carpeta JS/
│   └── funciones.js              ← toggleSidebar, mostrarToast, cerrarModal, validarCorreo
│
├── uploads/
│   ├── .htaccess                 ← Deniega acceso HTTP directo a todos los archivos subidos
│   ├── comprobantes/             ← Comprobantes de domicilio del registro
│   └── reportes/                 ← Fotos de reportes (opcional, jpg/jpeg/png, máx 5 MB)
│
├── backup/                       ← Versiones HTML originales de todas las páginas convertidas
│   ├── admin.html, admin-reportes.html, admin-vecinos.html, admin-bitacora.html
│   ├── dashboard.html, reporte.html, mis-reportes.html, perfil.html
│   └── directorio.html, directorio-admin.html
│
├── database/
│   └── vecireport.sql            ← Esquema completo + datos iniciales (admin + 8 trabajadores)
│
├── .htaccess                     ← Procesa login.html y registro.html como PHP (soporte CSRF)
└── test_conexion.php             ← Eliminar antes de producción
```

---

## Base de datos — esquema

```sql
-- Usuarios (vecinos y admin)
usuarios (id, nombre, apellidos, correo, password_hash, rol ENUM('vecino','admin'), estado ENUM('pendiente','activo','bloqueado'), created_at, updated_at)

-- Perfil extendido del vecino
vecinos (id, usuario_id FK, num_calle, num_casa, color_casa, comprobante_path)

-- Trabajadores (sin login, solo datos gestionados por el admin)
trabajadores (id, nombre, apellidos, especialidad ENUM('electricista','plomero','albanil','jardinero','general'), telefono, disponibilidad ENUM('disponible','ocupado'), created_at)

-- Reportes creados por vecinos
reportes (id, vecino_id FK, trabajador_id FK NULL, categoria ENUM('luz','agua','trabajadores','otros'), tipo ENUM('individual','colectivo'), descripcion, color_casa, num_casa, foto_path NULL, estado ENUM('pendiente','en_proceso','atendido'), created_at, updated_at)

-- Historial de asignaciones
asignaciones (id, reporte_id FK, trabajador_id FK, admin_id FK, notas NULL, assigned_at)

-- Bitácora de acciones del sistema
bitacora (id, usuario_id FK NULL, tipo_accion ENUM('login','logout','registro','reporte_creado','reporte_atendido','asignacion','vecino_aprobado','vecino_bloqueado','sistema'), descripcion, ip, created_at)
```

---

## Usuarios y datos en la BD

### Usuarios registrados

| id | nombre | correo | rol | estado |
|----|--------|--------|-----|--------|
| 1 | Guardia | admin@vecireport.com | admin | activo |
| 2 | Mauricio | murciamauricio921@gmail.com | vecino | activo |
| 3 | Mauricio | mauricioantoniomurchiaaquino@gmail.com | vecino | activo |

**Contraseña del usuario id=2:** hash de `password` generado via MySQL Workbench.

### Trabajadores registrados

8 trabajadores ficticios en la tabla `trabajadores`. Son datos fijos de prueba, no se crean desde ninguna vista del sistema (requiere inserción directa en BD o Workbench).

### Estado de los datos

- No hay reportes reales en la BD (solo los que se creen desde `reporte.php`)
- No hay vecinos en estado `pendiente` (el flujo de registro no inserta en BD — ver Problemas conocidos)
- La bitácora se puebla automáticamente con cada login, reporte, asignación y cambio de estado

---

## Lógica del sistema

### Roles

- **Vecino:** se registra → queda `pendiente` hasta aprobación del admin → puede hacer login → crea reportes → ve sus reportes → ve directorio de trabajadores (solo llamar)
- **Admin (Guardia):** ve todos los reportes, los filtra, asigna trabajadores, los marca como atendidos, aprueba/bloquea vecinos, ve bitácora
- **Trabajador:** sin login, solo datos gestionados por el admin

### Flujo de reporte

1. Vecino llena `reporte.php` → POST a `ReporteController.php?accion=crear`
2. Se valida, se inserta en `reportes` con `estado='pendiente'`, se registra en `bitacora`
3. Admin ve el reporte en `admin-reportes.php` o `admin.php`
4. Admin asigna trabajador → `estado='en_proceso'`, `trabajador_id` se completa, `trabajadores.disponibilidad='ocupado'`, se inserta en `asignaciones` y `bitacora`
5. Admin marca atendido → `estado='atendido'`, se registra en `bitacora`

### Flujo de login / sesión

- `login.html` → POST a `UsuarioController.php?accion=login`
- Si es admin → redirige a `admin-reportes.php`
- Si es vecino activo → redirige a `dashboard.php`
- Si es pendiente → redirige a `login.html?error=pendiente`
- Si es bloqueado → redirige a `login.html?error=bloqueado`
- Logout: `App/controllers/UsuarioController.php?accion=logout` → destruye sesión → redirige a `login.html`

---

## Controladores

### `UsuarioController.php`

Recibe `accion` por POST o GET.

| Acción | Método | Protección | Descripción |
|--------|--------|------------|-------------|
| `login` | POST | — | Valida credenciales, inicia sesión, registra en bitácora |
| `logout` | GET | `requiereLogin` | Destruye sesión y redirige a `login.html` |
| `aprobar` | POST | `requiereAdmin` | Cambia `estado` de vecino a `activo`, registra en bitácora |
| `bloquear` | POST | `requiereAdmin` | Cambia `estado` de vecino a `bloqueado`, registra en bitácora |

> **Sin implementar:** `registro` — no existe un `case 'registro'` en el switch. El formulario `registro.html` no llega a crear usuarios en la BD.

### `ReporteController.php`

Recibe `accion` por POST.

| Acción | Método | Protección | Descripción |
|--------|--------|------------|-------------|
| `crear` | POST | `requiereVecino` | Valida datos, inserta reporte, sube foto opcional, registra en bitácora |
| `atender` | POST | `requiereAdmin` | Cambia `estado` a `atendido`, registra en bitácora |
| `asignar` | POST | `requiereAdmin` | Asigna trabajador, cambia `estado` a `en_proceso`, marca trabajador como ocupado, inserta en `asignaciones` y `bitacora` |

---

## Helpers: auth.php

```php
requiereLogin()   // Redirige a login.html si no hay sesión
requiereAdmin()   // Redirige a dashboard.php si no es admin (⚠ redirect stale — ver Problemas conocidos)
requiereVecino()  // Redirige a admin.html si no es vecino (⚠ redirect stale — ver Problemas conocidos)
iniciarSesion($usuario)  // Guarda datos en $_SESSION, regenera ID de sesión
cerrarSesion()    // Destruye sesión, redirige a login.html
usuarioActual()   // Retorna $_SESSION['usuario_id'] o null
rolActual()       // Retorna $_SESSION['rol'] o null
generarCSRF()     // Genera token CSRF en $_SESSION['csrf_token'] (idempotente) y lo retorna
validarCSRF()     // Valida $_POST['csrf_token'] contra la sesión; responde 403 y termina si no coincide
```

---

## Seguridad implementada

### CSRF tokens

Todos los formularios POST del sistema están protegidos contra ataques CSRF.

**Mecanismo:**
- `generarCSRF()` crea un token de 64 caracteres hex (`bin2hex(random_bytes(32))`) y lo almacena en `$_SESSION['csrf_token']`.
- Cada formulario incluye `<input type="hidden" name="csrf_token" value="...">` con el token de la sesión actual.
- Los controladores llaman `validarCSRF()` como primera instrucción antes de procesar cualquier dato POST. Usa `hash_equals()` para la comparación (resistente a ataques de timing).

**Formularios protegidos:**

| Archivo | Acción |
|---------|--------|
| `login.html` | login |
| `registro.html` | registro |
| `reporte.php` | crear reporte |
| `perfil.php` | actualizar perfil |
| `admin-vecinos.php` | aprobar / bloquear / desbloquear vecino |
| `admin-reportes.php` | asignar trabajador / marcar atendido |
| `admin.php` | asignar trabajador / marcar atendido |
| `directorio-admin.php` | asignar trabajador (modal) |

**Nota para `login.html` y `registro.html`:** son archivos `.html` pero se procesan como PHP gracias al `.htaccess` raíz del proyecto (`SetHandler application/x-httpd-php`). Los nombres de archivo y todos los enlaces existentes permanecen sin cambios.

### Protección de archivos subidos

`uploads/.htaccess` deniega todo acceso HTTP directo a la carpeta de uploads:

```apache
Order Deny,Allow
Deny from all
```

Los archivos siguen siendo accesibles desde PHP (para lectura, descarga controlada, etc.) pero no pueden ser accedidos directamente por URL desde el navegador.

---

## CSS — tema claro

Todos los CSS fueron migrados de fondo oscuro (`#0D1B2A`) a tema claro profesional. Las variables globales están en `style.css`:

```css
--primary:       #F8F9FA;  /* fondo del body */
--primary-light: #F3F4F6;  /* secciones alternas, sidebar footer */
--accent:        #E85D26;  /* terracota — color principal de acento */
--text:          #1A1A2E;  /* texto principal */
--text-muted:    #6B7280;  /* texto secundario */
--card-bg:       #FFFFFF;  /* fondo de tarjetas */
--border:        #E5E7EB;  /* bordes */
```

El hero de `index.html` usa imagen de fondo de Unsplash + overlay `rgba(0,0,0,0.5)`, por lo que su texto es blanco (caso especial, no usa `var(--text)`).

---

## Problemas conocidos

### 1. Registro de vecinos no inserta en la BD

`UsuarioController.php` no tiene `case 'registro'` en su switch. El formulario `registro.html` envía los datos al controlador pero la acción no está implementada, por lo que los datos se descartan y se redirige al default (`login.html`).

**Consecuencia directa:** nunca hay vecinos en estado `pendiente`, por lo que los botones de aprobación en `admin-vecinos.php` nunca tienen qué aprobar.

**Solución pendiente:** agregar el `case 'registro'` en `UsuarioController.php` que inserte en `usuarios` (con `estado='pendiente'`) y en `vecinos`, y guarde el comprobante en `uploads/comprobantes/`.

### 2. La BD solo tiene datos de prueba fijos

- Solo 1 admin y 2 vecinos de prueba (estado `activo`)
- 8 trabajadores fijos insertados manualmente — no se pueden crear desde el sistema
- No existe ninguna pantalla para agregar o editar trabajadores
- Los reportes y la bitácora solo contienen lo que se genere durante las pruebas

### 3. Redirects stale en auth.php

`requiereAdmin()` redirige a `/VeciReport/dashboard.html` (ya no existe, ahora es `dashboard.php`).
`requiereVecino()` redirige a `/VeciReport/admin.html` (ya no existe, ahora es `admin.php`).

Si un vecino intenta acceder a una página de admin, o viceversa, recibirá un 404. No es un error bloqueante en uso normal pero debe corregirse.

### 4. Disponibilidad de trabajadores no se libera

Cuando un reporte pasa a `atendido`, `trabajadores.disponibilidad` NO se cambia de `ocupado` a `disponible` en `ReporteController.php::marcarAtendido()`. Con el tiempo, todos los trabajadores quedan marcados como ocupados aunque sus reportes estén resueltos.

---

## Pendientes en orden de prioridad

1. **Implementar `case 'registro'` en `UsuarioController.php`** — sin esto el sistema no admite nuevos vecinos
2. **Corregir redirects en `auth.php`** — `dashboard.html` → `dashboard.php` y `admin.html` → `admin.php`
3. **Liberar disponibilidad del trabajador al marcar reporte como atendido** — en `ReporteController.php::marcarAtendido()` agregar `UPDATE trabajadores SET disponibilidad='disponible' WHERE id = (SELECT trabajador_id FROM reportes WHERE id = ?)`
4. **Quitar el campo `detalle` del error en `database.php`** en producción

---

## Convenciones del proyecto

### PHP

- Conexión PDO via función `conectar()` en `database.php` (patrón singleton estático)
- Siempre `require_once` para incluir archivos
- Rutas de redirección absolutas con prefijo `/VeciReport/` (ej: `header('Location: /VeciReport/dashboard.php')`)
- Sanitizar todos los outputs con `htmlspecialchars()`
- Contraseñas con `password_hash()` / `password_verify()`
- Transacciones PDO en operaciones multi-tabla (`beginTransaction` / `commit` / `rollBack`)

### CSS

- Variables globales en `style.css` — nunca usar colores hardcoded en otros archivos
- Fuentes: Syne (`--font-display`, títulos) + DM Sans (`--font-body`, cuerpo)
- Clases BEM modificadas: `.sidebar__link--active`, `.kpi-card--total`, `.ar-card--atendido`
- Páginas admin llevan clase `admin-page` en `<body>` → amplía sidebar a 270px

### Sidebar admin — links correctos

Todos los PHP de admin usan estos hrefs en el sidebar:

```
admin.php              ← Resumen
admin-reportes.php     ← Todos los reportes
admin-vecinos.php      ← Vecinos
directorio-admin.php   ← Trabajadores
admin-bitacora.php     ← Bitácora
App/controllers/UsuarioController.php?accion=logout
```

### Notas de infraestructura

- El MySQL de XAMPP NO se usa — el puerto 3306 pertenece a MySQL Workbench / instalación independiente
- Solo Apache de XAMPP está corriendo
- La carpeta `App` tiene **A mayúscula** — crítico para los `require_once` en Windows
- `funciones.js` contiene helpers globales de JS usados en múltiples páginas (`toggleSidebar`, `mostrarToast`, `cerrarModal`, `validarCorreo`)
- Los trabajadores en la BD son datos ficticios, no personas reales
