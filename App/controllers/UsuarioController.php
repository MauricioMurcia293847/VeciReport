<?php
// App/controllers/UsuarioController.php
// Maneja: login, logout, registro, aprobación y bloqueo de vecinos.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'login':
        procesarLogin();
        break;

    case 'logout':
        procesarLogout();
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

    case 'actualizar_perfil':
        requiereVecino();
        actualizarPerfil();
        break;

    default:
        redirectTo('login.php');
}



// Procesa el formulario de login
function procesarLogin(): void {
    validarCSRF();

    $correo   = trim($_POST['correo']   ?? '');
    $password = $_POST['password'] ?? '';

    if (!$correo || !$password) {
        redirectTo('login.php?error=1');
    }

    $pdo  = conectar();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    // Credenciales incorrectas
    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        redirectTo('login.php?error=1');
    }

    // Cuenta pendiente de aprobación
    if ($usuario['estado'] === 'pendiente') {
        redirectTo('login.php?error=pendiente');
    }

    // Cuenta bloqueada por el admin
    if ($usuario['estado'] === 'bloqueado') {
        redirectTo('login.php?error=bloqueado');
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
        redirectTo('admin-reportes.php');
    } else {
        redirectTo('dashboard.php');
    }
}

// Procesa el cierre de sesión por POST protegido con CSRF.
function procesarLogout(): void {
    requiereLogin();
    validarCSRF();

    $usuario_id = usuarioActual();
    if ($usuario_id) {
        $pdo = conectar();
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("
            INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
            VALUES (?, 'logout', 'Cierre de sesión', ?)
        ");
        $stmt->execute([$usuario_id, $ip]);
    }

    cerrarSesion();
}

// Aprueba un vecino (pendiente → activo) o desbloquea uno
function aprobarVecino(): void {
    validarCSRF();

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) {
        redirectTo('admin-vecinos.php?error=1');
    }

    $pdo = conectar();
    $pdo->prepare("UPDATE usuarios SET estado = 'activo' WHERE id = ? AND rol = 'vecino'")
        ->execute([$usuario_id]);

    $admin_id = usuarioActual();
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare("INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip) VALUES (?, 'vecino_aprobado', ?, ?)")
        ->execute([$admin_id, "Vecino #{$usuario_id} aprobado por el administrador", $ip]);

    redirectTo('admin-vecinos.php?ok=aprobado');
}



// Registra un nuevo vecino (estado='pendiente', requiere aprobación del admin)
function registrarVecino(): void {
    validarCSRF();

    $nombre     = trim($_POST['nombre']     ?? '');
    $apellidos  = trim($_POST['apellidos']  ?? '');
    $correo     = trim($_POST['correo']     ?? '');
    $password   = $_POST['password']  ?? '';
    $password2  = $_POST['password2'] ?? '';
    $fraccionamiento_id = intval($_POST['fraccionamiento_id'] ?? 0);
    $ubicacion_lat = $_POST['ubicacion_lat'] ?? null;
    $ubicacion_lng = $_POST['ubicacion_lng'] ?? null;
    $num_calle  = trim($_POST['num_calle']  ?? '');
    $num_casa   = trim($_POST['num_casa']   ?? '');
    $color_casa = trim($_POST['color_casa'] ?? '');

    if (!$nombre || !$apellidos || !$correo || !$password || !$fraccionamiento_id || $ubicacion_lat === null || $ubicacion_lng === null || !$num_calle || !$num_casa || !$color_casa) {
        redirectTo('registro.php?error=campos_vacios');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        redirectTo('registro.php?error=correo_invalido');
    }

    if (strlen($password) < 6) {
        redirectTo('registro.php?error=password_corta');
    }

    if ($password !== $password2) {
        redirectTo('registro.php?error=passwords_no_coinciden');
    }

    $pdo = conectar();

    $stmt = $pdo->prepare("SELECT id, mapa_poligono FROM fraccionamientos WHERE id = ? AND activo = 1");
    $stmt->execute([$fraccionamiento_id]);
    $fraccionamiento = $stmt->fetch();
    if (!$fraccionamiento) {
        redirectTo('registro.php?error=fraccionamiento_invalido');
    }

    $ubicacion_lat = filter_var($ubicacion_lat, FILTER_VALIDATE_FLOAT);
    $ubicacion_lng = filter_var($ubicacion_lng, FILTER_VALIDATE_FLOAT);
    if ($ubicacion_lat === false || $ubicacion_lng === false || !puntoEnPoligono((float) $ubicacion_lat, (float) $ubicacion_lng, $fraccionamiento['mapa_poligono'])) {
        redirectTo('registro.php?error=ubicacion_fuera');
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        redirectTo('registro.php?error=correo_duplicado');
    }

    $comprobante_path = subirArchivoSeguro(
        'comprobante',
        'comprobantes',
        ['jpg', 'jpeg', 'png', 'pdf'],
        ['image/jpeg', 'image/png', 'application/pdf'],
        5 * 1024 * 1024,
        'comp_'
    );

    if ($comprobante_path === null) {
        redirectTo('registro.php?error=comprobante_invalido');
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
            INSERT INTO vecinos (usuario_id, fraccionamiento_id, num_calle, num_casa, color_casa, ubicacion_x, ubicacion_y, ubicacion_lat, ubicacion_lng, comprobante_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt2->execute([$usuario_id, $fraccionamiento_id, $num_calle, $num_casa, $color_casa, null, null, $ubicacion_lat, $ubicacion_lng, $comprobante_path]);

        $stmt3 = $pdo->prepare("
            INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
            VALUES (?, 'registro', ?, ?)
        ");
        $stmt3->execute([$usuario_id, "Registro nuevo vecino: {$correo}", $ip]);

        $pdo->commit();

        redirectTo('login.php?registro=ok');

    } catch (PDOException $e) {
        $pdo->rollBack();
        redirectTo('registro.php?error=error_servidor');
    }
}

// Actualiza los datos editables del perfil del vecino en sesión.
// Valida que una coordenada real pertenezca al poligono del fraccionamiento.
function puntoEnPoligono(float $lat, float $lng, ?string $poligonoJson): bool {
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || !$poligonoJson) {
        return false;
    }

    $poligono = json_decode($poligonoJson, true);
    if (!is_array($poligono) || count($poligono) < 3) {
        return false;
    }

    $dentro = false;
    $total = count($poligono);

    for ($i = 0, $j = $total - 1; $i < $total; $j = $i++) {
        if (!isset($poligono[$i][0], $poligono[$i][1], $poligono[$j][0], $poligono[$j][1])) {
            return false;
        }

        $yi = (float) $poligono[$i][0];
        $xi = (float) $poligono[$i][1];
        $yj = (float) $poligono[$j][0];
        $xj = (float) $poligono[$j][1];

        $intersecta = (($yi > $lat) !== ($yj > $lat))
            && ($lng < (($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 0.000001) + $xi));

        if ($intersecta) {
            $dentro = !$dentro;
        }
    }

    return $dentro;
}

// Actualiza los datos editables del perfil del vecino.
function actualizarPerfil(): void {
    validarCSRF();

    $usuario_id = usuarioActual();
    if (!$usuario_id) {
        redirectTo('login.php');
    }

    $nombre     = trim($_POST['nombre']     ?? '');
    $apellidos  = trim($_POST['apellidos']  ?? '');
    $num_calle  = trim($_POST['num_calle']  ?? '');
    $num_casa   = trim($_POST['num_casa']   ?? '');
    $color_casa = trim($_POST['color_casa'] ?? '');
    $pass_actual = $_POST['pass_actual'] ?? '';
    $pass_nueva  = $_POST['pass_nueva']  ?? '';

    if (!$nombre || !$apellidos || !$num_calle || !$num_casa || !$color_casa) {
        redirectTo('perfil.php?error=perfil');
    }

    if (strlen($nombre) > 80 || strlen($apellidos) > 120 || strlen($num_calle) > 100 || strlen($num_casa) > 20 || strlen($color_casa) > 50) {
        redirectTo('perfil.php?error=perfil');
    }

    $cambia_password = $pass_actual !== '' || $pass_nueva !== '';
    if ($cambia_password && (strlen($pass_nueva) < 6 || $pass_actual === '')) {
        redirectTo('perfil.php?error=perfil');
    }

    $pdo = conectar();
    $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ? AND rol = 'vecino'");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        redirectTo('perfil.php?error=perfil');
    }

    if (esCuentaDemoProtegida($pdo, (int) $usuario_id)) {
        redirectTo('perfil.php?error=demo');
    }

    if ($cambia_password && !password_verify($pass_actual, $usuario['password_hash'])) {
        redirectTo('perfil.php?error=perfil');
    }

    try {
        $pdo->beginTransaction();

        if ($cambia_password) {
            $hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellidos = ?, password_hash = ? WHERE id = ? AND rol = 'vecino'");
            $stmt->execute([$nombre, $apellidos, $hash, $usuario_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellidos = ? WHERE id = ? AND rol = 'vecino'");
            $stmt->execute([$nombre, $apellidos, $usuario_id]);
        }

        $stmt = $pdo->prepare("
            UPDATE vecinos
            SET num_calle = ?, num_casa = ?, color_casa = ?
            WHERE usuario_id = ?
        ");
        $stmt->execute([$num_calle, $num_casa, $color_casa, $usuario_id]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("
            INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
            VALUES (?, 'sistema', 'Actualización de perfil del vecino', ?)
        ");
        $stmt->execute([$usuario_id, $ip]);

        $pdo->commit();

        $_SESSION['nombre']    = $nombre;
        $_SESSION['apellidos'] = $apellidos;

        redirectTo('perfil.php?ok=perfil');
    } catch (PDOException $e) {
        $pdo->rollBack();
        redirectTo('perfil.php?error=perfil');
    }
}



// Bloquea un vecino activo
function bloquearVecino(): void {
    validarCSRF();

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) {
        redirectTo('admin-vecinos.php?error=1');
    }

    $pdo = conectar();
    if (esCuentaDemoProtegida($pdo, $usuario_id)) {
        redirectTo('admin-vecinos.php?error=demo');
    }

    $pdo->prepare("UPDATE usuarios SET estado = 'bloqueado' WHERE id = ? AND rol = 'vecino'")
        ->execute([$usuario_id]);

    $admin_id = usuarioActual();
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare("INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip) VALUES (?, 'vecino_bloqueado', ?, ?)")
        ->execute([$admin_id, "Vecino #{$usuario_id} bloqueado por el administrador", $ip]);

    redirectTo('admin-vecinos.php?ok=bloqueado');
}
