<?php
// perfil.php
// Perfil del vecino en sesión: datos personales, domicilio, estadísticas y accesos recientes.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereVecino();

$pdo        = conectar();
$usuario_id = usuarioActual();

// Datos personales + domicilio del vecino
$stmt = $pdo->prepare("
    SELECT u.nombre, u.apellidos, u.correo,
           v.num_calle, v.num_casa, v.color_casa, v.ubicacion_lat, v.ubicacion_lng, v.comprobante_path,
           f.nombre AS fraccionamiento
    FROM usuarios u
    JOIN vecinos v ON v.usuario_id = u.id
    JOIN fraccionamientos f ON f.id = v.fraccionamiento_id
    WHERE u.id = ?
");
$stmt->execute([$usuario_id]);
$perfil = $stmt->fetch();

// Si el vecino no tiene fila en la tabla vecinos, usa los datos de sesión como fallback
$nombre    = $perfil['nombre']    ?? $_SESSION['nombre'];
$apellidos = $perfil['apellidos'] ?? $_SESSION['apellidos'];
$correo    = $perfil['correo']    ?? $_SESSION['correo'];
$num_calle = $perfil['num_calle'] ?? '—';
$num_casa  = $perfil['num_casa']  ?? '—';
$color_casa= $perfil['color_casa']?? '—';
$fraccionamiento = $perfil['fraccionamiento'] ?? 'Fraccionamiento';
$ubicacion_mapa = isset($perfil['ubicacion_lat'], $perfil['ubicacion_lng'])
    ? number_format((float) $perfil['ubicacion_lat'], 7) . ', ' . number_format((float) $perfil['ubicacion_lng'], 7)
    : 'No registrada';
$comprobante_path = $perfil['comprobante_path'] ?? null;
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Estadísticas de reportes
$stmt = $pdo->prepare("
    SELECT
        COUNT(*)                       AS total,
        SUM(estado = 'atendido')       AS atendidos,
        SUM(estado = 'en_proceso')     AS en_proceso,
        SUM(estado = 'pendiente')      AS pendientes
    FROM reportes
    WHERE vecino_id = (SELECT id FROM vecinos WHERE usuario_id = ?)
");
$stmt->execute([$usuario_id]);
$stats      = $stmt->fetch();
$total      = (int)($stats['total']      ?? 0);
$atendidos  = (int)($stats['atendidos']  ?? 0);
$en_proceso = (int)($stats['en_proceso'] ?? 0);
$pendientes = (int)($stats['pendientes'] ?? 0);
$tasa       = $total > 0 ? round($atendidos / $total * 100) : 0;

// Acceso reciente desde bitácora (últimas 5 entradas de login/logout)
$stmt = $pdo->prepare("
    SELECT tipo_accion, created_at
    FROM bitacora
    WHERE usuario_id = ?
      AND tipo_accion IN ('login', 'logout')
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$usuario_id]);
$accesos = $stmt->fetchAll();

function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . round($diff / 60)    . ' min';
    if ($diff < 86400)  return 'Hace ' . round($diff / 3600)  . ' hora'  . (round($diff / 3600)  > 1 ? 's' : '');
    if ($diff < 604800) return 'Hace ' . round($diff / 86400) . ' día'   . (round($diff / 86400) > 1 ? 's' : '');
    return date('d M Y', strtotime($fecha));
}

// Mensajes desde el controlador (cuando se implemente actualizar_perfil)
$ok_perfil    = isset($_GET['ok'])    && $_GET['ok']    === 'perfil';
$error_perfil = isset($_GET['error']) && $_GET['error'] === 'perfil';
$error_demo = isset($_GET['error']) && $_GET['error'] === 'demo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Mi Perfil</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
  <link rel="stylesheet" href="Carpeta CSS/perfil.css">
</head>

<body class="dashboard-page">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="navbar__logo">
        <svg width="26" height="26" viewBox="0 0 28 28" fill="none">
          <circle cx="14" cy="14" r="13" stroke="#E85D26" stroke-width="2"/>
          <path d="M14 7C10.134 7 7 10.134 7 14C7 17.866 10.134 21 14 21C17.866 21 21 17.866 21 14" stroke="#E85D26" stroke-width="2" stroke-linecap="round"/>
          <circle cx="14" cy="14" r="3" fill="#E85D26"/>
          <path d="M14 11V7M17 14H21" stroke="#E85D26" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <span class="sidebar__name">VeciReport</span>
    </div>

    <nav class="sidebar__nav">
      <p class="sidebar__section-label">Principal</p>

      <a href="dashboard.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Inicio</span>
      </a>

      <a href="reporte.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        <span>Nuevo reporte</span>
      </a>

      <a href="mis-reportes.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        <span>Mis reportes</span>
        <?php if ($en_proceso > 0): ?>
          <span class="sidebar__badge"><?= $en_proceso ?></span>
        <?php endif; ?>
      </a>

      <a href="directorio.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Trabajadores</span>
      </a>

      <p class="sidebar__section-label">Cuenta</p>

      <a href="perfil.php" class="sidebar__link sidebar__link--active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Mi perfil</span>
      </a>

      <?= formularioLogout() ?>
    </nav>

    <div class="sidebar__user">
      <div class="sidebar__user-avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="sidebar__user-info">
        <p class="sidebar__user-name"><?= htmlspecialchars($nombre . ' ' . $apellidos) ?></p>
        <p class="sidebar__user-role">Vecino · Casa #<?= htmlspecialchars($num_casa) ?></p>
      </div>
    </div>
  </aside>

  <div class="sidebar__overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  <main class="dashboard-main">

    <header class="topbar">
      <button class="topbar__toggle" onclick="toggleSidebar()" aria-label="Abrir menú">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topbar__title">
        <h1>Mi perfil</h1>
        <p>Información de tu cuenta y domicilio</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">
      <div class="perfil-layout">

        <!-- Columna izquierda: datos del usuario -->
        <div class="perfil-main">

          <!-- Alertas del controlador -->
          <?php if ($ok_perfil): ?>
            <div class="form__alert form__alert--success" style="display:flex;margin-bottom:16px">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              <span>Perfil actualizado correctamente.</span>
            </div>
          <?php elseif ($error_perfil): ?>
            <div class="form__alert form__alert--error" style="display:flex;margin-bottom:16px">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span>Ocurrió un error al guardar. Intenta de nuevo.</span>
            </div>
          <?php endif; ?>

          <!-- Tarjeta de información personal -->
          <div class="dash-section perfil-section">
            <div class="dash-section__header">
              <h2 class="dash-section__title">Información personal</h2>
              <button class="perfil-edit-btn" id="btnEditar" onclick="toggleEditar()">✏️ Editar</button>
            </div>

            <form id="perfilForm" action="App/controllers/UsuarioController.php" method="POST" novalidate>
              <input type="hidden" name="accion" value="actualizar_perfil">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">

              <div class="perfil-avatar-wrap">
                <div class="perfil-avatar"><?= htmlspecialchars($iniciales) ?></div>
                <div>
                  <p class="perfil-nombre-big"><?= htmlspecialchars($nombre . ' ' . $apellidos) ?></p>
                  <p class="perfil-rol-badge">👤 Vecino verificado</p>
                </div>
              </div>

              <div class="perfil-grid">

                <div class="form__group">
                  <label class="form__label">Nombre(s)</label>
                  <input type="text" name="nombre" class="form__input perfil-input"
                         value="<?= htmlspecialchars($nombre) ?>" disabled>
                </div>

                <div class="form__group">
                  <label class="form__label">Apellidos</label>
                  <input type="text" name="apellidos" class="form__input perfil-input"
                         value="<?= htmlspecialchars($apellidos) ?>" disabled>
                </div>

                <div class="form__group perfil-grid--full">
                  <label class="form__label">Correo electrónico</label>
                  <input type="email" name="correo" class="form__input"
                          value="<?= htmlspecialchars($correo) ?>" disabled>
                </div>

              </div>

              <!-- Sección de domicilio -->
              <div class="perfil-section__sub">
                <h3 class="perfil-section__sub-title">Domicilio</h3>
                <div class="perfil-grid">
                  <div class="form__group perfil-grid--full">
                    <label class="form__label">Fraccionamiento</label>
                    <input type="text" class="form__input"
                           value="<?= htmlspecialchars($fraccionamiento) ?>" disabled>
                  </div>
                  <div class="form__group">
                    <label class="form__label">Número de calle</label>
                    <input type="text" name="num_calle" class="form__input perfil-input"
                           value="<?= htmlspecialchars($num_calle) ?>" disabled>
                  </div>
                  <div class="form__group">
                    <label class="form__label">Número de casa</label>
                    <input type="text" name="num_casa" class="form__input perfil-input"
                           value="<?= htmlspecialchars($num_casa) ?>" disabled>
                  </div>
                  <div class="form__group">
                    <label class="form__label">Color de la casa</label>
                    <input type="text" name="color_casa" class="form__input perfil-input"
                           value="<?= htmlspecialchars($color_casa) ?>" disabled>
                  </div>
                  <div class="form__group">
                    <label class="form__label">Ubicacion en mapa</label>
                    <input type="text" class="form__input"
                           value="<?= htmlspecialchars($ubicacion_mapa) ?>" disabled>
                  </div>
                </div>
              </div>

              <!-- Sección cambio de contraseña (oculta hasta activar edición) -->
              <div class="perfil-section__sub perfil-pass-section" id="passSection" style="display:none">
                <h3 class="perfil-section__sub-title">Cambiar contraseña</h3>
                <div class="perfil-grid">
                  <div class="form__group">
                    <label class="form__label">Contraseña actual</label>
                    <input type="password" name="pass_actual" class="form__input" placeholder="••••••••">
                  </div>
                  <div class="form__group">
                    <label class="form__label">Nueva contraseña</label>
                    <input type="password" name="pass_nueva" class="form__input" placeholder="Mínimo 6 caracteres">
                  </div>
                </div>
              </div>

              <!-- Botones de guardar (ocultos hasta activar edición) -->
              <div class="perfil-actions" id="perfilActions" style="display:none">
                <button type="button" class="btn btn--ghost" onclick="cancelarEdicion()">Cancelar</button>
                <button type="submit" class="btn btn--primary">Guardar cambios</button>
              </div>

            </form>
          </div>

        </div>

        <!-- Columna derecha: estadísticas, comprobante y accesos -->
        <div class="perfil-aside">

          <!-- Estadísticas reales del vecino -->
          <div class="dash-section">
            <h2 class="dash-section__title" style="margin-bottom:16px">Mis estadísticas</h2>
            <div class="perfil-stats">
              <div class="perfil-stat">
                <p class="perfil-stat__num"><?= $total ?></p>
                <p class="perfil-stat__label">Reportes totales</p>
              </div>
              <div class="perfil-stat">
                <p class="perfil-stat__num"><?= $atendidos ?></p>
                <p class="perfil-stat__label">Atendidos</p>
              </div>
              <div class="perfil-stat">
                <p class="perfil-stat__num"><?= $en_proceso ?></p>
                <p class="perfil-stat__label">En proceso</p>
              </div>
            </div>
            <div class="perfil-progress">
              <div class="perfil-progress__label">
                <span>Tasa de resolución</span>
                <span class="perfil-progress__pct"><?= $tasa ?>%</span>
              </div>
              <div class="perfil-progress__bar">
                <div class="perfil-progress__fill" style="width:<?= $tasa ?>%"></div>
              </div>
            </div>
          </div>

          <!-- Estado del comprobante de domicilio -->
          <div class="dash-section perfil-comprobante">
            <h2 class="dash-section__title" style="margin-bottom:16px">Comprobante de domicilio</h2>
            <?php if ($comprobante_path): ?>
              <div class="comprobante-status comprobante-status--verificado">
                <div class="comprobante-status__icon">✓</div>
                <div>
                  <p class="comprobante-status__title">Verificado</p>
                  <p class="comprobante-status__desc">Tu comprobante fue aceptado y tu cuenta está verificada.</p>
                </div>
              </div>
            <?php else: ?>
              <div class="comprobante-status">
                <div class="comprobante-status__icon">⏳</div>
                <div>
                  <p class="comprobante-status__title">Pendiente</p>
                  <p class="comprobante-status__desc">No se encontró comprobante registrado.</p>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Acceso reciente desde bitácora -->
          <div class="dash-section">
            <h2 class="dash-section__title" style="margin-bottom:16px">Acceso reciente</h2>
            <div class="perfil-accesos">
              <?php if (empty($accesos)): ?>
                <p style="color:var(--text-muted);font-size:0.9rem">Sin registros de acceso aún.</p>
              <?php else: ?>
                <?php foreach ($accesos as $i => $acc): ?>
                  <div class="perfil-acceso">
                    <div class="perfil-acceso__dot <?= $i === 0 ? 'perfil-acceso__dot--green' : '' ?>"></div>
                    <div>
                      <p class="perfil-acceso__text">
                        <?= $acc['tipo_accion'] === 'login' ? 'Sesión iniciada' : 'Sesión cerrada' ?>
                      </p>
                      <p class="perfil-acceso__time"><?= tiempoRelativo($acc['created_at']) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>

      </div>
    </div>
  </main>

  <div class="toast" id="toast"></div>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
    function toggleEditar() {
      document.querySelectorAll('.perfil-input').forEach(i => i.disabled = false);
      document.getElementById('perfilActions').style.display  = 'flex';
      document.getElementById('passSection').style.display    = 'block';
      document.getElementById('btnEditar').style.display      = 'none';
    }

    function cancelarEdicion() {
      document.getElementById('perfilForm').reset();
      document.querySelectorAll('.perfil-input').forEach(i => i.disabled = true);
      document.getElementById('perfilActions').style.display  = 'none';
      document.getElementById('passSection').style.display    = 'none';
      document.getElementById('btnEditar').style.display      = 'inline-flex';
    }

    <?php if ($ok_perfil): ?>
      mostrarToast('Perfil actualizado correctamente.', 'success');
    <?php elseif ($error_demo): ?>
      mostrarToast('Esta cuenta demo esta protegida para mantener estable la demo publica.', 'error');
    <?php elseif ($error_perfil): ?>
      mostrarToast('Error al guardar el perfil.', 'error');
    <?php endif; ?>
  </script>

</body>
</html>
