# Guia De Capturas

Esta carpeta esta pensada para guardar capturas limpias del proyecto para GitHub y LinkedIn.

## Recomendaciones

- Usar datos demo, no datos personales reales.
- Tomar capturas en navegador de escritorio.
- Mantener la misma resolucion para todas las imagenes, por ejemplo `1440x900`.
- Evitar que se vean contrasenas, rutas locales sensibles o archivos privados.
- Verificar que no aparezcan errores PHP, warnings o datos de pruebas accidentales.

## Capturas Sugeridas

| Archivo | URL local sugerida | Objetivo |
|---|---|---|
| `01-landing.png` | `http://localhost/VeciReport/index.php` | Presentar el producto |
| `02-login.png` | `http://localhost/VeciReport/login.php` | Mostrar acceso y cuentas demo |
| `03-dashboard-vecino.png` | `http://localhost/VeciReport/dashboard.php` | Resumen del vecino |
| `04-nuevo-reporte.png` | `http://localhost/VeciReport/reporte.php` | Flujo de reporte |
| `05-mis-reportes.png` | `http://localhost/VeciReport/mis-reportes.php` | Historial del vecino |
| `06-perfil.png` | `http://localhost/VeciReport/perfil.php` | Perfil editable |
| `07-admin-reportes.png` | `http://localhost/VeciReport/admin-reportes.php` | Gestion de incidencias |
| `08-admin-vecinos.png` | `http://localhost/VeciReport/admin-vecinos.php` | Aprobacion de vecinos |
| `09-directorio-admin.png` | `http://localhost/VeciReport/directorio-admin.php` | Gestion de trabajadores |
| `10-bitacora.png` | `http://localhost/VeciReport/admin-bitacora.php` | Auditoria del sistema |
| `11-admin-fraccionamientos.png` | `http://localhost/VeciReport/admin-fraccionamientos.php` | Gestion de fraccionamientos con mapa |

## Orden Para Tomarlas

1. Encender Apache y MySQL.
2. Ejecutar `database/demo_seed.sql` si la base ya existia.
3. Entrar como admin y revisar datos demo.
4. Crear al menos un reporte pendiente, uno en proceso y uno atendido.
5. Tomar capturas del lado vecino.
6. Tomar capturas del lado admin.
7. Revisar cada imagen antes de subirla.

## Uso En README

Cuando las imagenes existan, se pueden agregar al `README.md` con este formato:

```md
![Dashboard vecino](docs/screenshots/03-dashboard-vecino.png)
```
