<?php
// admin-bitacora.php
// Bitácora completa de acciones del sistema. Solo admin.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereAdmin();

$pdo       = conectar();
$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Badge del sidebar
$sidebar_badge = (int) $pdo->query("SELECT COUNT(*) FROM reportes WHERE estado IN ('pendiente','en_proceso')")->fetchColumn();

// Bitácora con nombre del usuario que ejecutó la acción (puede ser NULL para sistema)
$stmt = $pdo->query("
    SELECT
        b.id, b.tipo_accion, b.descripcion, b.ip, b.created_at,
        u.nombre    AS u_nombre,
        u.apellidos AS u_apellidos,
        u.rol       AS u_rol
    FROM bitacora b
    LEFT JOIN usuarios u ON b.usuario_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 200
");
$entradas = $stmt->fetchAll();

// Mapeo de tipo_accion → grupo de filtro JS, icono, color y badge
$tipo_cfg = [
    'reporte_creado'   => ['grupo'=>'reporte',    'icono'=>'📋', 'color'=>'blue',   'badge'=>'Reporte',    'clase'=>'reporte'],
    'reporte_atendido' => ['grupo'=>'reporte',    'icono'=>'✓',  'color'=>'green',  'badge'=>'Reporte',    'clase'=>'reporte'],
    'asignacion'       => ['grupo'=>'asignacion', 'icono'=>'🔧', 'color'=>'green',  'badge'=>'Asignación', 'clase'=>'asignacion'],
    'login'            => ['grupo'=>'usuario',    'icono'=>'🔑', 'color'=>'yellow', 'badge'=>'Acceso',     'clase'=>'usuario'],
    'logout'           => ['grupo'=>'usuario',    'icono'=>'🚪', 'color'=>'yellow', 'badge'=>'Acceso',     'clase'=>'usuario'],
    'registro'         => ['grupo'=>'usuario',    'icono'=>'👤', 'color'=>'purple', 'badge'=>'Usuario',    'clase'=>'usuario'],
    'vecino_aprobado'  => ['grupo'=>'usuario',    'icono'=>'✓',  'color'=>'green',  'badge'=>'Usuario',    'clase'=>'usuario'],
    'vecino_bloqueado' => ['grupo'=>'usuario',    'icono'=>'🚫', 'color'=>'red',    'badge'=>'Usuario',    'clase'=>'usuario'],
    'sistema'          => ['grupo'=>'sistema',    'icono'=>'⚙️','color'=>'yellow', 'badge'=>'Sistema',    'clase'=>'sistema'],
];
$default_cfg = ['grupo'=>'sistema', 'icono'=>'⚙️', 'color'=>'yellow', 'badge'=>'Sistema', 'clase'=>'sistema'];

function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . round($diff / 60)    . ' min';
    if ($diff < 86400)  return 'Hace ' . round($diff / 3600)  . ' hora'  . (round($diff/3600)  > 1 ? 's' : '');
    if ($diff < 604800) return 'Hace ' . round($diff / 86400) . ' día'   . (round($diff/86400) > 1 ? 's' : '');
    return date('d/m/Y H:i', strtotime($fecha));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Bitácora</title>
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
      <a href="admin-vecinos.php" class="sidebar__link">
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
      <a href="admin-bitacora.php" class="sidebar__link sidebar__link--active">
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
        <h1>Bitácora del sistema</h1>
        <p>Registro de todas las acciones realizadas · <?= count($entradas) ?> entradas</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar topbar__avatar--admin"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <div class="ar-toolbar">
        <div class="mr-filters">
          <button class="mr-filter mr-filter--active" data-tipo="todos"      onclick="setFiltro(this,'todos')">Todos</button>
          <button class="mr-filter"                   data-tipo="reporte"    onclick="setFiltro(this,'reporte')">📋 Reportes</button>
          <button class="mr-filter"                   data-tipo="usuario"    onclick="setFiltro(this,'usuario')">👤 Usuarios</button>
          <button class="mr-filter"                   data-tipo="asignacion" onclick="setFiltro(this,'asignacion')">🔧 Asignaciones</button>
          <button class="mr-filter"                   data-tipo="sistema"    onclick="setFiltro(this,'sistema')">⚙️ Sistema</button>
        </div>
        <div class="mr-search">
          <span class="mr-search__icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </span>
          <input type="text" id="bitSearch" class="mr-search__input" placeholder="Buscar en bitácora..." oninput="filtrarBitacora()">
        </div>
      </div>

      <div class="dash-section">
        <div class="bitacora-list" id="bitacoraList">

          <?php if (empty($entradas)): ?>
            <p style="color:var(--text-muted);padding:24px 0">La bitácora está vacía.</p>
          <?php endif; ?>

          <?php foreach ($entradas as $e):
            $cfg      = $tipo_cfg[$e['tipo_accion']] ?? $default_cfg;
            $actor    = $e['u_nombre']
                          ? htmlspecialchars($e['u_nombre'] . ' ' . $e['u_apellidos'])
                          : 'Sistema';
            $data_txt = strtolower(($e['descripcion'] ?? '') . ' ' . $actor);
          ?>
          <div class="bit-item"
               data-tipo="<?= $cfg['grupo'] ?>"
               data-texto="<?= htmlspecialchars($data_txt) ?>">

            <div class="bit-item__icon bit-item__icon--<?= $cfg['color'] ?>"><?= $cfg['icono'] ?></div>

            <div class="bit-item__content">
              <p class="bit-item__texto"><?= htmlspecialchars($e['descripcion'] ?? '—') ?></p>
              <p class="bit-item__meta">
                <?= tiempoRelativo($e['created_at']) ?>
                <?php if ($e['ip']): ?> · IP: <?= htmlspecialchars($e['ip']) ?><?php endif; ?>
                · <?= $actor ?>
              </p>
            </div>

            <span class="bit-item__badge bit-item__badge--<?= $cfg['clase'] ?>"><?= $cfg['badge'] ?></span>

          </div>
          <?php endforeach; ?>

        </div>

        <div class="dir-empty" id="bitEmpty" style="display:none">
          <p class="dir-empty__icon">📖</p>
          <p class="dir-empty__text">No se encontraron registros con ese criterio.</p>
        </div>
      </div>

    </div>
  </main>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
    let filtroTipo = 'todos';

    function setFiltro(btn, tipo) {
      filtroTipo = tipo;
      document.querySelectorAll('.mr-filter').forEach(b => b.classList.remove('mr-filter--active'));
      btn.classList.add('mr-filter--active');
      filtrarBitacora();
    }

    function filtrarBitacora() {
      const texto  = document.getElementById('bitSearch').value.toLowerCase();
      const items  = document.querySelectorAll('.bit-item');
      let visibles = 0;

      items.forEach(item => {
        const matchTipo  = filtroTipo === 'todos' || item.dataset.tipo  === filtroTipo;
        const matchTexto = !texto                 || item.dataset.texto.includes(texto);

        if (matchTipo && matchTexto) {
          item.style.display = '';
          visibles++;
        } else {
          item.style.display = 'none';
        }
      });

      document.getElementById('bitEmpty').style.display = visibles === 0 ? 'flex' : 'none';
    }
  </script>

</body>
</html>
