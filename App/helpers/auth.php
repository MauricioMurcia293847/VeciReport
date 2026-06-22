<?php
// app/helpers/auth.php
// Funciones de autenticación reutilizadas en todos los controladores.
// Se incluye al inicio de cualquier página que requiera sesión activa.

// Configuracion opcional de entorno/ruta base para hosting.
$appConfig = __DIR__ . '/../config/app.php';
if (is_file($appConfig)) {
    require_once $appConfig;
}

// Inicia la sesión si no está iniciada ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Devuelve la base publica de la app: '' en dominio raiz o '/VeciReport' en XAMPP.
function appBaseUrl(): string {
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    if (defined('APP_BASE_URL')) {
        $base = rtrim((string) APP_BASE_URL, '/');
        return $base === '/' ? '' : $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $appPos = strpos($script, '/App/');

    if ($appPos !== false) {
        $base = substr($script, 0, $appPos);
    } else {
        $base = dirname($script);
    }

    $base = str_replace('\\', '/', $base);
    $base = rtrim($base, '/');
    return $base === '' || $base === '.' ? '' : $base;
}

function appEnv(): string {
    return defined('APP_ENV') ? (string) APP_ENV : 'local';
}

function appDemoMode(): bool {
    return defined('APP_DEMO_MODE') && APP_DEMO_MODE === true;
}

function demoProtectedEmails(): array {
    if (!appDemoMode()) {
        return [];
    }

    $emails = defined('APP_DEMO_PROTECTED_EMAILS')
        ? explode(',', (string) APP_DEMO_PROTECTED_EMAILS)
        : ['admin@vecireport.com', 'vecino.demo@vecireport.com'];

    return array_values(array_filter(array_map(
        static fn($email) => strtolower(trim($email)),
        $emails
    )));
}

function esCuentaDemoProtegida(PDO $pdo, int $usuario_id): bool {
    if (!appDemoMode() || $usuario_id <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $correo = $stmt->fetchColumn();

    return $correo && in_array(strtolower((string) $correo), demoProtectedEmails(), true);
}

// Construye URLs absolutas dentro de la app sin depender de /VeciReport.
function url(string $path = ''): string {
    $base = appBaseUrl();
    $path = ltrim($path, '/');

    return $path === '' ? ($base ?: '/') : (($base ?: '') . '/' . $path);
}

// Redirige a una ruta interna respetando la base local o de hosting.
function redirectTo(string $path): void {
    header('Location: ' . url($path));
    exit;
}

// Verifica que haya una sesión activa.
// Si no hay sesión, redirige al login.
function requiereLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        redirectTo('login.php');
    }
}

// Verifica que el usuario en sesión sea administrador.
// Si no lo es, redirige al dashboard del vecino.
function requiereAdmin(): void {
    requiereLogin();

    if ($_SESSION['rol'] !== 'admin') {
        redirectTo('dashboard.php');
    }
}

// Verifica que el usuario en sesión sea vecino.
// Si es admin, lo manda al panel del admin.
function requiereVecino(): void {
    requiereLogin();

    if ($_SESSION['rol'] !== 'vecino') {
        redirectTo('admin.php');
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
    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Solicitud inválida. Recarga la página e intenta de nuevo.');
    }
}

// Valida y guarda un archivo subido dentro de uploads.
function subirArchivoSeguro(
    string $campo,
    string $subdirectorio,
    array $extensionesPermitidas,
    array $mimePermitidos,
    int $maxBytes,
    string $prefijo
): ?string {
    if (empty($_FILES[$campo]['tmp_name'])) {
        return null;
    }

    $archivo = $_FILES[$campo];

    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (($archivo['size'] ?? 0) <= 0 || $archivo['size'] > $maxBytes) {
        return null;
    }

    $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($extension, $extensionesPermitidas, true)) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($archivo['tmp_name']);
    if (!in_array($mime, $mimePermitidos, true)) {
        return null;
    }

    $carpeta = __DIR__ . '/../../uploads/' . trim($subdirectorio, '/') . '/';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    $nombre = $prefijo . bin2hex(random_bytes(16)) . '.' . $extension;
    $ruta   = $carpeta . $nombre;

    if (!move_uploaded_file($archivo['tmp_name'], $ruta)) {
        return null;
    }

    return 'uploads/' . trim($subdirectorio, '/') . '/' . $nombre;
}

// Renderiza el formulario de cierre de sesión protegido con CSRF.
function formularioLogout(): string {
    $token  = htmlspecialchars(generarCSRF(), ENT_QUOTES, 'UTF-8');
    $action = htmlspecialchars(url('App/controllers/UsuarioController.php'), ENT_QUOTES, 'UTF-8');

    return <<<HTML
      <form method="POST" action="{$action}" class="sidebar__logout-form">
        <input type="hidden" name="accion" value="logout">
        <input type="hidden" name="csrf_token" value="{$token}">
        <button type="submit" class="sidebar__link sidebar__link--logout">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
          <span>Cerrar sesión</span>
        </button>
      </form>
HTML;
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
    redirectTo('login.php');
}
