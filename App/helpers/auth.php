<?php
// app/helpers/auth.php
// Funciones de autenticación reutilizadas en todos los controladores.
// Se incluye al inicio de cualquier página que requiera sesión activa.

// Inicia la sesión si no está iniciada ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica que haya una sesión activa.
// Si no hay sesión, redirige al login.
function requiereLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: /VeciReport/login.html');
        exit;
    }
}

// Verifica que el usuario en sesión sea administrador.
// Si no lo es, redirige al dashboard del vecino.
function requiereAdmin(): void {
    requiereLogin();

    if ($_SESSION['rol'] !== 'admin') {
        header('Location: /VeciReport/dashboard.php');
        exit;
    }
}

// Verifica que el usuario en sesión sea vecino.
// Si es admin, lo manda al panel del admin.
function requiereVecino(): void {
    requiereLogin();

    if ($_SESSION['rol'] !== 'vecino') {
        header('Location: /VeciReport/admin.php');
        exit;
    }
}

// Retorna el ID del usuario en sesión o null si no hay sesión.
function usuarioActual(): ?int {
    return $_SESSION['usuario_id'] ?? null;
}

// Retorna el rol del usuario en sesión o null si no hay sesión.
function rolActual(): ?string {
    return $_SESSION['rol'] ?? null;
}

// Guarda los datos del usuario en la sesión después del login.
function iniciarSesion(array $usuario): void {
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['nombre']     = $usuario['nombre'];
    $_SESSION['apellidos']  = $usuario['apellidos'];
    $_SESSION['correo']     = $usuario['correo'];
    $_SESSION['rol']        = $usuario['rol'];

    // Regenera el ID de sesión para prevenir session fixation
    session_regenerate_id(true);
}

// Genera un token CSRF y lo guarda en sesión. Devuelve el token.
function generarCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida el token CSRF del POST. Termina con 403 si no coincide.
function validarCSRF(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Solicitud inválida. Recarga la página e intenta de nuevo.');
    }
}

// Destruye la sesión completamente y redirige al login.
function cerrarSesion(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
    header('Location: /VeciReport/login.html');
    exit;
}