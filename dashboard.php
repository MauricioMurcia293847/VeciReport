<?php
// dashboard.php
// Panel principal del vecino. Requiere sesión activa de tipo "vecino".
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

// Si no hay sesión o no es vecino, redirige
requiereVecino();

$pdo        = conectar();
$usuario_id = usuarioActual();

// Datos del usuario en sesión
$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Datos del domicilio del vecino
$stmt = $pdo->prepare("SELECT num_casa, color_casa FROM vecinos WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$vecino = $stmt->fetch();
$num_casa   = $vecino['num_casa']   ?? '—';
$color_casa = $vecino['color_casa'] ?? '—';

// KPIs: conteo de reportes del vecino
$stmt = $pdo->prepare("
    SELECT
        COUNT(*)                                            AS total,
        SUM(estado = 'en_proceso')                         AS en_proceso,
        SUM(estado = 'atendido')                           AS atendidos,
        SUM(estado = 'pendiente')                          AS pendientes
    FROM reportes
    WHERE vecino_id = (SELECT id FROM vecinos WHERE usuario_id = ?)
");
$stmt->execute([$usuario_id]);
$kpis = $stmt->fetch();

$total     = $kpis['total']     ?? 0;
$en_proceso= $kpis['en_proceso']?? 0;
$atendidos = $kpis['atendidos'] ?? 0;
$pendientes= $kpis['pendientes']?? 0;

// Últimos 4 reportes del vecino
$stmt = $pdo->prepare("
    SELECT r.id, r.categoria, r.descripcion, r.estado, r.created_at
    FROM reportes r
    JOIN vecinos v ON r.vecino_id = v.id
    WHERE v.usuario_id = ?
    ORDER BY r.created_at DESC
    LIMIT 4
");
$stmt->execute([$usuario_id]);
$reportes_recientes = $stmt->fetchAll();

// Emojis y estilos por categoría
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
$estado_label = [
    'pendiente'  => ['label' => 'Pendiente',  'class' => 'pendiente'],
    'en_proceso' => ['label' => 'En proceso', 'class' => 'proceso'],
    'atendido'   => ['label' => 'Atendido',   'class' => 'atendido'],
];

// Trabajador asignado al reporte en proceso más reciente
$stmt = $pdo->prepare("
    SELECT r.id AS reporte_id, r.categoria,
           t.nombre, t.apellidos, t.especialidad, t.telefono, t.disponibilidad
    FROM reportes r
    JOIN vecinos v   ON r.vecino_id    = v.id
    JOIN trabajadores t ON r.trabajador_id = t.id
    WHERE v.usuario_id = ?
      AND r.estado = 'en_proceso'
      AND r.trabajador_id IS NOT NULL
    ORDER BY r.created_at DESC
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$trabajador = $stmt->fetch();

// Tiempo relativo para mostrar "Hace X horas"
function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . round($diff / 60)   . ' min';
    if ($diff < 86400)  return 'Hace ' . round($diff / 3600) . ' hora' . (round($diff/3600) > 1 ? 's' : '');
    if ($diff < 604800) return 'Hace ' . round($diff / 86400). ' día'  . (round($diff/86400) > 1 ? 's' : '');
    return date('d/m/Y', strtotime($fecha));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Mi Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
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

      <a href="dashboard.php" class="sidebar__link sidebar__link--active">
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

      <a href="mis-reportes.php" class="sidebar__link">
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

      <a href="App/controllers/UsuarioController.php?accion=logout" class="sidebar__link sidebar__link--logout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        <span>Cerrar sesión</span>
      </a>
    </nav>

    <!-- Datos reales del vecino en sesión -->
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
        <h1>Inicio</h1>
        <p>Bienvenido de vuelta, <span><?= htmlspecialchars($nombre) ?></span></p>
      </div>
      <div class="topbar__actions">
        <button class="topbar__icon-btn" onclick="toggleNotifs()" aria-label="Notificaciones">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <span class="topbar__notif-dot"></span>
        </button>

        <div class="notif-panel" id="notifPanel">
          <div class="notif-panel__header">
            <p class="notif-panel__title">Notificaciones</p>
            <button class="notif-panel__clear" onclick="limpiarNotifs()">Limpiar</button>
          </div>
          <div class="notif-panel__list">
            <?php if ($en_proceso > 0): ?>
            <div class="notif-item notif-item--unread">
              <div class="notif-item__icon notif-item__icon--yellow">⏳</div>
              <div>
                <p class="notif-item__text">Tienes <strong><?= $en_proceso ?></strong> reporte(s) en proceso</p>
                <p class="notif-item__time">Ahora</p>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($atendidos > 0): ?>
            <div class="notif-item notif-item--unread">
              <div class="notif-item__icon notif-item__icon--green">✓</div>
              <div>
                <p class="notif-item__text">Tienes <strong><?= $atendidos ?></strong> reporte(s) atendidos</p>
                <p class="notif-item__time">Revisalos en "Mis reportes"</p>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($total === 0): ?>
            <div class="notif-item">
              <div class="notif-item__icon notif-item__icon--blue">ℹ</div>
              <div>
                <p class="notif-item__text">No tienes reportes aún. ¡Crea tu primero!</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="topbar__avatar"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <!-- KPIs con datos reales -->
      <div class="kpi-grid">

        <div class="kpi-card kpi-card--total">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Total reportes</p>
            <p class="kpi-card__num"><?= $total ?></p>
          </div>
        </div>

        <div class="kpi-card kpi-card--proceso">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">En proceso</p>
            <p class="kpi-card__num"><?= $en_proceso ?></p>
          </div>
        </div>

        <div class="kpi-card kpi-card--atendidos">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Atendidos</p>
            <p class="kpi-card__num"><?= $atendidos ?></p>
          </div>
        </div>

        <div class="kpi-card kpi-card--pendientes">
          <div class="kpi-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div class="kpi-card__info">
            <p class="kpi-card__label">Pendientes</p>
            <p class="kpi-card__num"><?= $pendientes ?></p>
          </div>
        </div>

      </div>

      <div class="dashboard-grid">

        <!-- Reportes recientes reales -->
        <section class="dash-section dash-section--reportes">
          <div class="dash-section__header">
            <h2 class="dash-section__title">Mis reportes recientes</h2>
            <a href="mis-reportes.php" class="dash-section__link">Ver todos →</a>
          </div>

          <div class="reportes-list">
            <?php if (empty($reportes_recientes)): ?>
              <p style="color:var(--text-muted);font-size:0.9rem;padding:20px 0">
                Aún no tienes reportes. ¡Crea tu primero!
              </p>
            <?php else: ?>
              <?php foreach ($reportes_recientes as $r): ?>
                <?php
                  $emoji  = $cat_emoji[$r['categoria']] ?? '📋';
                  $clase  = $cat_class[$r['categoria']]  ?? 'otros';
                  $estado = $estado_label[$r['estado']]  ?? ['label'=>$r['estado'],'class'=>'pendiente'];
                ?>
                <div class="reporte-item">
                  <div class="reporte-item__cat reporte-item__cat--<?= $clase ?>"><?= $emoji ?></div>
                  <div class="reporte-item__info">
                    <p class="reporte-item__title"><?= htmlspecialchars(mb_strimwidth($r['descripcion'], 0, 55, '...')) ?></p>
                    <p class="reporte-item__meta">Reporte #<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?> · <?= tiempoRelativo($r['created_at']) ?></p>
                  </div>
                  <span class="reporte-item__status reporte-item__status--<?= $estado['class'] ?>"><?= $estado['label'] ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>

        <!-- Columna derecha -->
        <div class="dash-right">

          <!-- Nuevo reporte rápido -->
          <section class="dash-section dash-section--nuevo">
            <h2 class="dash-section__title">Nuevo reporte</h2>
            <p class="dash-section__desc">¿Hay un problema en tu comunidad? Repórtalo ahora.</p>
            <div class="quick-cats">
              <a href="reporte.php?tipo=luz" class="quick-cat quick-cat--luz"><span>💡</span> Luz</a>
              <a href="reporte.php?tipo=agua" class="quick-cat quick-cat--agua"><span>💧</span> Agua</a>
              <a href="reporte.php?tipo=trabajadores" class="quick-cat quick-cat--trabajadores"><span>🔧</span> Trabajadores</a>
              <a href="reporte.php?tipo=otros" class="quick-cat quick-cat--otros"><span>📋</span> Otros</a>
            </div>
            <a href="reporte.php" class="btn btn--primary btn--full-w">Levantar reporte completo</a>
          </section>

          <!-- Trabajador asignado (si existe) -->
          <?php if ($trabajador): ?>
            <?php
              $t_iniciales = strtoupper(substr($trabajador['nombre'],0,1) . substr($trabajador['apellidos'],0,1));
            ?>
          <section class="dash-section dash-section--trabajador">
            <h2 class="dash-section__title">Trabajador asignado</h2>
            <p class="dash-section__desc">Para tu reporte #<?= str_pad($trabajador['reporte_id'],4,'0',STR_PAD_LEFT) ?> (<?= ucfirst($trabajador['categoria']) ?>)</p>
            <div class="worker-card">
              <div class="worker-card__avatar"><?= $t_iniciales ?></div>
              <div class="worker-card__info">
                <p class="worker-card__name"><?= htmlspecialchars($trabajador['nombre'] . ' ' . $trabajador['apellidos']) ?></p>
                <p class="worker-card__role"><?= ucfirst($trabajador['especialidad']) ?></p>
                <a href="tel:<?= htmlspecialchars($trabajador['telefono']) ?>" class="worker-card__tel">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                  </svg>
                  <?= htmlspecialchars($trabajador['telefono']) ?>
                </a>
              </div>
              <div class="worker-card__status">
                <span class="badge__dot"></span> <?= ucfirst($trabajador['disponibilidad']) ?>
              </div>
            </div>
          </section>
          <?php else: ?>
          <section class="dash-section dash-section--trabajador">
            <h2 class="dash-section__title">Trabajador asignado</h2>
            <p class="dash-section__desc" style="color:var(--text-muted)">
              Ninguno de tus reportes tiene un trabajador asignado aún.
            </p>
          </section>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </main>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('sidebar--open');
      document.getElementById('sidebarOverlay').classList.toggle('overlay--visible');
    }

    function toggleNotifs() {
      document.getElementById('notifPanel').classList.toggle('notif-panel--open');
    }

    function limpiarNotifs() {
      document.querySelectorAll('.notif-item--unread').forEach(el => el.classList.remove('notif-item--unread'));
      document.querySelector('.topbar__notif-dot').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
      const panel = document.getElementById('notifPanel');
      const btn   = document.querySelector('.topbar__icon-btn');
      if (!panel.contains(e.target) && !btn.contains(e.target)) {
        panel.classList.remove('notif-panel--open');
      }
    });

    document.querySelectorAll('.kpi-card').forEach((card, i) => {
      card.style.animationDelay = `${i * 0.08}s`;
      card.classList.add('animate-in');
    });
  </script>

</body>
</html>