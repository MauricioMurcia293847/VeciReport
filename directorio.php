<?php
// directorio.php
// Directorio de trabajadores del fraccionamiento. Solo lectura para el vecino (ver y llamar).
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereVecino();

$pdo        = conectar();
$usuario_id = usuarioActual();

$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Domicilio para el sidebar
$stmt = $pdo->prepare("SELECT num_casa FROM vecinos WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$num_casa = $stmt->fetchColumn() ?: '—';

// Badge del sidebar: reportes en proceso del vecino
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM reportes r
    JOIN vecinos v ON r.vecino_id = v.id
    WHERE v.usuario_id = ? AND r.estado = 'en_proceso'
");
$stmt->execute([$usuario_id]);
$en_proceso = (int) $stmt->fetchColumn();

// Todos los trabajadores: disponibles primero, luego ocupados
$stmt = $pdo->query("
    SELECT id, nombre, apellidos, especialidad, telefono, disponibilidad
    FROM trabajadores
    ORDER BY disponibilidad ASC, especialidad, nombre
");
$trabajadores = $stmt->fetchAll();
$total        = count($trabajadores);

$esp_emoji = [
    'electricista' => '⚡',
    'plomero'      => '🔧',
    'albanil'      => '🏗',
    'jardinero'    => '🌿',
    'general'      => '🛠',
];
$esp_label = [
    'electricista' => 'Electricista',
    'plomero'      => 'Plomero',
    'albanil'      => 'Albañil',
    'jardinero'    => 'Jardinero',
    'general'      => 'Mantenimiento general',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Directorio de Trabajadores</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
  <link rel="stylesheet" href="Carpeta CSS/directorio.css">
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

      <a href="directorio.php" class="sidebar__link sidebar__link--active">
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
        <h1>Directorio de trabajadores</h1>
        <p>Consulta y contacta a los especialistas disponibles</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <div class="directorio-toolbar">

        <div class="search-wrap">
          <span class="search-icon">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <input
            type="text"
            id="searchInput"
            class="search-input"
            placeholder="Buscar por nombre o especialidad..."
            oninput="filtrarTrabajadores()"
          >
        </div>

        <div class="filtros">
          <button class="filtro-btn filtro-btn--active" data-filtro="todos"        onclick="setFiltro(this)">Todos</button>
          <button class="filtro-btn"                    data-filtro="electricista" onclick="setFiltro(this)">⚡ Electricista</button>
          <button class="filtro-btn"                    data-filtro="plomero"      onclick="setFiltro(this)">🔧 Plomero</button>
          <button class="filtro-btn"                    data-filtro="albanil"      onclick="setFiltro(this)">🏗 Albañil</button>
          <button class="filtro-btn"                    data-filtro="jardinero"    onclick="setFiltro(this)">🌿 Jardinero</button>
          <button class="filtro-btn"                    data-filtro="general"      onclick="setFiltro(this)">🛠 General</button>
        </div>

      </div>

      <p class="directorio-count" id="directorioCount">
        Mostrando <strong><?= $total ?></strong> trabajadores
      </p>

      <div class="trabajadores-grid" id="trabajadoresGrid">

        <?php foreach ($trabajadores as $i => $t):
          $disponible  = $t['disponibilidad'] === 'disponible';
          $ini         = strtoupper(substr($t['nombre'], 0, 1) . substr($t['apellidos'], 0, 1));
          $color_num   = ($i % 8) + 1;
          $emoji       = $esp_emoji[$t['especialidad']]  ?? '🛠';
          $label       = $esp_label[$t['especialidad']]  ?? ucfirst($t['especialidad']);
          $tel_limpio  = preg_replace('/[^0-9]/', '', $t['telefono']);
          $data_nombre = strtolower($t['nombre'] . ' ' . $t['apellidos']);
        ?>
        <div class="trabajador-card"
             data-especialidad="<?= $t['especialidad'] ?>"
             data-nombre="<?= htmlspecialchars($data_nombre) ?>">

          <div class="trabajador-card__header">
            <div class="trabajador-card__avatar trabajador-card__avatar--<?= $color_num ?>">
              <?= htmlspecialchars($ini) ?>
            </div>
            <div class="trabajador-card__status trabajador-card__status--<?= $disponible ? 'disponible' : 'ocupado' ?>">
              <span class="status-dot"></span>
              <?= $disponible ? 'Disponible' : 'Ocupado' ?>
            </div>
          </div>

          <div class="trabajador-card__body">
            <h3 class="trabajador-card__name"><?= htmlspecialchars($t['nombre'] . ' ' . $t['apellidos']) ?></h3>
            <p class="trabajador-card__especialidad"><?= $emoji ?> <?= htmlspecialchars($label) ?></p>
            <div class="trabajador-card__info">
              <span class="trabajador-card__info-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 3.07 3.33A2 2 0 0 1 5.64 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <?= htmlspecialchars($t['telefono']) ?>
              </span>
            </div>
          </div>

          <div class="trabajador-card__footer trabajador-card__footer--single">
            <a href="tel:<?= htmlspecialchars($tel_limpio) ?>"
               class="btn-contactar btn-contactar--tel btn-contactar--full<?= $disponible ? '' : ' btn-contactar--ocupado' ?>">
              📞 Llamar a <?= htmlspecialchars($t['nombre']) ?>
            </a>
          </div>

        </div>
        <?php endforeach; ?>

        <?php if (empty($trabajadores)): ?>
          <p style="color:var(--text-muted);padding:24px 0;grid-column:1/-1">No hay trabajadores registrados aún.</p>
        <?php endif; ?>

      </div>

      <div class="no-resultados" id="noResultados" style="display:none">
        <p class="no-resultados__emoji">🔍</p>
        <p class="no-resultados__text">No se encontraron trabajadores</p>
        <p class="no-resultados__hint">Intenta con otro nombre o especialidad</p>
      </div>

    </div>
  </main>

  <script src="Carpeta JS/funciones.js"></script>
  <script>
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
        const nombre       = card.dataset.nombre;
        const especialidad = card.dataset.especialidad;
        const coincideTexto  = !texto || nombre.includes(texto) || especialidad.includes(texto);
        const coincideFiltro = filtro === 'todos' || especialidad === filtro;

        if (coincideTexto && coincideFiltro) {
          card.style.display = '';
          visibles++;
        } else {
          card.style.display = 'none';
        }
      });

      document.querySelector('#directorioCount strong').textContent = visibles;
      document.getElementById('noResultados').style.display         = visibles === 0 ? 'block' : 'none';
      document.getElementById('trabajadoresGrid').style.display     = visibles === 0 ? 'none'  : '';
    }

    document.querySelectorAll('.trabajador-card').forEach((card, i) => {
      card.style.animationDelay = `${i * 0.07}s`;
      card.classList.add('animate-in');
    });
  </script>

</body>
</html>
