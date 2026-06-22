# Guia De Capturas

Esta carpeta esta pensada para guardar capturas limpias del proyecto para GitHub y LinkedIn.

## Recomendaciones

- Usar datos demo, no datos personales reales.
- Tomar capturas en navegador de escritorio desde la demo publica cuando sea posible.
- Mantener la misma resolucion para todas las imagenes, por ejemplo `1440x900`.
- Evitar que se vean contrasenas, rutas locales sensibles o archivos privados.
- Verificar que no aparezcan errores PHP, warnings o datos de pruebas accidentales.

## Capturas Sugeridas

| Archivo | URL sugerida | Objetivo |
|---|---|---|
| `01-landing.png` | `https://vecireport.gt.tc/index.php` | Presentar el producto |
| `02-login.png` | `https://vecireport.gt.tc/login.php` | Mostrar acceso y cuentas demo |
| `03-dashboard-vecino.png` | `https://vecireport.gt.tc/dashboard.php` | Resumen del vecino |
| `04-nuevo-reporte.png` | `https://vecireport.gt.tc/reporte.php` | Flujo de reporte |
| `05-mis-reportes.png` | `https://vecireport.gt.tc/mis-reportes.php` | Historial del vecino |
| `06-perfil.png` | `https://vecireport.gt.tc/perfil.php` | Perfil editable |
| `07-admin-reportes.png` | `https://vecireport.gt.tc/admin-reportes.php` | Gestion de incidencias |
| `08-admin-vecinos.png` | `https://vecireport.gt.tc/admin-vecinos.php` | Aprobacion de vecinos |
| `09-directorio-admin.png` | `https://vecireport.gt.tc/directorio-admin.php` | Gestion de trabajadores |
| `10-bitacora.png` | `https://vecireport.gt.tc/admin-bitacora.php` | Auditoria del sistema |
| `11-admin-fraccionamientos.png` | `https://vecireport.gt.tc/admin-fraccionamientos.php` | Gestion de fraccionamientos con mapa |

## Orden Para Tomarlas

1. Abrir la demo publica o el entorno local.
2. Ejecutar el seed demo si la base local ya existia.
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
