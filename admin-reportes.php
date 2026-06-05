<?php
// admin-reportes.php
// Todos los reportes del fraccionamiento. Solo accesible para admin.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereAdmin();

$pdo        = conectar();
$nombre     = $_SESSION['nombre'];
$apellidos  = $_SESSION['apellidos'];
$iniciales  = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Conteos globales para filtros
$stmt = $pdo->query("
    SELECT
        COUNT(*)                       AS total,
        SUM(estado = 'pendiente')      AS pendientes,
        SUM(estado = 'en_proceso')     AS en_proceso,
        SUM(estado = 'atendido')       AS atendidos
    FROM reportes
");
$conteos    = $stmt->fetch();
$total      = (int)($conteos['total']      ?? 0);
$pendientes = (int)($conteos['pendientes'] ?? 0);
$en_proceso = (int)($conteos['en_proceso'] ?? 0);
$atendidos  = (int)($conteos['atendidos']  ?? 0);
$sidebar_badge = $pendientes + $en_proceso;

// Todos los reportes con datos del vecino y trabajador asignado
$stmt = $pdo->query("
    SELECT
        r.id, r.categoria, r.tipo, r.descripcion, r.estado, r.created_at,
        u.nombre  AS v_nombre,
        u.apellidos AS v_apellidos,
        t.id      AS t_id,
        t.nombre  AS t_nombre,
        t.apellidos AS t_apellidos,
        t.especialidad
    FROM reportes r
    JOIN  vecinos   v ON r.vecino_id    = v.id
    JOIN  usuarios  u ON v.usuario_id   = u.id
    LEFT JOIN trabajadores t ON r.trabajador_id = t.id
    ORDER BY
        FIELD(r.estado, 'pendiente', 'en_proceso', 'atendido'),
        r.created_at DESC
");
$reportes = $stmt->fetchAll();

// Trabajadores disponibles para el dropdown de asignación
$stmt       = $pdo->query("SELECT id, nombre, apellidos, especialidad FROM trabajadores ORDER BY especialidad, nombre");
$trabajadores = $stmt->fetchAll();

$cat_emoji = ['luz'=>'💡','agua'=>'💧','trabajadores'=>'🔧','otros'=>'📋'];
$cat_class = ['luz'=>'luz','agua'=>'agua','trabajadores'=>'trabajadores','otros'=>'otros'];
$estado_cfg = [
    'pendiente'  => ['label'=>'⏳ Pendiente',  'class'=>'pendiente', 'data'=>'pendiente'],
    'en_proceso' => ['label'=>'⏳ En proceso', 'class'=>'proceso',   'data'=>'proceso'],
    'atendido'   => ['label'=>'✓ Atendido',    'class'=>'atendido',  'data'=>'atendido'],
];

function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . round($diff / 60)    . ' min';
    if ($diff < 86400)  return 'Hace ' . round($diff / 3600)  . ' hora'  . (round($diff/3600)  > 1 ? 's' : '');
    if ($diff < 604800) return 'Hace ' . round($diff / 86400) . ' día'   . (round($diff/86400) > 1 ? 's' : '');
    return date('d/m/Y', strtotime($fecha));
}

// Mensajes desde el controlador
$ok    = $_GET['ok']    ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Todos los Reportes</title>
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
      <a href="admin-reportes.php" class="sidebar__link sidebar__link--active">
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
      <a href="directorio-admin.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Trabajadores</span>
      </a>
      <a href="admin-bitacora.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <span>Bitácora</span>
      </a>
      <p class="sidebar__section-label">Cuenta</p>
      <a href="App/controllers/UsuarioController.php?accion=logout" class="sidebar__link sidebar__link--logout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>Cerrar sesión</span>
      </a>
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
        <h1>Todos los reportes</h1>
        <p>Gestión completa de incidencias del fraccionamiento</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar topbar__avatar--admin"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <div class="ar-toolbar">
        <div class="mr-filters">
          <button class="mr-filter mr-filter--active" data-status="todos" onclick="setFiltro(this,'todos')">
            Todos <span class="mr-filter__count"><?= $total ?></span>
          </button>
          <button class="mr-filter" data-status="pendiente" onclick="setFiltro(this,'pendiente')">
            ⏳ Pendientes <span class="mr-filter__count"><?= $pendientes ?></span>
          </button>
          <button class="mr-filter" data-status="proceso" onclick="setFiltro(this,'proceso')">
            ⏳ En proceso <span class="mr-filter__count"><?= $en_proceso ?></span>
          </button>
          <button class="mr-filter" data-status="atendido" onclick="setFiltro(this,'atendido')">
            ✓ Atendidos <span class="mr-filter__count"><?= $atendidos ?></span>
          </button>
        </div>

        <div class="ar-search-wrap">
          <select class="ar-select" id="filtroCategoria" onchange="aplicarFiltros()">
            <option value="todas">Todas las categorías</option>
            <option value="luz">💡 Luz</option>
            <option value="agua">💧 Agua</option>
            <option value="trabajadores">🔧 Trabajadores</option>
            <option value="otros">📋 Otros</option>
          </select>
          <div class="mr-search">
            <span class="mr-search__icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" id="arSearch" class="mr-search__input" placeholder="Buscar reporte o vecino..." oninput="aplicarFiltros()">
          </div>
        </div>
      </div>

      <div class="ar-cards-grid" id="arCardsGrid">

        <?php foreach ($reportes as $r):
          $ecfg      = $estado_cfg[$r['estado']] ?? $estado_cfg['pendiente'];
          $emoji     = $cat_emoji[$r['categoria']]  ?? '📋';
          $cclass    = $cat_class[$r['categoria']]   ?? 'otros';
          $id_pad    = str_pad($r['id'], 4, '0', STR_PAD_LEFT);
          $v_nom     = htmlspecialchars($r['v_nombre'] . ' ' . $r['v_apellidos']);
          $v_ini     = strtoupper(substr($r['v_nombre'], 0, 1) . substr($r['v_apellidos'], 0, 1));
          $atendido  = $r['estado'] === 'atendido';
          $data_txt  = strtolower("{$r['v_nombre']} {$r['v_apellidos']} {$r['descripcion']} {$r['categoria']}");
        ?>
        <div class="ar-card <?= $atendido ? 'ar-card--atendido' : '' ?>"
             data-status="<?= $ecfg['data'] ?>"
             data-cat="<?= $r['categoria'] ?>"
             data-texto="<?= htmlspecialchars($data_txt) ?>">

          <div class="ar-card__header">
            <div class="ar-card__left">
              <span class="ar-card__id">#<?= $id_pad ?></span>
              <span class="admin-table__cat admin-table__cat--<?= $cclass ?>"><?= $emoji ?> <?= ucfirst($r['categoria']) ?></span>
              <span class="admin-table__tipo admin-table__tipo--<?= $r['tipo'] === 'individual' ? 'ind' : 'col' ?>"><?= ucfirst($r['tipo']) ?></span>
            </div>
            <span class="mr-item__status mr-item__status--<?= $ecfg['class'] ?>"><?= $ecfg['label'] ?></span>
          </div>

          <p class="ar-card__desc"><?= htmlspecialchars(mb_strimwidth($r['descripcion'], 0, 80, '…')) ?></p>

          <div class="ar-card__meta">
            <div class="ar-card__vecino">
              <div class="admin-table__avatar"><?= $v_ini ?></div>
              <span><?= $v_nom ?></span>
            </div>
            <span class="ar-card__fecha"><?= tiempoRelativo($r['created_at']) ?></span>
          </div>

          <?php if ($r['t_id']): ?>
            <!-- Trabajador ya asignado -->
            <div class="ar-card__worker">
              <div class="admin-table__w-avatar"><?= strtoupper(substr($r['t_nombre'],0,1).substr($r['t_apellidos'],0,1)) ?></div>
              <span><?= htmlspecialchars($r['t_nombre'] . ' ' . $r['t_apellidos']) ?> — <?= ucfirst($r['especialidad']) ?></span>
            </div>
          <?php elseif (!$atendido): ?>
            <!-- Sin trabajador: mostrar dropdown de asignación -->
            <form method="POST" action="App/controllers/ReporteController.php" style="margin:0">
              <input type="hidden" name="accion"      value="asignar">
              <input type="hidden" name="reporte_id"  value="<?= $r['id'] ?>">
              <input type="hidden" name="notas"        value="">
              <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars(generarCSRF()) ?>">
              <div class="ar-card__assign">
                <label class="ar-card__assign-label">Asignar trabajador</label>
                <select name="trabajador_id" class="admin-table__assign" onchange="this.form.submit()">
                  <option value="">Seleccionar...</option>
                  <?php foreach ($trabajadores as $t): ?>
                    <option value="<?= $t['id'] ?>">
                      <?= htmlspecialchars($t['nombre'] . ' ' . $t['apellidos']) ?> — <?= ucfirst($t['especialidad']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>
          <?php endif; ?>

          <?php if (!$atendido): ?>
            <div class="ar-card__actions">
              <form method="POST" action="App/controllers/ReporteController.php" style="margin:0">
                <input type="hidden" name="accion"     value="atender">
                <input type="hidden" name="reporte_id" value="<?= $r['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">
                <button type="submit" class="admin-btn admin-btn--resolve">✓ Marcar atendido</button>
              </form>
            </div>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>

        <?php if (empty($reportes)): ?>
          <p style="color:var(--text-muted);padding:24px 0;grid-column:1/-1">No hay reportes registrados aún.</p>
        <?php endif; ?>

      </div>

      <div class="dir-empty" id="arEmpty" style="display:none">
        <p class="dir-empty__icon">📋</p>
        <p class="dir-empty__text">No se encontraron reportes con ese criterio.</p>
      </div>

    </div>
  </main>

  <div class="toast" id="toast"></div>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
    let filtroStatus = 'todos';

    function setFiltro(btn, status) {
      filtroStatus = status;
      document.querySelectorAll('.mr-filter').forEach(b => b.classList.remove('mr-filter--active'));
      btn.classList.add('mr-filter--active');
      aplicarFiltros();
    }

    function aplicarFiltros() {
      const texto  = document.getElementById('arSearch').value.toLowerCase().trim();
      const cat    = document.getElementById('filtroCategoria').value;
      const cards  = document.querySelectorAll('.ar-card');
      let visibles = 0;

      cards.forEach(card => {
        const matchStatus = filtroStatus === 'todos' || card.dataset.status === filtroStatus;
        const matchCat    = cat === 'todas'           || card.dataset.cat    === cat;
        const matchTexto  = !texto                    || card.dataset.texto.includes(texto);

        if (matchStatus && matchCat && matchTexto) {
          card.style.display = '';
          visibles++;
        } else {
          card.style.display = 'none';
        }
      });

      document.getElementById('arEmpty').style.display     = visibles === 0 ? 'flex' : 'none';
      document.getElementById('arCardsGrid').style.display = visibles === 0 ? 'none' : 'grid';
    }

    <?php if ($ok === 'atendido'): ?>
      mostrarToast('Reporte marcado como atendido ✓', 'success');
    <?php elseif ($ok === 'asignado'): ?>
      mostrarToast('Trabajador asignado correctamente ✓', 'success');
    <?php elseif ($error): ?>
      mostrarToast('Ocurrió un error. Intenta de nuevo.', 'error');
    <?php endif; ?>
  </script>

</body>
</html>
