# Guia De Hosting

Esta guia resume los pasos para subir VeciReport a un hosting con PHP, MySQL y Apache/cPanel.

## Recomendacion Para Demo Gratuita

Para el demo de portafolio se recomienda usar InfinityFree porque permite PHP, MySQL, phpMyAdmin, `.htaccess` y subdominio gratuito sin anuncios insertados. Es suficiente para mostrar VeciReport a reclutadores o contactos de LinkedIn.

En InfinityFree la base de datos se crea desde el panel y normalmente tendra un nombre propio del hosting. Por eso, para importar en phpMyAdmin usa los archivos preparados para hosting:

```text
database/hosting_schema.sql
database/demo_seed_hosting.sql
```

Estos archivos no incluyen `CREATE DATABASE` ni `USE vecireport`, asi que se importan directamente dentro de la base de datos seleccionada en phpMyAdmin.

## Requisitos

- PHP 8.1 o superior.
- Extension PDO MySQL habilitada.
- Extension `fileinfo` habilitada para validar uploads.
- Extension `mbstring` habilitada para recortes de texto en vistas.
- MySQL 8.0 o MariaDB compatible.
- Apache con soporte para `.htaccess`.
- Permisos de escritura en:

```text
uploads/comprobantes/
uploads/reportes/
```

## Archivos De Configuracion

En produccion no se deben subir credenciales reales al repositorio.

Crear estos archivos directamente en el hosting:

```text
App/config/database.php
App/config/app.php
```

Puedes partir de:

```text
App/config/database.example.php
App/config/app.example.php
```

Ejemplo de `App/config/app.php` si el proyecto vive en la raiz del dominio:

```php
<?php
define('APP_ENV', 'production');
define('APP_DEMO_MODE', false);
define('APP_DEMO_PROTECTED_EMAILS', 'admin@vecireport.com,vecino.demo@vecireport.com');
define('APP_BASE_URL', '');
```

Ejemplo si vive en una subcarpeta:

```php
<?php
define('APP_ENV', 'production');
define('APP_DEMO_MODE', false);
define('APP_DEMO_PROTECTED_EMAILS', 'admin@vecireport.com,vecino.demo@vecireport.com');
define('APP_BASE_URL', '/VeciReport');
```

## Base De Datos

1. Crear una base de datos en el panel del hosting.
2. Crear un usuario MySQL con permisos sobre esa base.
3. En phpMyAdmin, seleccionar la base de datos creada por el hosting.
4. Para InfinityFree u otro hosting compartido, importar:

```text
database/hosting_schema.sql
```

Para XAMPP/local tambien puedes usar:

```text
database/vecireport.sql
```

Si ya existe una base previa, ejecutar la migracion incremental:

```text
database/migrations/2026_06_21_fraccionamientos.sql
database/migrations/2026_06_21_mapa_fraccionamientos.sql
database/migrations/2026_06_21_leaflet_coords.sql
```

Para demo publica, ejecutar tambien:

```text
database/demo_seed_hosting.sql
```

En XAMPP/local tambien puedes usar:

```text
database/demo_seed.sql
```

5. Ajustar `App/config/database.php` con las credenciales reales del hosting.
6. Configurar `App/config/app.php` segun `docs/demo.md` si sera una demo publica.
7. Cambiar la contrasena inicial del admin despues del primer acceso si no sera demo.

## Archivos A Subir

Subir el proyecto sin estos archivos locales:

```text
App/config/database.php
App/config/app.php
uploads/comprobantes/*
uploads/reportes/*
*.log
```

Si el hosting usa Apache, puedes copiar `.htaccess.example` como `.htaccess` en la raiz del proyecto.

## Verificacion Rapida

Abrir estas rutas:

```text
/index.php
/login.php
/registro.php
```

Luego probar:

1. Login admin.
2. Registro de vecino con comprobante.
3. Aprobacion del vecino.
4. Creacion de reporte.
5. Asignacion de trabajador.
6. Logout.

## Notas De Seguridad

- No subir datos reales de vecinos ni comprobantes.
- No dejar `APP_ENV` como `local` en produccion.
- Verificar que `uploads/.htaccess` este presente.
- Confirmar que `App/config/database.php` no este en Git.
- Usar contrasenas fuertes para el usuario MySQL y el admin del sistema.

Para modo demo publico, revisar tambien `docs/demo.md`.
