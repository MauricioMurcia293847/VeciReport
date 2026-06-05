<?php
// App/controllers/UsuarioController.php
// Maneja: login, logout, registro, aprobación y bloqueo de vecinos.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'login':
        procesarLogin();
        break;

    case 'logout':
        cerrarSesion();
        break;

    case 'aprobar':
        requiereAdmin();
        aprobarVecino();
        break;

    case 'bloquear':
        requiereAdmin();
        bloquearVecino();
        break;

    case 'registro':
        registrarVecino();
        break;

    default:
        header('Location: /VeciReport/login.html');
        exit;
}



// Procesa el formulario de login
function procesarLogin(): void {
    validarCSRF();

    $correo   = trim($_POST['correo']   ?? '');
    $password = $_POST['password'] ?? '';

    if (!$correo || !$password) {
        header('Location: /VeciReport/login.html?error=1');
        exit;
    }

    $pdo  = conectar();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    // Credenciales incorrectas
    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        header('Location: /VeciReport/login.html?error=1');
        exit;
    }

    // Cuenta pendiente de aprobación
    if ($usuario['estado'] === 'pendiente') {
        header('Location: /VeciReport/login.html?error=pendiente');
        exit;
    }

    // Cuenta bloqueada por el admin
    if ($usuario['estado'] === 'bloqueado') {
        header('Location: /VeciReport/login.html?error=bloqueado');
        exit;
    }

    // Login exitoso: guardar sesión
    iniciarSesion($usuario);

    // Registrar en bitácora
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $pdo->prepare("
        INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
        VALUES (?, 'login', ?, ?)
    ");
    $stmt->execute([$usuario['id'], "Login exitoso: {$usuario['correo']}", $ip]);

    // Redirigir según rol
    if ($usuario['rol'] === 'admin') {
        header('Location: /VeciReport/admin-reportes.php');
    } else {
        header('Location: /VeciReport/dashboard.php');
    }
    exit;
}



// Aprueba un vecino (pendiente → activo) o desbloquea uno
function aprobarVecino(): void {
    validarCSRF();

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) {
        header('Location: /VeciReport/admin-vecinos.php?error=1');
        exit;
    }

    $pdo = conectar();
    $pdo->prepare("UPDATE usuarios SET estado = 'activo' WHERE id = ? AND rol = 'vecino'")
        ->execute([$usuario_id]);

    $admin_id = usuarioActual();
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare("INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip) VALUES (?, 'vecino_aprobado', ?, ?)")
        ->execute([$admin_id, "Vecino #{$usuario_id} aprobado por el administrador", $ip]);

    header('Location: /VeciReport/admin-vecinos.php?ok=aprobado');
    exit;
}



// Registra un nuevo vecino (estado='pendiente', requiere aprobación del admin)
function registrarVecino(): void {
    validarCSRF();

    $nombre     = trim($_POST['nombre']     ?? '');
    $apellidos  = trim($_POST['apellidos']  ?? '');
    $correo     = trim($_POST['correo']     ?? '');
    $password   = $_POST['password']  ?? '';
    $password2  = $_POST['password2'] ?? '';
    $num_calle  = trim($_POST['num_calle']  ?? '');
    $num_casa   = trim($_POST['num_casa']   ?? '');
    $color_casa = trim($_POST['color_casa'] ?? '');

    if (!$nombre || !$apellidos || !$correo || !$password || !$num_calle || !$num_casa || !$color_casa) {
        header('Location: /VeciReport/registro.html?error=campos_vacios');
        exit;
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header('Location: /VeciReport/registro.html?error=correo_invalido');
        exit;
    }

    if (strlen($password) < 6) {
        header('Location: /VeciReport/registro.html?error=password_corta');
        exit;
    }

    if ($password !== $password2) {
        header('Location: /VeciReport/registro.html?error=passwords_no_coinciden');
        exit;
    }

    $pdo = conectar();

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        header('Location: /VeciReport/registro.html?error=correo_duplicado');
        exit;
    }

    $comprobante_path = null;
    if (!empty($_FILES['comprobante']['tmp_name'])) {
        $archivo    = $_FILES['comprobante'];
        $extension  = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($extension, $permitidos) || $archivo['size'] > 5 * 1024 * 1024) {
            header('Location: /VeciReport/registro.html?error=comprobante_invalido');
            exit;
        }

        $carpeta        = __DIR__ . '/../../uploads/comprobantes/';
        $nombre_archivo = uniqid('comp_', true) . '.' . $extension;

        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
        if (!move_uploaded_file($archivo['tmp_name'], $carpeta . $nombre_archivo)) {
            header('Location: /VeciReport/registro.html?error=error_servidor');
            exit;
        }

        $comprobante_path = 'uploads/comprobantes/' . $nombre_archivo;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, apellidos, correo, password_hash, rol, estado)
            VALUES (?, ?, ?, ?, 'vecino', 'pendiente')
        ");
        $stmt->execute([$nombre, $apellidos, $correo, $hash]);

        $usuario_id = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("
            INSERT INTO vecinos (usuario_id, num_calle, num_casa, color_casa, comprobante_path)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt2->execute([$usuario_id, $num_calle, $num_casa, $color_casa, $comprobante_path]);

        $stmt3 = $pdo->prepare("
            INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
            VALUES (?, 'registro', ?, ?)
        ");
        $stmt3->execute([$usuario_id, "Registro nuevo vecino: {$correo}", $ip]);

        $pdo->commit();

        header('Location: /VeciReport/login.html?registro=ok');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: /VeciReport/registro.html?error=error_servidor');
        exit;
    }
}



// Bloquea un vecino activo
function bloquearVecino(): void {
    validarCSRF();

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) {
        header('Location: /VeciReport/admin-vecinos.php?error=1');
        exit;
    }

    $pdo = conectar();
    $pdo->prepare("UPDATE usuarios SET estado = 'bloqueado' WHERE id = ? AND rol = 'vecino'")
        ->execute([$usuario_id]);

    $admin_id = usuarioActual();
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare("INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip) VALUES (?, 'vecino_bloqueado', ?, ?)")
        ->execute([$admin_id, "Vecino #{$usuario_id} bloqueado por el administrador", $ip]);

    header('Location: /VeciReport/admin-vecinos.php?ok=bloqueado');
    exit;
}
