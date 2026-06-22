<?php
// admin.php — Panel de resumen del administrador. Solo accesible para admin.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereAdmin();

$pdo       = conectar();
$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// KPIs de reportes en una sola consulta
$row = $pdo->query("
    SELECT
        COUNT(*)                                         AS total,
        SUM(estado = 'pendiente')                        AS pendientes,
        SUM(estado = 'en_proceso')                       AS en_proceso,
        SUM(estado = 'atendido')                         AS atendidos,
        SUM(tipo = 'colectivo' AND estado != 'atendido') AS colectivos
    FROM reportes
")->fetch();

$total_reportes     = (int)($row['total']      ?? 0);
$pendientes         = (int)($row['pendientes'] ?? 0);
$en_proceso         = (int)($row['en_proceso'] ?? 0);
$atendidos          = (int)($row['atendidos']  ?? 0);
$colectivos_activos = (int)($row['colectivos'] ?? 0);
$sidebar_badge      = $pendientes + $en_proceso;
$pct_resueltos      = $total_reportes > 0 ? round($atendidos / $total_reportes * 100) : 0;

// Vecinos activos
$total_vecinos = (int) $pdo->query(
    "SELECT COUNT(*) FROM usuarios WHERE rol = 'vecino' AND estado = 'activo'"
)->fetchColumn();

// Trabajadores: total y disponibles
$tw = $pdo->query(
    "SELECT COUNT(*) AS total, SUM(disponibilidad = 'disponible') AS lib FROM trabajadores"
)->fetch();
$total_trab    = (int)($tw['total'] ?? 0);
$trab_libres   = (int)($tw['lib']   ?? 0);
$trab_ocupados = $total_trab - $trab_libres;

// Reportes activos (pendiente + en_proceso) para el panel central — máximo 6
$reportes_activos = $pdo->query("
    SELECT r.id, r.categoria, r.tipo, r.descripcion, r.estado, r.created_at,
           f.nombre AS fraccionamiento,
           u.nombre AS v_nombre, u.apellidos AS v_apellidos,
           t.id AS t_id, t.nombre AS t_nombre, t.apellidos AS t_apellidos, t.especialidad
    FROM   reportes r
    JOIN   vecinos   v ON r.vecino_id    = v.id
    JOIN   fraccionamientos f ON f.id = v.fraccionamiento_id
    JOIN   usuarios  u ON v.usuario_id   = u.id
    LEFT JOIN trabajadores t ON r.trabajador_id = t.id
    WHERE  r.estado IN ('pendiente','en_proceso')
    ORDER  BY FIELD(r.estado,'en_proceso','pendiente'), r.created_at DESC
    LIMIT  6
")->fetchAll();

// Trabajadores para el panel lateral y los dropdowns de asignación
$trabajadores = $pdo->query("
    SELECT id, nombre, apellidos, especialidad, disponibilidad
    FROM   trabajadores
    ORDER  BY disponibilidad DESC, especialidad, nombre
")->fetchAll();

// Vecinos más recientes (últimos 5)
$vecinos_recientes = $pdo->query("
    SELECT u.id AS uid, u.nombre, u.apellidos, u.estado, u.created_at, v.num_casa, f.nombre AS fraccionamiento
    FROM   usuarios u
    JOIN   vecinos  v ON v.usuario_id = u.id
    JOIN   fraccionamientos f ON f.id = v.fraccionamiento_id
    WHERE  u.rol = 'vecino'
    ORDER  BY u.created_at DESC
    LIMIT  5
")->fetchAll();

$cat_emoji  = ['luz'=>'💡','agua'=>'💧','trabajadores'=>'🔧','otros'=>'📋'];
$cat_class  = ['luz'=>'luz','agua'=>'agua','trabajadores'=>'trabajadores','otros'=>'otros'];
$estado_cfg = [
    'pendiente'  => ['label'=>'⏳ Pendiente',  'class'=>'pendiente'],
    'en_proceso' => ['label'=>'⏳ En proceso', 'class'=>'proceso'],
];
$esp_emoji     = ['electricista'=>'⚡','plomero'=>'🔧','albanil'=>'🏗️','jardinero'=>'🌿','general'=>'🛠️'];
$worker_colors = ['--green','--yellow','--orange','--purple'];
$vecino_colors = ['','--blue','--yellow','--orange','--purple'];

function tiempoRelativo(string $fecha): string {
    $d = time() - strtotime($fecha);
    if ($d < 60)     return 'Hace un momento';
    if ($d < 3600)   return 'Hace ' . round($d / 60)    . ' min';
    if ($d < 86400)  return 'Hace ' . round($d / 3600)  . ' hora'  . (round($d / 3600)  > 1 ? 's' : '');
    if ($d < 604800) return 'Hace ' . round($d / 86400) . ' día'   . (round($d / 86400) > 1 ? 's' : '');
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
  <title>VeciReport - Panel Administrador</title>
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

      <a href="admin.php" class="sidebar__link sidebar__link--active">
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
        <h1>Panel de administración</h1>
        <p>Resumen general del fraccionamiento</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar topbar__avatar--admin"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <!-- KPIs: 6 métricas reales del fraccionamiento -->
      <div class="kpi-grid kpi-grid--admin">

        <div class="kpi-card kpi-card--total">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Total reportes</p>
            <p class="kpi-card__num"><?= $total_reportes ?></p>
            <p class="kpi-card__trend kpi-card__trend--neutral"><?= $pct_resueltos ?>% resueltos</p>
          </div>
        </div>

        <div class="kpi-card kpi-card--proceso">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">En proceso</p>
            <p class="kpi-card__num"><?= $en_proceso ?></p>
            <p class="kpi-card__trend kpi-card__trend--<?= $pendientes > 0 ? 'down' : 'neutral' ?>">
              <?= $pendientes > 0 ? "↓ {$pendientes} sin asignar" : 'Sin pendientes' ?>
            </p>
          </div>
        </div>

        <div class="kpi-card kpi-card--atendidos">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Atendidos</p>
            <p class="kpi-card__num"><?= $atendidos ?></p>
            <p class="kpi-card__trend kpi-card__trend--up">↑ <?= $pct_resueltos ?>% del total</p>
          </div>
        </div>

        <div class="kpi-card kpi-card--vecinos">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Vecinos activos</p>
            <p class="kpi-card__num"><?= $total_vecinos ?></p>
            <p class="kpi-card__trend kpi-card__trend--neutral">En el fraccionamiento</p>
          </div>
        </div>

        <div class="kpi-card kpi-card--trabajadores-kpi">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Trabajadores</p>
            <p class="kpi-card__num"><?= $total_trab ?></p>
            <p class="kpi-card__trend kpi-card__trend--<?= $trab_ocupados > 0 ? 'neutral' : 'up' ?>">
              <?= $trab_ocupados ?> ocupado<?= $trab_ocupados !== 1 ? 's' : '' ?>
            </p>
          </div>
        </div>

        <div class="kpi-card kpi-card--colectivos">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Reportes colectivos</p>
            <p class="kpi-card__num"><?= $colectivos_activos ?></p>
            <p class="kpi-card__trend kpi-card__trend--<?= $colectivos_activos > 0 ? 'down' : 'neutral' ?>">
              <?= $colectivos_activos > 0 ? '↓ Requieren atención' : 'Ninguno activo' ?>
            </p>
          </div>
        </div>

      </div>


      <!-- Grid principal: reportes activos + columna derecha -->
      <div class="admin-grid">

        <!-- Reportes pendientes / en proceso -->
        <section class="dash-section admin-section--reportes">
          <div class="dash-section__header">
            <h2 class="dash-section__title">
              Reportes activos
              <?php if ($sidebar_badge > 0): ?>
                <span style="font-size:0.78rem;font-weight:600;color:var(--accent);margin-left:8px"><?= $sidebar_badge ?> sin resolver</span>
              <?php endif; ?>
            </h2>
            <a href="admin-reportes.php" class="dash-section__link">Ver todos →</a>
          </div>

          <?php if (empty($reportes_activos)): ?>
            <p style="color:var(--text-muted);padding:24px 0;text-align:center">No hay reportes activos. Todo está resuelto ✓</p>
          <?php else: ?>
          <div class="ar-cards-grid ar-cards-grid--compact">

            <?php foreach ($reportes_activos as $r):
              $ecfg   = $estado_cfg[$r['estado']] ?? $estado_cfg['pendiente'];
              $emoji  = $cat_emoji[$r['categoria']]  ?? '📋';
              $cclass = $cat_class[$r['categoria']]   ?? 'otros';
              $id_pad = str_pad($r['id'], 4, '0', STR_PAD_LEFT);
              $v_ini  = strtoupper(substr($r['v_nombre'], 0, 1) . substr($r['v_apellidos'], 0, 1));
              $v_nom  = htmlspecialchars($r['v_nombre'] . ' ' . $r['v_apellidos']);
            ?>
            <div class="ar-card" data-status="<?= $r['estado'] ?>">

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
                <span class="ar-card__fecha"><?= htmlspecialchars($r['fraccionamiento']) ?></span>
                <span class="ar-card__fecha"><?= tiempoRelativo($r['created_at']) ?></span>
              </div>

              <?php if ($r['t_id']): ?>
                <div class="ar-card__worker">
                  <div class="admin-table__w-avatar"><?= strtoupper(substr($r['t_nombre'], 0, 1) . substr($r['t_apellidos'], 0, 1)) ?></div>
                  <span><?= htmlspecialchars($r['t_nombre'] . ' ' . $r['t_apellidos']) ?> — <?= ucfirst($r['especialidad']) ?></span>
                </div>
              <?php else: ?>
                <form method="POST" action="App/controllers/ReporteController.php" style="margin:0">
                  <input type="hidden" name="accion"     value="asignar">
                  <input type="hidden" name="reporte_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="notas"      value="">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">
                  <div class="ar-card__assign">
                    <label class="ar-card__assign-label">Asignar trabajador</label>
                    <select name="trabajador_id" class="admin-table__assign" onchange="this.form.submit()">
                      <option value="">Seleccionar...</option>
                      <?php foreach ($trabajadores as $t): ?>
                        <option value="<?= $t['id'] ?>"<?= $t['disponibilidad'] === 'ocupado' ? ' disabled' : '' ?>>
                          <?= htmlspecialchars($t['nombre'] . ' ' . $t['apellidos']) ?> — <?= ucfirst($t['especialidad']) ?><?= $t['disponibilidad'] === 'ocupado' ? ' (ocupado)' : '' ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </form>
              <?php endif; ?>

              <div class="ar-card__actions">
                <form method="POST" action="App/controllers/ReporteController.php" style="margin:0">
                  <input type="hidden" name="accion"     value="atender">
                  <input type="hidden" name="reporte_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">
                  <button type="submit" class="admin-btn admin-btn--resolve">✓ Marcar atendido</button>
                </form>
              </div>

            </div>
            <?php endforeach; ?>

          </div>
          <?php endif; ?>
        </section>


        <!-- Columna derecha: trabajadores + vecinos recientes -->
        <div class="admin-right">

          <!-- Disponibilidad de trabajadores -->
          <section class="dash-section">
            <div class="dash-section__header">
              <h2 class="dash-section__title">Trabajadores</h2>
              <a href="directorio-admin.php" class="dash-section__link">Ver directorio →</a>
            </div>
            <div class="admin-workers">
              <?php foreach ($trabajadores as $t):
                $color    = $worker_colors[$t['id'] % count($worker_colors)];
                $t_ini    = strtoupper(substr($t['nombre'], 0, 1) . substr($t['apellidos'], 0, 1));
                $libre    = $t['disponibilidad'] === 'disponible';
                $emoji_e  = $esp_emoji[$t['especialidad']] ?? '🛠️';
              ?>
              <div class="admin-worker">
                <div class="admin-worker__avatar admin-worker__avatar<?= $color ?>"><?= $t_ini ?></div>
                <div class="admin-worker__info">
                  <p class="admin-worker__name"><?= htmlspecialchars($t['nombre'] . ' ' . $t['apellidos']) ?></p>
                  <p class="admin-worker__esp"><?= $emoji_e ?> <?= ucfirst($t['especialidad']) ?></p>
                </div>
                <span class="admin-worker__status admin-worker__status--<?= $libre ? 'disponible' : 'ocupado' ?>">
                  <?= $libre ? 'Libre' : 'Ocupado' ?>
                </span>
              </div>
              <?php endforeach; ?>
              <?php if (empty($trabajadores)): ?>
                <p style="color:var(--text-muted);font-size:0.85rem">No hay trabajadores registrados.</p>
              <?php endif; ?>
            </div>
          </section>

          <!-- Vecinos recientes -->
          <section class="dash-section admin-section--vecinos">
            <div class="dash-section__header">
              <h2 class="dash-section__title">Vecinos recientes</h2>
              <a href="admin-vecinos.php" class="dash-section__link">Ver todos →</a>
            </div>
            <div class="admin-vecinos">
              <?php foreach ($vecinos_recientes as $v):
                $v_ini      = strtoupper(substr($v['nombre'], 0, 1) . substr($v['apellidos'], 0, 1));
                $color_v    = $vecino_colors[$v['uid'] % count($vecino_colors)];
                $diff_dias  = (time() - strtotime($v['created_at'])) / 86400;
                $es_nuevo   = $diff_dias < 7;
                $badge_lbl  = $es_nuevo ? 'Nuevo' : ucfirst($v['estado']);
                $badge_cls  = $es_nuevo ? 'admin-vecino__badge--nuevo' : '';
              ?>
              <div class="admin-vecino">
                <div class="admin-vecino__avatar<?= $color_v ? ' admin-vecino__avatar' . $color_v : '' ?>"><?= $v_ini ?></div>
                <div class="admin-vecino__info">
                  <p class="admin-vecino__name"><?= htmlspecialchars($v['nombre'] . ' ' . $v['apellidos']) ?></p>
                  <p class="admin-vecino__meta"><?= htmlspecialchars($v['fraccionamiento']) ?> · Casa #<?= htmlspecialchars($v['num_casa']) ?></p>
                  <p class="admin-vecino__meta"><?= tiempoRelativo($v['created_at']) ?></p>
                </div>
                <span class="admin-vecino__badge <?= $badge_cls ?>"><?= htmlspecialchars($badge_lbl) ?></span>
              </div>
              <?php endforeach; ?>
              <?php if (empty($vecinos_recientes)): ?>
                <p style="color:var(--text-muted);font-size:0.85rem">No hay vecinos registrados aún.</p>
              <?php endif; ?>
            </div>
          </section>

        </div>

      </div>
    </div>
  </main>

  <div class="toast" id="toast"></div>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
    document.querySelectorAll('.kpi-card').forEach((card, i) => {
      card.style.animationDelay = `${i * 0.07}s`;
      card.classList.add('animate-in');
    });

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
