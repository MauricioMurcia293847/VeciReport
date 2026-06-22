<?php
// App/controllers/TrabajadorController.php
// Gestiona altas, edicion y disponibilidad de trabajadores. Solo admin.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requiereAdmin();

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'crear':
        crearTrabajador();
        break;

    case 'actualizar':
        actualizarTrabajador();
        break;

    case 'disponibilidad':
        cambiarDisponibilidad();
        break;

    default:
        redirectTo('directorio-admin.php?error=1');
}

function datosTrabajadorDesdePost(): array {
    $nombre         = trim($_POST['nombre'] ?? '');
    $apellidos      = trim($_POST['apellidos'] ?? '');
    $especialidad   = trim($_POST['especialidad'] ?? '');
    $telefono       = trim($_POST['telefono'] ?? '');
    $disponibilidad = trim($_POST['disponibilidad'] ?? 'disponible');

    $especialidades = ['electricista', 'plomero', 'albanil', 'jardinero', 'general'];
    $estados        = ['disponible', 'ocupado'];

    if (!$nombre || !$apellidos || !$telefono) {
        redirectTo('directorio-admin.php?error=campos');
    }

    if (!in_array($especialidad, $especialidades, true) || !in_array($disponibilidad, $estados, true)) {
        redirectTo('directorio-admin.php?error=datos');
    }

    return [$nombre, $apellidos, $especialidad, $telefono, $disponibilidad];
}

function registrarBitacora(PDO $pdo, string $descripcion): void {
    $stmt = $pdo->prepare("
        INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
        VALUES (?, 'sistema', ?, ?)
    ");
    $stmt->execute([usuarioActual(), $descripcion, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function crearTrabajador(): void {
    validarCSRF();
    [$nombre, $apellidos, $especialidad, $telefono, $disponibilidad] = datosTrabajadorDesdePost();

    $pdo = conectar();
    $stmt = $pdo->prepare("
        INSERT INTO trabajadores (nombre, apellidos, especialidad, telefono, disponibilidad)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nombre, $apellidos, $especialidad, $telefono, $disponibilidad]);

    registrarBitacora($pdo, "Trabajador creado: {$nombre} {$apellidos} ({$especialidad})");

    redirectTo('directorio-admin.php?ok=trabajador_creado');
}

function actualizarTrabajador(): void {
    validarCSRF();
    $trabajador_id = intval($_POST['trabajador_id'] ?? 0);

    if (!$trabajador_id) {
        redirectTo('directorio-admin.php?error=1');
    }

    [$nombre, $apellidos, $especialidad, $telefono, $disponibilidad] = datosTrabajadorDesdePost();

    $pdo = conectar();
    $stmt = $pdo->prepare("
        UPDATE trabajadores
        SET nombre = ?, apellidos = ?, especialidad = ?, telefono = ?, disponibilidad = ?
        WHERE id = ?
    ");
    $stmt->execute([$nombre, $apellidos, $especialidad, $telefono, $disponibilidad, $trabajador_id]);

    registrarBitacora($pdo, "Trabajador #{$trabajador_id} actualizado");

    redirectTo('directorio-admin.php?ok=trabajador_actualizado');
}

function cambiarDisponibilidad(): void {
    validarCSRF();
    $trabajador_id   = intval($_POST['trabajador_id'] ?? 0);
    $disponibilidad  = trim($_POST['disponibilidad'] ?? '');
    $estados         = ['disponible', 'ocupado'];

    if (!$trabajador_id || !in_array($disponibilidad, $estados, true)) {
        redirectTo('directorio-admin.php?error=1');
    }

    $pdo = conectar();
    $stmt = $pdo->prepare("UPDATE trabajadores SET disponibilidad = ? WHERE id = ?");
    $stmt->execute([$disponibilidad, $trabajador_id]);

    registrarBitacora($pdo, "Disponibilidad del trabajador #{$trabajador_id} cambiada a {$disponibilidad}");

    redirectTo('directorio-admin.php?ok=disponibilidad');
}
