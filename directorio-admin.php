<?php
// directorio-admin.php — Directorio de trabajadores con asignación. Solo admin.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereAdmin();

$pdo       = conectar();
$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Badge del sidebar: reportes sin resolver
$sidebar_badge = (int) $pdo->query(
    "SELECT COUNT(*) FROM reportes WHERE estado IN ('pendiente','en_proceso')"
)->fetchColumn();

// Todos los trabajadores
$trabajadores = $pdo->query("
    SELECT id, nombre, apellidos, especialidad, telefono, disponibilidad
    FROM   trabajadores
    ORDER  BY disponibilidad DESC, especialidad, nombre
")->fetchAll();

$total_trab = count($trabajadores);

// Reportes sin trabajador asignado (para el modal de asignación)
$reportes_sin_asignar = $pdo->query("
    SELECT r.id, r.categoria, r.descripcion
    FROM   reportes r
    WHERE  r.trabajador_id IS NULL AND r.estado IN ('pendiente','en_proceso')
    ORDER  BY r.created_at DESC
")->fetchAll();

$cat_emoji = ['luz'=>'💡','agua'=>'💧','trabajadores'=>'🔧','otros'=>'📋'];
$cat_class = ['luz'=>'luz','agua'=>'agua','trabajadores'=>'trabajadores','otros'=>'otros'];
$esp_emoji = ['electricista'=>'⚡','plomero'=>'🔧','albanil'=>'🏗','jardinero'=>'🌿','general'=>'🛠'];
$esp_label = [
    'electricista'=>'Electricista','plomero'=>'Plomero',
    'albanil'=>'Albañil','jardinero'=>'Jardinero','general'=>'Mantenimiento general',
];

$ok    = $_GET['ok']    ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Directorio de Trabajadores (Admin)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
  <link rel="stylesheet" href="Carpeta CSS/admin.css">
  <link rel="stylesheet" href="Carpeta CSS/directorio.css">
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

      <a href="directorio-admin.php" class="sidebar__link sidebar__link--active">
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
        <h1>Directorio de trabajadores</h1>
        <p>Consulta, contacta y asigna a los especialistas del fraccionamiento</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar topbar__avatar--admin"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <div class="directorio-toolbar">
        <div class="search-wrap">
          <span class="search-icon">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </span>
          <input type="text" id="searchInput" class="search-input" placeholder="Buscar por nombre o especialidad..." oninput="filtrarTrabajadores()">
        </div>
        <div class="filtros">
          <button class="filtro-btn filtro-btn--active" data-filtro="todos"         onclick="setFiltro(this)">Todos</button>
          <button class="filtro-btn"                    data-filtro="electricista"  onclick="setFiltro(this)">⚡ Electricista</button>
          <button class="filtro-btn"                    data-filtro="plomero"       onclick="setFiltro(this)">🔧 Plomero</button>
          <button class="filtro-btn"                    data-filtro="albanil"       onclick="setFiltro(this)">🏗 Albañil</button>
          <button class="filtro-btn"                    data-filtro="jardinero"     onclick="setFiltro(this)">🌿 Jardinero</button>
          <button class="filtro-btn"                    data-filtro="general"       onclick="setFiltro(this)">🛠 General</button>
        </div>
      </div>

      <p class="directorio-count" id="directorioCount">
        Mostrando <strong><?= $total_trab ?></strong> trabajador<?= $total_trab !== 1 ? 'es' : '' ?>
      </p>

      <div class="trabajadores-grid" id="trabajadoresGrid">

        <?php foreach ($trabajadores as $i => $t):
          $t_ini    = strtoupper(substr($t['nombre'], 0, 1) . substr($t['apellidos'], 0, 1));
          $color_n  = ($t['id'] % 8) + 1;
          $libre    = $t['disponibilidad'] === 'disponible';
          $emoji_e  = $esp_emoji[$t['especialidad']]  ?? '🛠';
          $label_e  = $esp_label[$t['especialidad']]  ?? ucfirst($t['especialidad']);
          $tel_raw  = preg_replace('/\D/', '', $t['telefono']);
          $sin_rep  = empty($reportes_sin_asignar);
        ?>
        <div class="trabajador-card"
             data-especialidad="<?= $t['especialidad'] ?>"
             data-nombre="<?= htmlspecialchars(strtolower($t['nombre'] . ' ' . $t['apellidos'])) ?>">

          <div class="trabajador-card__header">
            <div class="trabajador-card__avatar trabajador-card__avatar--<?= $color_n ?>"><?= $t_ini ?></div>
            <div class="trabajador-card__status trabajador-card__status--<?= $libre ? 'disponible' : 'ocupado' ?>">
              <span class="status-dot"></span>
              <?= $libre ? 'Disponible' : 'Ocupado' ?>
            </div>
          </div>

          <div class="trabajador-card__body">
            <h3 class="trabajador-card__name"><?= htmlspecialchars($t['nombre'] . ' ' . $t['apellidos']) ?></h3>
            <p class="trabajador-card__especialidad"><?= $emoji_e ?> <?= $label_e ?></p>
            <div class="trabajador-card__info">
              <span class="trabajador-card__info-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 3.07 3.33A2 2 0 0 1 5.64 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <?= htmlspecialchars($t['telefono']) ?>
              </span>
            </div>
          </div>

          <div class="trabajador-card__footer">
            <a href="tel:<?= $tel_raw ?>" class="btn-contactar btn-contactar--tel btn-contactar--full">📞 Llamar</a>
            <?php if ($libre && !$sin_rep): ?>
              <button class="btn-contactar btn-contactar--reporte"
                      onclick="abrirModalAsignar(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nombre'] . ' ' . $t['apellidos'])) ?>', '<?= $label_e ?>')">
                Asignar a reporte
              </button>
            <?php elseif (!$libre): ?>
              <button class="btn-contactar btn-contactar--reporte" disabled>No disponible</button>
            <?php else: ?>
              <button class="btn-contactar btn-contactar--reporte" disabled>Sin reportes</button>
            <?php endif; ?>
          </div>

        </div>
        <?php endforeach; ?>

        <?php if (empty($trabajadores)): ?>
          <p style="color:var(--text-muted);padding:40px 0;grid-column:1/-1;text-align:center">
            No hay trabajadores registrados en el sistema.
          </p>
        <?php endif; ?>

      </div>

      <div class="no-resultados" id="noResultados" style="display:none">
        <p class="no-resultados__emoji">🔍</p>
        <p class="no-resultados__text">No se encontraron trabajadores</p>
        <p class="no-resultados__hint">Intenta con otro nombre o especialidad</p>
      </div>

    </div>
  </main>


  <!-- Modal para asignar trabajador a un reporte sin asignar -->
  <div class="modal-overlay" id="modalOverlay" style="display:none" onclick="cerrarModalSiOverlay(event)">
    <div class="modal">
      <form method="POST" action="App/controllers/ReporteController.php" onsubmit="return validarModal()">
        <input type="hidden" name="accion"        value="asignar">
        <input type="hidden" name="notas"          value="">
        <input type="hidden" name="trabajador_id"  id="modalTrabajadorId" value="">
        <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars(generarCSRF()) ?>">

        <button type="button" class="modal__close" onclick="cerrarModal()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <h2 class="modal__title">Asignar trabajador</h2>
        <p class="modal__desc">
          Selecciona el reporte al que deseas asignar a
          <strong id="modalNombre"></strong> (<span id="modalEsp"></span>).
        </p>

        <div class="modal-reportes">
          <?php foreach ($reportes_sin_asignar as $r):
            $emoji   = $cat_emoji[$r['categoria']]  ?? '📋';
            $tcls    = 'modal-reporte-opt__tag--' . ($cat_class[$r['categoria']] ?? 'otros');
            $id_pad  = str_pad($r['id'], 4, '0', STR_PAD_LEFT);
          ?>
          <label class="modal-reporte-opt">
            <input type="radio" name="reporte_id" value="<?= $r['id'] ?>">
            <div class="modal-reporte-opt__card">
              <span class="modal-reporte-opt__tag <?= $tcls ?>"><?= $emoji ?> <?= ucfirst($r['categoria']) ?></span>
              <p><?= htmlspecialchars(mb_strimwidth($r['descripcion'], 0, 65, '…')) ?></p>
              <small>Reporte #<?= $id_pad ?></small>
            </div>
          </label>
          <?php endforeach; ?>
        </div>

        <span class="form__error" id="error-modal"></span>

        <div class="modal__actions">
          <button type="button" class="btn btn--ghost" onclick="cerrarModal()">Cancelar</button>
          <button type="submit" class="btn btn--primary">Confirmar asignación</button>
        </div>

      </form>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('sidebar--open');
      document.getElementById('sidebarOverlay').classList.toggle('overlay--visible');
    }

    /* --- Filtros del directorio --- */
    function filtrarTrabajadores() {
      const texto        = document.getElementById('searchInput').value.toLowerCase();
      const filtroActivo = document.querySelector('.filtro-btn--active').dataset.filtro;
      aplicarFiltros(texto, filtroActivo);
    }

    function setFiltro(btn) {
      document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('filtro-btn--active'));
      btn.classList.add('filtro-btn--active');
      aplicarFiltros(document.getElementById('searchInput').value.toLowerCase(), btn.dataset.filtro);
    }

    function aplicarFiltros(texto, filtro) {
      const cards  = document.querySelectorAll('.trabajador-card');
      let visibles = 0;

      cards.forEach(card => {
        const nombre      = card.dataset.nombre;
        const esp         = card.dataset.especialidad;
        const matchTexto  = !texto || nombre.includes(texto) || esp.includes(texto);
        const matchFiltro = filtro === 'todos' || esp === filtro;

        if (matchTexto && matchFiltro) {
          card.style.display = '';
          visibles++;
        } else {
          card.style.display = 'none';
        }
      });

      document.querySelector('#directorioCount strong').textContent = visibles;
      document.getElementById('noResultados').style.display = visibles === 0 ? 'block' : 'none';
    }

    /* --- Modal de asignación --- */
    function abrirModalAsignar(trabajadorId, nombre, especialidad) {
      document.getElementById('modalNombre').textContent       = nombre;
      document.getElementById('modalEsp').textContent          = especialidad;
      document.getElementById('modalTrabajadorId').value       = trabajadorId;
      document.getElementById('error-modal').textContent       = '';
      document.querySelectorAll('input[name="reporte_id"]').forEach(r => r.checked = false);
      document.querySelectorAll('.modal-reporte-opt__card').forEach(c => c.classList.remove('modal-reporte-opt--selected'));
      document.getElementById('modalOverlay').style.display = 'flex';
    }

    function validarModal() {
      const sel = document.querySelector('input[name="reporte_id"]:checked');
      if (!sel) {
        document.getElementById('error-modal').textContent = 'Selecciona un reporte para asignar.';
        return false;
      }
      return true;
    }

    function cerrarModal() {
      document.getElementById('modalOverlay').style.display = 'none';
    }

    function cerrarModalSiOverlay(e) {
      if (e.target === document.getElementById('modalOverlay')) cerrarModal();
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

    /* Selección visual del reporte en el modal */
    document.querySelectorAll('input[name="reporte_id"]').forEach(radio => {
      radio.addEventListener('change', function () {
        document.querySelectorAll('.modal-reporte-opt__card').forEach(c => c.classList.remove('modal-reporte-opt--selected'));
        this.closest('.modal-reporte-opt').querySelector('.modal-reporte-opt__card').classList.add('modal-reporte-opt--selected');
      });
    });

    /* Animación de entrada de las tarjetas */
    document.querySelectorAll('.trabajador-card').forEach((card, i) => {
      card.style.animationDelay = `${i * 0.06}s`;
      card.classList.add('animate-in');
    });

    <?php if ($ok === 'asignado'): ?>
      mostrarToast('Trabajador asignado correctamente ✓', 'success');
    <?php elseif ($error): ?>
      mostrarToast('Ocurrió un error. Intenta de nuevo.', 'error');
    <?php endif; ?>
  </script>

</body>
</html>
