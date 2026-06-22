<?php
// Copia este archivo como App/config/app.php solo si necesitas ajustar
// configuracion por entorno en hosting.

// Entorno de ejecucion: local | production
define('APP_ENV', 'local');

// Activa ayudas y protecciones para una demo publica controlada.
// En produccion real debe quedar en false.
define('APP_DEMO_MODE', false);

// Cuentas que no deben bloquearse ni modificarse cuando APP_DEMO_MODE=true.
define('APP_DEMO_PROTECTED_EMAILS', 'admin@vecireport.com,vecino.demo@vecireport.com');

// Ruta base publica de la aplicacion.
// Deja '' cuando el proyecto vive en la raiz del dominio:
// https://tudominio.com/
//
// Usa '/VeciReport' cuando vive en una subcarpeta:
// https://tudominio.com/VeciReport/
//
// Si no defines APP_BASE_URL, el sistema intentara detectarlo
// automaticamente usando SCRIPT_NAME.
define('APP_BASE_URL', '');
