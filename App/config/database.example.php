<?php
// App/config/database.php  ←  copia este archivo a database.php y ajusta los valores
// app/config/database.php
// Conexión a la base de datos usando PDO.
// Se incluye en todos los controladores que necesiten acceso a la BD.

// Datos de conexión
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'vecireport');
define('DB_USER', 'root');
define('DB_PASS', 'tu_password_aqui');

// Función que retorna una conexión PDO lista para usar.
// Usamos una variable estática para no abrir más de una conexión por petición.
function conectar(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";port=" . DB_PORT
             . ";dbname=" . DB_NAME
             . ";charset=utf8mb4";

        $opciones = [
            // Lanza excepciones en lugar de silenciar errores
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Retorna los resultados como arrays asociativos por defecto
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Desactiva la emulación de prepared statements (más seguro)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // En producción nunca mostrar el error real al usuario
            // Aquí lo mostramos solo durante el desarrollo
            http_response_code(500);
            die(json_encode([
                'error' => 'No se pudo conectar a la base de datos.',
                'detalle' => $e->getMessage() // Quitar esta línea en producción
            ]));
        }
    }

    return $pdo;
}
