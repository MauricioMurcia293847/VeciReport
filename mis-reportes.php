<?php
// mis-reportes.php
// Lista completa de reportes del vecino en sesión. Requiere sesión activa de tipo "vecino".
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereVecino();

$pdo        = conectar();
$usuario_id = usuarioActual();

$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Domicilio del vecino
$stmt = $pdo->prepare("SELECT num_casa, color_casa FROM vecinos WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$vecino     = $stmt->fetch();
$num_casa   = $vecino['num_casa']   ?? '—';
$color_casa = $vecino['color_casa'] ?? '—';

// Conteos para badges del sidebar y botones de filtro
$stmt = $pdo->prepare("
    SELECT
        COUNT(*)                       AS total,
        SUM(estado = 'en_proceso')     AS en_proceso,
        SUM(estado = 'atendido')       AS atendidos,
        SUM(estado = 'pendiente')      AS pendientes
    FROM reportes
    WHERE vecino_id = (SELECT id FROM vecinos WHERE usuario_id = ?)
");
$stmt->execute([$usuario_id]);
$conteos    = $stmt->fetch();
$total      = (int)($conteos['total']      ?? 0);
$en_proceso = (int)($conteos['en_proceso'] ?? 0);
$atendidos  = (int)($conteos['atendidos']  ?? 0);
$pendientes = (int)($conteos['pendientes'] ?? 0);

// Todos los reportes del vecino con trabajador asignado (puede ser NULL)
$stmt = $pdo->prepare("
    SELECT
        r.id, r.categoria, r.tipo, r.descripcion,
        r.color_casa, r.num_casa, r.estado, r.created_at,
        t.nombre      AS t_nombre,
        t.apellidos   AS t_apellidos,
        t.especialidad,
        t.telefono
    FROM reportes r
    JOIN vecinos v ON r.vecino_id = v.id
    LEFT JOIN trabajadores t ON r.trabajador_id = t.id
    WHERE v.usuario_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$usuario_id]);
$reportes = $stmt->fetchAll();

$cat_emoji = [
    'luz'          => '💡',
    'agua'         => '💧',
    'trabajadores' => '🔧',
    'otros'        => '📋',
];
$cat_class = [
    'luz'          => 'luz',
    'agua'         => 'agua',
    'trabajadores' => 'otros',
    'otros'        => 'otros',
];
$estado_cfg = [
    'pendiente'  => ['label' => '⏳ Pendiente',  'class' => 'pendiente', 'data' => 'pendiente'],
    'en_proceso' => ['label' => '⏳ En proceso', 'class' => 'proceso',   'data' => 'proceso'],
    'atendido'   => ['label' => '✓ Atendido',    'class' => 'atendido',  'data' => 'atendido'],
];

function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . round($diff / 60)    . ' min';
    if ($diff < 86400)  return 'Hace ' . round($diff / 3600)  . ' hora'  . (round($diff / 3600)  > 1 ? 's' : '');
    if ($diff < 604800) return 'Hace ' . round($diff / 86400) . ' día'   . (round($diff / 86400) > 1 ? 's' : '');
    return date('d/m/Y', strtotime($fecha));
}

// Construye el objeto JS para el modal con todos los datos de cada reporte
$reportes_js = [];
foreach ($reportes as $r) {
    $estado  = $r['estado'];
    $tl_done = [
        'pendiente'  => [true,  false, false, false],
        'en_proceso' => [true,  true,  true,  false],
        'atendido'   => [true,  true,  true,  true],
    ];
    $done = $tl_done[$estado] ?? [true, false, false, false];

    $t_tiene = !empty($r['t_nombre']);

    $reportes_js[$r['id']] = [
        'cat'      => $cat_emoji[$r['categoria']] ?? '📋',
        'id'       => '#' . str_pad($r['id'], 4, '0', STR_PAD_LEFT),
        'title'    => mb_strimwidth($r['descripcion'], 0, 65, '…'),
        'status'   => $estado === 'en_proceso' ? 'proceso' : $estado,
        'desc'     => $r['descripcion'],
        'categoria'=> ucfirst($r['categoria']),
        'tipo'     => ucfirst($r['tipo']),
        'casa'     => ucfirst($r['color_casa']) . ' · #' . $r['num_casa'],
        'fecha'    => tiempoRelativo($r['created_at']),
        'timeline' => [
            ['estado' => 'Reporte recibido',       'tiempo' => tiempoRelativo($r['created_at']), 'done' => $done[0]],
            ['estado' => 'Trabajador asignado',     'tiempo' => $done[1] ? '—' : 'Pendiente',    'done' => $done[1]],
            ['estado' => 'En proceso de atención',  'tiempo' => $done[2] ? '—' : 'Pendiente',    'done' => $done[2]],
            ['estado' => 'Problema resuelto',       'tiempo' => $done[3] ? tiempoRelativo($r['created_at']) : 'Pendiente', 'done' => $done[3]],
        ],
        'worker' => $t_tiene ? [
            'avatar' => strtoupper(substr($r['t_nombre'], 0, 1) . substr($r['t_apellidos'], 0, 1)),
            'nombre' => htmlspecialchars($r['t_nombre'] . ' ' . $r['t_apellidos']),
            'rol'    => ucfirst($r['especialidad']),
            'tel'    => $r['telefono'],
        ] : null,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Mis Reportes</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
  <link rel="stylesheet" href="Carpeta CSS/mis-reportes.css">
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
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span>Inicio</span>
      </a>

      <a href="reporte.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="16"/>
          <line x1="8" y1="12" x2="16" y2="12"/>
        </svg>
        <span>Nuevo reporte</span>
      </a>

      <a href="mis-reportes.php" class="sidebar__link sidebar__link--active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="8" y1="6" x2="21" y2="6"/>
          <line x1="8" y1="12" x2="21" y2="12"/>
          <line x1="8" y1="18" x2="21" y2="18"/>
          <line x1="3" y1="6" x2="3.01" y2="6"/>
          <line x1="3" y1="12" x2="3.01" y2="12"/>
          <line x1="3" y1="18" x2="3.01" y2="18"/>
        </svg>
        <span>Mis reportes</span>
        <?php if ($en_proceso > 0): ?>
          <span class="sidebar__badge"><?= $en_proceso ?></span>
        <?php endif; ?>
      </a>

      <a href="directorio.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <span>Trabajadores</span>
      </a>

      <p class="sidebar__section-label">Cuenta</p>

      <a href="perfil.php" class="sidebar__link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
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
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <div class="topbar__title">
        <h1>Mis reportes</h1>
        <p>Historial completo de tus incidencias</p>
      </div>
      <div class="topbar__actions">
        <a href="reporte.php" class="btn btn--primary">+ Nuevo reporte</a>
        <div class="topbar__avatar"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>


    <div class="dashboard-content">

      <!-- Filtros de estado con conteos reales -->
      <div class="mr-toolbar">
        <div class="mr-filters">
          <button class="mr-filter mr-filter--active" data-status="todos" onclick="setFiltro(this, 'todos')">
            Todos <span class="mr-filter__count"><?= $total ?></span>
          </button>
          <button class="mr-filter" data-status="pendiente" onclick="setFiltro(this, 'pendiente')">
            ⏳ Pendientes <span class="mr-filter__count"><?= $pendientes ?></span>
          </button>
          <button class="mr-filter" data-status="proceso" onclick="setFiltro(this, 'proceso')">
            ⏳ En proceso <span class="mr-filter__count"><?= $en_proceso ?></span>
          </button>
          <button class="mr-filter" data-status="atendido" onclick="setFiltro(this, 'atendido')">
            ✓ Atendidos <span class="mr-filter__count"><?= $atendidos ?></span>
          </button>
        </div>

        <div class="mr-search">
          <span class="mr-search__icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <input
            type="text"
            id="mrSearch"
            class="mr-search__input"
            placeholder="Buscar por descripción o #ID..."
            oninput="aplicarFiltros()"
          >
        </div>
      </div>


      <!-- Grid de cards generado desde la BD -->
      <div class="mr-grid" id="mrGrid">

        <?php if (empty($reportes)): ?>
          <!-- Estado vacío inicial (sin reportes en BD) -->
          <div class="mr-empty" style="display:flex; grid-column:1/-1">
            <p class="mr-empty__icon">📋</p>
            <p class="mr-empty__text">Aún no tienes reportes registrados.</p>
            <a href="reporte.php" class="btn btn--primary">Crear primer reporte</a>
          </div>
        <?php else: ?>
          <?php foreach ($reportes as $r):
            $emoji  = $cat_emoji[$r['categoria']]   ?? '📋';
            $cclass = $cat_class[$r['categoria']]   ?? 'otros';
            $ecfg   = $estado_cfg[$r['estado']]     ?? $estado_cfg['pendiente'];
            $t_tiene = !empty($r['t_nombre']);
            $t_iniciales = $t_tiene
                ? strtoupper(substr($r['t_nombre'], 0, 1) . substr($r['t_apellidos'], 0, 1))
                : '—';
            $id_pad = str_pad($r['id'], 4, '0', STR_PAD_LEFT);
          ?>
          <div class="mr-card"
               data-status="<?= $ecfg['data'] ?>"
               data-id="<?= $id_pad ?>"
               data-desc="<?= htmlspecialchars(strtolower($r['descripcion'])) ?>">

            <div class="mr-card__header">
              <div class="mr-card__cat mr-card__cat--<?= $cclass ?>">
                <span class="mr-card__cat-emoji"><?= $emoji ?></span>
                <span class="mr-card__cat-name"><?= ucfirst($r['categoria']) ?></span>
              </div>
              <span class="mr-card__status mr-card__status--<?= $ecfg['class'] ?>"><?= $ecfg['label'] ?></span>
            </div>

            <div class="mr-card__body">
              <div class="mr-card__id-row">
                <span class="mr-card__id">#<?= $id_pad ?></span>
                <span class="mr-card__tipo"><?= ucfirst($r['tipo']) ?></span>
              </div>
              <h3 class="mr-card__title"><?= htmlspecialchars(mb_strimwidth($r['descripcion'], 0, 65, '…')) ?></h3>
              <p class="mr-card__desc"><?= htmlspecialchars($r['descripcion']) ?></p>
            </div>

            <div class="mr-card__footer">
              <div class="mr-card__worker">
                <div class="mr-card__worker-avatar"><?= htmlspecialchars($t_iniciales) ?></div>
                <div>
                  <?php if ($t_tiene): ?>
                    <p class="mr-card__worker-name"><?= htmlspecialchars($r['t_nombre'] . ' ' . $r['t_apellidos']) ?></p>
                    <p class="mr-card__worker-role"><?= ucfirst($r['especialidad']) ?></p>
                  <?php else: ?>
                    <p class="mr-card__worker-name">Sin asignación</p>
                    <p class="mr-card__worker-role">Pendiente de asignar</p>
                  <?php endif; ?>
                </div>
              </div>
              <div class="mr-card__meta">
                <span class="mr-card__time"><?= tiempoRelativo($r['created_at']) ?></span>
                <button class="mr-card__btn" onclick="abrirDetalle(<?= $r['id'] ?>)">Ver detalle →</button>
              </div>
            </div>

          </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <!-- Estado vacío cuando los filtros no devuelven resultados -->
      <div class="mr-empty" id="mrEmpty" style="display:none">
        <p class="mr-empty__icon">📋</p>
        <p class="mr-empty__text">No tienes reportes en esta categoría.</p>
        <a href="reporte.php" class="btn btn--primary">Crear nuevo reporte</a>
      </div>

    </div>
  </main>


  <!-- Modal de detalle del reporte -->
  <div class="modal-overlay" id="modalOverlay" style="display:none" onclick="cerrarModalSiOverlay(event)">
    <div class="modal modal--detalle">

      <button class="modal__close" onclick="cerrarModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

      <div class="modal-det__header">
        <div class="modal-det__cat" id="modalCat"></div>
        <div>
          <div class="modal-det__id-row">
            <span class="modal-det__id" id="modalId"></span>
            <span class="mr-card__status" id="modalStatus"></span>
          </div>
          <h2 class="modal-det__title" id="modalTitle"></h2>
        </div>
      </div>

      <div class="modal-det__body">

        <div class="modal-det__row">
          <span class="modal-det__label">Descripción</span>
          <p class="modal-det__val" id="modalDesc"></p>
        </div>

        <div class="modal-det__grid">
          <div class="modal-det__cell">
            <span class="modal-det__label">Categoría</span>
            <span class="modal-det__val" id="modalCategoria"></span>
          </div>
          <div class="modal-det__cell">
            <span class="modal-det__label">Tipo</span>
            <span class="modal-det__val" id="modalTipo"></span>
          </div>
          <div class="modal-det__cell">
            <span class="modal-det__label">Casa</span>
            <span class="modal-det__val" id="modalCasa"></span>
          </div>
          <div class="modal-det__cell">
            <span class="modal-det__label">Fecha</span>
            <span class="modal-det__val" id="modalFecha"></span>
          </div>
        </div>

        <div>
          <p class="modal-det__label" style="margin-bottom:12px">Historial</p>
          <div class="modal-det__timeline" id="modalTimeline"></div>
        </div>

        <div class="modal-det__worker">
          <p class="modal-det__label">Trabajador asignado</p>
          <div class="modal-det__worker-card" id="modalWorkerCard"></div>
        </div>

      </div>
    </div>
  </div>


  <script src="Carpeta JS/funciones.js"></script>

  <script>
    /* Datos de todos los reportes generados por PHP para el modal */
    const reportesData = <?= json_encode($reportes_js, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;

    function abrirDetalle(id) {
      const r = reportesData[id];
      if (!r) return;

      document.getElementById('modalCat').textContent       = r.cat;
      document.getElementById('modalId').textContent        = r.id;
      document.getElementById('modalTitle').textContent     = r.title;
      document.getElementById('modalDesc').textContent      = r.desc;
      document.getElementById('modalCategoria').textContent = r.categoria;
      document.getElementById('modalTipo').textContent      = r.tipo;
      document.getElementById('modalCasa').textContent      = r.casa;
      document.getElementById('modalFecha').textContent     = r.fecha;

      const statusEl = document.getElementById('modalStatus');
      statusEl.className   = `mr-card__status mr-card__status--${r.status}`;
      statusEl.textContent = r.status === 'proceso' ? '⏳ En proceso'
                           : r.status === 'atendido' ? '✓ Atendido'
                           : '⏳ Pendiente';

      document.getElementById('modalTimeline').innerHTML = r.timeline.map(paso => `
        <div class="tl-item ${paso.done ? 'tl-item--done' : ''}">
          <div class="tl-item__dot"></div>
          <div class="tl-item__content">
            <p class="tl-item__estado">${paso.estado}</p>
            <p class="tl-item__tiempo">${paso.tiempo}</p>
          </div>
        </div>
      `).join('');

      const workerCard = document.getElementById('modalWorkerCard');
      if (r.worker) {
        workerCard.innerHTML = `
          <div class="mr-card__worker-avatar">${r.worker.avatar}</div>
          <div>
            <p class="modal-det__worker-name">${r.worker.nombre}</p>
            <p class="modal-det__worker-rol">${r.worker.rol}</p>
          </div>
          <a class="modal-det__worker-tel" href="tel:${r.worker.tel}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            Llamar
          </a>
        `;
      } else {
        workerCard.innerHTML = `
          <div class="mr-card__worker-avatar">—</div>
          <div>
            <p class="modal-det__worker-name">Sin asignación</p>
            <p class="modal-det__worker-rol">El admin asignará un trabajador pronto</p>
          </div>
        `;
      }

      document.getElementById('modalOverlay').style.display = 'flex';
    }

    /* Filtro activo actual */
    let filtroActivo = 'todos';

    function setFiltro(btn, status) {
      filtroActivo = status;
      document.querySelectorAll('.mr-filter').forEach(b => b.classList.remove('mr-filter--active'));
      btn.classList.add('mr-filter--active');
      aplicarFiltros();
    }

    function aplicarFiltros() {
      const texto  = document.getElementById('mrSearch').value.toLowerCase().trim();
      const cards  = document.querySelectorAll('.mr-card');
      let visibles = 0;

      cards.forEach(card => {
        const coincideStatus = filtroActivo === 'todos' || card.dataset.status === filtroActivo;
        const coincideTexto  = !texto
          || card.dataset.desc.includes(texto)
          || card.dataset.id.includes(texto);

        if (coincideStatus && coincideTexto) {
          card.style.display = '';
          visibles++;
        } else {
          card.style.display = 'none';
        }
      });

      document.getElementById('mrEmpty').style.display = visibles === 0 ? 'flex' : 'none';
      document.getElementById('mrGrid').style.display  = visibles === 0 ? 'none' : 'grid';
    }

    /* Animación de entrada escalonada para las cards */
    document.querySelectorAll('.mr-card').forEach((card, i) => {
      card.style.animationDelay = `${i * 0.06}s`;
      card.classList.add('animate-in');
    });
  </script>

</body>
</html>
