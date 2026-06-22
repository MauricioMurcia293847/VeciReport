<?php
// App/controllers/FraccionamientoController.php
// Gestiona el catalogo de fraccionamientos. Solo admin.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requiereAdmin();

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'crear':
        crearFraccionamiento();
        break;

    case 'estado':
        cambiarEstadoFraccionamiento();
        break;

    case 'eliminar':
        eliminarFraccionamiento();
        break;

    default:
        redirectTo('admin-fraccionamientos.php?error=1');
}

function registrarBitacoraFraccionamiento(PDO $pdo, string $descripcion): void {
    $stmt = $pdo->prepare("
        INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
        VALUES (?, 'sistema', ?, ?)
    ");
    $stmt->execute([usuarioActual(), $descripcion, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function crearFraccionamiento(): void {
    validarCSRF();

    $nombre    = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $mapa_poligono = trim($_POST['mapa_poligono'] ?? '');

    if (!$nombre || strlen($nombre) > 120 || strlen($direccion) > 180) {
        redirectTo('admin-fraccionamientos.php?error=campos');
    }

    if ($mapa_poligono === '') {
        $mapa_poligono = '[[31.741150,-106.489700],[31.742050,-106.481900],[31.738700,-106.478900],[31.734850,-106.482400],[31.735200,-106.488800]]';
    }

    if (!poligonoValido($mapa_poligono)) {
        redirectTo('admin-fraccionamientos.php?error=poligono');
    }

    $pdo = conectar();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO fraccionamientos (nombre, direccion, mapa_poligono, activo)
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([$nombre, $direccion ?: null, $mapa_poligono]);

        registrarBitacoraFraccionamiento($pdo, "Fraccionamiento creado: {$nombre}");
        redirectTo('admin-fraccionamientos.php?ok=creado');
    } catch (PDOException $e) {
        redirectTo('admin-fraccionamientos.php?error=duplicado');
    }
}

function poligonoValido(string $mapa_poligono): bool {
    $puntos = json_decode($mapa_poligono, true);
    if (!is_array($puntos) || count($puntos) < 3) {
        return false;
    }

    foreach ($puntos as $punto) {
        if (!is_array($punto) || count($punto) < 2 || !is_numeric($punto[0]) || !is_numeric($punto[1])) {
            return false;
        }

        $x = (float) $punto[0];
        $y = (float) $punto[1];
        if ($x < -90 || $x > 90 || $y < -180 || $y > 180) {
            return false;
        }
    }

    return true;
}

function cambiarEstadoFraccionamiento(): void {
    validarCSRF();

    $fraccionamiento_id = intval($_POST['fraccionamiento_id'] ?? 0);
    $activo = intval($_POST['activo'] ?? 0) === 1 ? 1 : 0;

    if (!$fraccionamiento_id) {
        redirectTo('admin-fraccionamientos.php?error=1');
    }

    $pdo = conectar();
    $stmt = $pdo->prepare("UPDATE fraccionamientos SET activo = ? WHERE id = ?");
    $stmt->execute([$activo, $fraccionamiento_id]);

    registrarBitacoraFraccionamiento(
        $pdo,
        "Fraccionamiento #{$fraccionamiento_id} marcado como " . ($activo ? 'activo' : 'inactivo')
    );

    redirectTo('admin-fraccionamientos.php?ok=estado');
}

function eliminarFraccionamiento(): void {
    validarCSRF();

    $fraccionamiento_id = intval($_POST['fraccionamiento_id'] ?? 0);
    if (!$fraccionamiento_id) {
        redirectTo('admin-fraccionamientos.php?error=1');
    }

    $pdo = conectar();

    $stmt = $pdo->prepare("SELECT nombre FROM fraccionamientos WHERE id = ?");
    $stmt->execute([$fraccionamiento_id]);
    $nombre = $stmt->fetchColumn();

    if (!$nombre) {
        redirectTo('admin-fraccionamientos.php?error=1');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vecinos WHERE fraccionamiento_id = ?");
    $stmt->execute([$fraccionamiento_id]);
    if ((int) $stmt->fetchColumn() > 0) {
        redirectTo('admin-fraccionamientos.php?error=con_vecinos');
    }

    $stmt = $pdo->prepare("DELETE FROM fraccionamientos WHERE id = ?");
    $stmt->execute([$fraccionamiento_id]);

    registrarBitacoraFraccionamiento($pdo, "Fraccionamiento eliminado: {$nombre}");

    redirectTo('admin-fraccionamientos.php?ok=eliminado');
}
