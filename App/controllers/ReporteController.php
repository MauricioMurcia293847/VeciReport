<?php
// App/controllers/ReporteController.php
// Maneja todas las acciones relacionadas con reportes:
// crear, listar los del vecino, y cambiar estado (admin).

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'crear':
        requiereVecino();
        crearReporte();
        break;

    case 'atender':
        requiereAdmin();
        marcarAtendido();
        break;

    case 'asignar':
        requiereAdmin();
        asignarTrabajador();
        break;

    default:
        redirectTo('dashboard.php');
}



// Crear un nuevo reporte desde el formulario del vecino
function crearReporte(): void {
    validarCSRF();

    $pdo        = conectar();
    $usuario_id = usuarioActual();

    $categoria   = trim($_POST['categoria']    ?? '');
    $tipo        = trim($_POST['tipo_problema'] ?? '');
    $descripcion = trim($_POST['descripcion']  ?? '');
    $color_casa  = trim($_POST['color_casa']   ?? '');
    $num_casa    = trim($_POST['num_casa']     ?? '');

    $cats_validas  = ['luz', 'agua', 'trabajadores', 'otros'];
    $tipos_validos = ['individual', 'colectivo'];

    if (!in_array($categoria, $cats_validas) || !in_array($tipo, $tipos_validos)) {
        redirectTo('reporte.php?error=datos_invalidos');
    }

    if (strlen($descripcion) < 10) {
        redirectTo('reporte.php?error=descripcion_corta');
    }

    if (!$color_casa || !$num_casa) {
        redirectTo('reporte.php?error=campos_vacios');
    }

    $stmt = $pdo->prepare("SELECT id FROM vecinos WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $vecino = $stmt->fetch();

    if (!$vecino) {
        redirectTo('reporte.php?error=vecino_no_encontrado');
    }

    $vecino_id = $vecino['id'];

    $foto_path = null;
    if (!empty($_FILES['foto']['tmp_name'])) {
        $foto_path = subirFoto();
        if ($foto_path === null) {
            redirectTo('reporte.php?error=foto_invalida');
        }
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO reportes
                (vecino_id, categoria, tipo, descripcion, color_casa, num_casa, foto_path, estado)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmt->execute([
            $vecino_id, $categoria, $tipo,
            $descripcion, $color_casa, $num_casa, $foto_path
        ]);

        $reporte_id = $pdo->lastInsertId();

        $desc_bit = "Reporte #{$reporte_id} creado — Categoría: {$categoria}, Tipo: {$tipo}";
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt2    = $pdo->prepare("
            INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
            VALUES (?, 'reporte_creado', ?, ?)
        ");
        $stmt2->execute([$usuario_id, $desc_bit, $ip]);

        $pdo->commit();

        redirectTo("reporte.php?ok=1&id={$reporte_id}");

    } catch (PDOException $e) {
        $pdo->rollBack();
        redirectTo('reporte.php?error=error_servidor');
    }
}



// El admin marca un reporte como atendido
function marcarAtendido(): void {
    validarCSRF();

    $pdo        = conectar();
    $reporte_id = intval($_POST['reporte_id'] ?? 0);
    $admin_id   = usuarioActual();

    if (!$reporte_id) {
        redirectTo('admin-reportes.php?error=1');
    }

    $stmt = $pdo->prepare("UPDATE reportes SET estado = 'atendido' WHERE id = ?");
    $stmt->execute([$reporte_id]);

    $stmt_lib = $pdo->prepare("UPDATE trabajadores SET disponibilidad='disponible' WHERE id = (SELECT trabajador_id FROM reportes WHERE id = ?)");
    $stmt_lib->execute([$reporte_id]);

    $stmt2 = $pdo->prepare("
        INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
        VALUES (?, 'reporte_atendido', ?, ?)
    ");
    $stmt2->execute([
        $admin_id,
        "Reporte #{$reporte_id} marcado como atendido",
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    redirectTo('admin-reportes.php?ok=atendido');
}



// El admin asigna un trabajador a un reporte
function asignarTrabajador(): void {
    validarCSRF();

    $pdo           = conectar();
    $reporte_id    = intval($_POST['reporte_id']    ?? 0);
    $trabajador_id = intval($_POST['trabajador_id'] ?? 0);
    $admin_id      = usuarioActual();
    $notas         = trim($_POST['notas'] ?? '');

    if (!$reporte_id || !$trabajador_id) {
        redirectTo('admin-reportes.php?error=1');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE reportes
            SET trabajador_id = ?, estado = 'en_proceso'
            WHERE id = ?
        ");
        $stmt->execute([$trabajador_id, $reporte_id]);

        $stmt2 = $pdo->prepare("
            INSERT INTO asignaciones (reporte_id, trabajador_id, admin_id, notas)
            VALUES (?, ?, ?, ?)
        ");
        $stmt2->execute([$reporte_id, $trabajador_id, $admin_id, $notas ?: null]);

        $stmt3 = $pdo->prepare("
            UPDATE trabajadores SET disponibilidad = 'ocupado' WHERE id = ?
        ");
        $stmt3->execute([$trabajador_id]);

        $stmt4 = $pdo->prepare("
            INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
            VALUES (?, 'asignacion', ?, ?)
        ");
        $stmt4->execute([
            $admin_id,
            "Trabajador #{$trabajador_id} asignado al reporte #{$reporte_id}",
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        $pdo->commit();

        redirectTo('admin-reportes.php?ok=asignado');

    } catch (PDOException $e) {
        $pdo->rollBack();
        redirectTo('admin-reportes.php?error=1');
    }
}



// Sube la foto del reporte al servidor
function subirFoto(): ?string {
    return subirArchivoSeguro(
        'foto',
        'reportes',
        ['jpg', 'jpeg', 'png'],
        ['image/jpeg', 'image/png'],
        5 * 1024 * 1024,
        'rep_'
    );
}
