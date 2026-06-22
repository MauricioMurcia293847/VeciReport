# Demo Publica Controlada

Esta guia deja VeciReport listo para una demo publica de portafolio sin exponer datos reales.

## Credenciales Demo

| Rol | Correo | Password |
|---|---|---|
| Admin | `admin@vecireport.com` | `VeciAdmin!2026#Demo` |
| Vecino | `vecino.demo@vecireport.com` | `VecinoDemo!2026#Ok` |

En la base local actual puede existir otra contrasena de admin si fue cambiada manualmente. Para una instalacion fresca desde `database/vecireport.sql`, la contrasena inicial del admin es `VeciAdmin!2026#Demo`.

## Activar Modo Demo

Crear `App/config/app.php` en el hosting:

```php
<?php
define('APP_ENV', 'production');
define('APP_DEMO_MODE', true);
define('APP_DEMO_PROTECTED_EMAILS', 'admin@vecireport.com,vecino.demo@vecireport.com');
define('APP_BASE_URL', '');
```

Si el proyecto vive en una subcarpeta, usar:

```php
define('APP_BASE_URL', '/VeciReport');
```

## Preparar Datos Demo

Para una base nueva, importar:

```text
database/vecireport.sql
```

Para una base existente, ejecutar en este orden:

```text
database/migrations/2026_06_21_fraccionamientos.sql
database/migrations/2026_06_21_mapa_fraccionamientos.sql
database/migrations/2026_06_21_leaflet_coords.sql
database/demo_seed.sql
```

`database/demo_seed.sql` se puede volver a ejecutar para restaurar el vecino demo y sus reportes de ejemplo.

Si la base ya esta desplegada en InfinityFree y solo quieres actualizar las contrasenas demo, ejecutar:

```text
database/update_demo_passwords_hosting.sql
```

## Mapa Real Del Fraccionamiento

VeciReport usa Leaflet con OpenStreetMap para mostrar el mapa real. El campo `mapa_poligono` guarda un arreglo JSON de coordenadas reales:

```json
[[31.741150,-106.489700],[31.742050,-106.481900],[31.738700,-106.478900]]
```

Cada punto tiene este formato:

```text
[latitud, longitud]
```

Durante el registro, el vecino hace clic dentro del poligono. El navegador guarda `ubicacion_lat` y `ubicacion_lng`, y PHP vuelve a validar que ese punto este dentro del poligono antes de crear la cuenta.

## Protecciones Del Modo Demo

Cuando `APP_DEMO_MODE` esta activo:

- El login muestra botones para rellenar credenciales demo.
- Las cuentas listadas en `APP_DEMO_PROTECTED_EMAILS` no se pueden bloquear.
- El perfil del vecino demo no se puede modificar.
- Los visitantes aun pueden probar registro, reportes y navegacion normal.

## Checklist Antes De Publicar

1. Confirmar que `App/config/database.php` no esta versionado.
2. Confirmar que `App/config/app.php` existe solo en el hosting.
3. Confirmar que `APP_DEMO_MODE` esta en `true` para demo y `false` para produccion real.
4. Ejecutar `database/demo_seed.sql`.
5. Probar login admin y vecino demo.
6. Verificar que no se muestren errores PHP.
7. Tomar capturas finales desde `docs/screenshots/README.md`.
8. Probar el enlace publico: `https://vecireport.gt.tc/`.
