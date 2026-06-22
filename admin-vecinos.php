<?php
// admin-vecinos.php
// Lista de vecinos registrados con opciones de aprobar y bloquear. Solo admin.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereAdmin();

$pdo       = conectar();
$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Badge del sidebar: reportes sin resolver
$sidebar_badge = (int) $pdo->query("SELECT COUNT(*) FROM reportes WHERE estado IN ('pendiente','en_proceso')")->fetchColumn();

// Todos los vecinos con datos de usuario, domicilio y conteo de reportes
$stmt = $pdo->query("
    SELECT
        u.id        AS usuario_id,
        u.nombre,
        u.apellidos,
        u.correo,
        u.estado,
        u.created_at,
        v.num_casa,
        v.color_casa,
        v.ubicacion_lat,
        v.ubicacion_lng,
        f.nombre AS fraccionamiento,
        COUNT(r.id) AS total_reportes
    FROM usuarios u
    JOIN  vecinos  v ON v.usuario_id = u.id
    JOIN  fraccionamientos f ON f.id = v.fraccionamiento_id
    LEFT JOIN reportes r ON r.vecino_id = v.id
    WHERE u.rol = 'vecino'
    GROUP BY u.id, u.nombre, u.apellidos, u.correo, u.estado, u.created_at, v.num_casa, v.color_casa, v.ubicacion_lat, v.ubicacion_lng, f.nombre
    ORDER BY u.created_at DESC
");
$vecinos       = $stmt->fetchAll();
$total_vecinos = count($vecinos);

$badge_cfg = [
    'activo'   => ['label' => 'Activo',    'class' => 'vecino-card__badge--activo'],
    'pendiente'=> ['label' => 'Pendiente', 'class' => 'vecino-card__badge--nuevo'],
    'bloqueado'=> ['label' => 'Bloqueado', 'class' => 'vecino-card__badge--bloqueado'],
];
$avatar_colors = ['', '--purple', '--blue', '--yellow', '--orange'];

function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . round($diff / 60)    . ' min';
    if ($diff < 86400)  return 'Hace ' . round($diff / 3600)  . ' hora'  . (round($diff/3600)  > 1 ? 's' : '');
    if ($diff < 604800) return 'Hace ' . round($diff / 86400) . ' día'   . (round($diff/86400) > 1 ? 's' : '');
    return date('d/m/Y', strtotime($fecha));
}

$ok    = $_GET['ok']    ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Vecinos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
  <link rel="stylesheet" href="Carpeta CSS/admin.css">
  <link rel="stylesheet" href="Carpeta CSS/admin-reportes.css">
</head>

<body class="dashboard-page admin-page">

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
      <span class="sidebar__role-badge">Admin</span>
    </div>

    <nav class="sidebar__nav">
      <p class="sidebar__section-label">Panel</p>
      <a href="admin.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        <span>Resumen</span>
      </a>
      <a href="admin-reportes.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span>Todos los reportes</span>
        <?php if ($sidebar_badge > 0): ?>
          <span class="sidebar__badge"><?= $sidebar_badge ?></span>
        <?php endif; ?>
      </a>
      <a href="admin-vecinos.php" class="sidebar__link sidebar__link--active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Vecinos</span>
      </a>
      <a href="admin-fraccionamientos.php" class="sidebar__link">
        <span>Fraccionamientos</span>
      </a>
      <a href="directorio-admin.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Trabajadores</span>
      </a>
      <a href="admin-bitacora.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <span>Bitácora</span>
      </a>
      <p class="sidebar__section-label">Cuenta</p>
      <?= formularioLogout() ?>
    </nav>

    <div class="sidebar__user">
      <div class="sidebar__user-avatar sidebar__user-avatar--admin"><?= htmlspecialchars($iniciales) ?></div>
      <div class="sidebar__user-info">
        <p class="sidebar__user-name"><?= htmlspecialchars($nombre . ' ' . $apellidos) ?></p>
        <p class="sidebar__user-role">Administrador</p>
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
        <h1>Vecinos registrados</h1>
        <p>Directorio de vecinos del fraccionamiento</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar topbar__avatar--admin"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <div class="ar-toolbar">
        <p class="dir-count">Total: <strong><?= $total_vecinos ?> vecino<?= $total_vecinos !== 1 ? 's' : '' ?></strong></p>
        <div class="mr-search">
          <span class="mr-search__icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </span>
          <input type="text" id="vecinoSearch" class="mr-search__input" placeholder="Buscar vecino por nombre o casa..." oninput="filtrarVecinos()">
        </div>
      </div>

      <div class="vecinos-grid" id="vecinosGrid">

        <?php foreach ($vecinos as $v):
          $bdg    = $badge_cfg[$v['estado']] ?? $badge_cfg['pendiente'];
          $ini    = strtoupper(substr($v['nombre'],0,1) . substr($v['apellidos'],0,1));
          $color  = $avatar_colors[$v['usuario_id'] % count($avatar_colors)];
          $reps   = (int)$v['total_reportes'];
        ?>
        <div class="vecino-card"
             data-nombre="<?= htmlspecialchars(strtolower($v['nombre'] . ' ' . $v['apellidos'])) ?>"
             data-fraccionamiento="<?= htmlspecialchars(strtolower($v['fraccionamiento'])) ?>"
             data-casa="<?= htmlspecialchars($v['num_casa']) ?>">

          <div class="vecino-card__top">
            <div class="vecino-card__avatar<?= $color ? ' vecino-card__avatar' . $color : '' ?>"><?= $ini ?></div>
            <span class="vecino-card__badge <?= $bdg['class'] ?>"><?= $bdg['label'] ?></span>
          </div>

          <div class="vecino-card__info">
            <h3 class="vecino-card__name"><?= htmlspecialchars($v['nombre'] . ' ' . $v['apellidos']) ?></h3>
            <p class="vecino-card__meta"><?= htmlspecialchars($v['fraccionamiento']) ?> · Casa #<?= htmlspecialchars($v['num_casa']) ?></p>
            <p class="vecino-card__meta">Color: <?= htmlspecialchars(ucfirst($v['color_casa'])) ?></p>
            <?php if ($v['ubicacion_lat'] !== null && $v['ubicacion_lng'] !== null): ?>
              <p class="vecino-card__meta">Mapa: <?= number_format((float) $v['ubicacion_lat'], 7) ?>, <?= number_format((float) $v['ubicacion_lng'], 7) ?></p>
            <?php endif; ?>
            <p class="vecino-card__meta"><?= $reps ?> reporte<?= $reps !== 1 ? 's' : '' ?> · Registrado <?= tiempoRelativo($v['created_at']) ?></p>
          </div>

          <div class="vecino-card__footer">
            <span class="vecino-card__stat">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <?= $reps ?> reporte<?= $reps !== 1 ? 's' : '' ?>
            </span>

            <div style="display:flex;gap:6px;align-items:center">
              <?php if ($v['estado'] === 'pendiente'): ?>
                <form method="POST" action="App/controllers/UsuarioController.php" style="margin:0">
                  <input type="hidden" name="accion"      value="aprobar">
                  <input type="hidden" name="usuario_id"  value="<?= $v['usuario_id'] ?>">
                  <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars(generarCSRF()) ?>">
                  <button type="submit" class="admin-btn admin-btn--resolve" style="font-size:0.75rem;padding:5px 10px">✓ Aprobar</button>
                </form>
              <?php elseif ($v['estado'] === 'activo'): ?>
                <form method="POST" action="App/controllers/UsuarioController.php" style="margin:0">
                  <input type="hidden" name="accion"      value="bloquear">
                  <input type="hidden" name="usuario_id"  value="<?= $v['usuario_id'] ?>">
                  <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars(generarCSRF()) ?>">
                  <button type="submit" class="admin-btn" style="font-size:0.75rem;padding:5px 10px;background:var(--danger,#e53e3e);color:#fff;border:none;border-radius:6px;cursor:pointer">🚫 Bloquear</button>
                </form>
              <?php elseif ($v['estado'] === 'bloqueado'): ?>
                <form method="POST" action="App/controllers/UsuarioController.php" style="margin:0">
                  <input type="hidden" name="accion"      value="aprobar">
                  <input type="hidden" name="usuario_id"  value="<?= $v['usuario_id'] ?>">
                  <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars(generarCSRF()) ?>">
                  <button type="submit" class="admin-btn admin-btn--resolve" style="font-size:0.75rem;padding:5px 10px">↩ Desbloquear</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

        </div>
        <?php endforeach; ?>

        <?php if (empty($vecinos)): ?>
          <p style="color:var(--text-muted);padding:24px 0;grid-column:1/-1">No hay vecinos registrados aún.</p>
        <?php endif; ?>

      </div>

      <div class="dir-empty" id="vecinoEmpty" style="display:none">
        <p class="dir-empty__icon">👤</p>
        <p class="dir-empty__text">No se encontraron vecinos con ese criterio.</p>
      </div>

    </div>
  </main>

  <div class="toast" id="toast"></div>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
    function filtrarVecinos() {
      const texto  = document.getElementById('vecinoSearch').value.toLowerCase();
      const cards  = document.querySelectorAll('.vecino-card');
      let visibles = 0;

      cards.forEach(card => {
        const match = !texto || card.dataset.nombre.includes(texto) || card.dataset.casa.includes(texto) || card.dataset.fraccionamiento.includes(texto);
        card.style.display = match ? '' : 'none';
        if (match) visibles++;
      });

      document.getElementById('vecinoEmpty').style.display = visibles === 0 ? 'flex' : 'none';
      document.getElementById('vecinosGrid').style.display = visibles === 0 ? 'none' : 'grid';
    }

    <?php if ($ok === 'aprobado'): ?>
      mostrarToast('Vecino aprobado correctamente ✓', 'success');
    <?php elseif ($ok === 'bloqueado'): ?>
      mostrarToast('Vecino bloqueado.', 'success');
    <?php elseif ($error === 'demo'): ?>
      mostrarToast('Esta cuenta demo esta protegida para mantener estable la demo publica.', 'error');
    <?php elseif ($error): ?>
      mostrarToast('Ocurrió un error. Intenta de nuevo.', 'error');
    <?php endif; ?>
  </script>

</body>
</html>
