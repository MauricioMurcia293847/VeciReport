<?php
// admin-fraccionamientos.php
// Catalogo de fraccionamientos permitidos para registro. Solo admin.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereAdmin();

$pdo       = conectar();
$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

$sidebar_badge = (int) $pdo->query("SELECT COUNT(*) FROM reportes WHERE estado IN ('pendiente','en_proceso')")->fetchColumn();

$fraccionamientos = $pdo->query("
    SELECT f.id, f.nombre, f.direccion, f.mapa_poligono, f.activo, f.created_at,
           COUNT(v.id) AS total_vecinos
    FROM fraccionamientos f
    LEFT JOIN vecinos v ON v.fraccionamiento_id = f.id
    GROUP BY f.id, f.nombre, f.direccion, f.mapa_poligono, f.activo, f.created_at
    ORDER BY f.activo DESC, f.nombre
")->fetchAll();

$ok    = $_GET['ok'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Fraccionamientos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
  <link rel="stylesheet" href="Carpeta CSS/admin.css">
  <link rel="stylesheet" href="Carpeta CSS/admin-reportes.css">
  <style>
    .fracc-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
      align-items: start;
    }
    .fracc-builder {
      border: 1px solid var(--border);
      border-radius: var(--radius);
      background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.96));
      box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
      overflow: hidden;
    }
    .fracc-builder__head {
      display: flex;
      justify-content: space-between;
      gap: 18px;
      padding: 22px 24px 18px;
      border-bottom: 1px solid var(--border);
    }
    .fracc-builder__title {
      font-family: var(--font-display);
      font-size: 1.1rem;
      color: var(--text);
      margin-bottom: 4px;
    }
    .fracc-builder__text {
      color: var(--text-muted);
      font-size: 0.86rem;
      line-height: 1.5;
    }
    .fracc-builder__badge {
      align-self: flex-start;
      padding: 6px 12px;
      border-radius: 999px;
      background: rgba(232,93,38,0.1);
      color: var(--accent);
      border: 1px solid rgba(232,93,38,0.22);
      font-size: 0.72rem;
      font-weight: 800;
      white-space: nowrap;
    }
    .fracc-builder__body {
      padding: 22px 24px 24px;
    }
    .fracc-field--full {
      grid-column: 1 / -1;
    }
    .fracc-map-tools {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
    .fracc-search-card {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
      padding: 14px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: rgba(255,255,255,0.72);
    }
    .fracc-map-status {
      min-height: 22px;
      margin-top: 10px;
      color: var(--text-muted);
      font-size: 0.82rem;
    }
    .fracc-map {
      height: 360px;
      border-radius: var(--radius-sm);
      overflow: hidden;
      border: 1px solid var(--border);
      margin-top: 10px;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6);
    }
    .fracc-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 16px;
    }
    .fracc-actions__left {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .fracc-json {
      min-height: 72px;
      font-family: Consolas, monospace;
      font-size: 0.78rem;
      line-height: 1.45;
      resize: vertical;
    }
    .fracc-card-actions {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }
    .admin-btn--danger {
      background: rgba(220,38,38,0.08);
      color: #DC2626;
      border: 1px solid rgba(220,38,38,0.22);
    }
    .admin-btn--danger:hover:not(:disabled) {
      background: rgba(220,38,38,0.14);
    }
    .admin-btn:disabled {
      opacity: 0.48;
      cursor: not-allowed;
    }
    @media (max-width: 820px) {
      .fracc-form-grid { grid-template-columns: 1fr; }
      .fracc-builder__head { flex-direction: column; }
      .fracc-search-card,
      .fracc-map-tools { grid-template-columns: 1fr; }
      .fracc-actions { align-items: stretch; flex-direction: column; }
      .fracc-actions .btn { width: 100%; justify-content: center; }
    }
  </style>
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
        <span>Resumen</span>
      </a>
      <a href="admin-reportes.php" class="sidebar__link">
        <span>Todos los reportes</span>
        <?php if ($sidebar_badge > 0): ?>
          <span class="sidebar__badge"><?= $sidebar_badge ?></span>
        <?php endif; ?>
      </a>
      <a href="admin-vecinos.php" class="sidebar__link">
        <span>Vecinos</span>
      </a>
      <a href="admin-fraccionamientos.php" class="sidebar__link sidebar__link--active">
        <span>Fraccionamientos</span>
      </a>
      <a href="directorio-admin.php" class="sidebar__link">
        <span>Trabajadores</span>
      </a>
      <a href="admin-bitacora.php" class="sidebar__link">
        <span>Bitacora</span>
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
      <button class="topbar__toggle" onclick="toggleSidebar()" aria-label="Abrir menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topbar__title">
        <h1>Fraccionamientos</h1>
        <p>Comunidades permitidas para registro de vecinos</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar topbar__avatar--admin"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">
      <section class="fracc-builder" style="margin-bottom:22px">
        <div class="fracc-builder__head">
          <div>
            <h2 class="fracc-builder__title">Nuevo fraccionamiento</h2>
            <p class="fracc-builder__text">Busca una zona, dibuja o ajusta el limite y guarda solo comunidades permitidas para registro.</p>
          </div>
          <span class="fracc-builder__badge">Mapa real</span>
        </div>

        <form method="POST" action="App/controllers/FraccionamientoController.php" class="fracc-builder__body">
          <input type="hidden" name="accion" value="crear">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">

          <div class="fracc-form-grid">
            <div class="form__group">
              <label class="form__label">Nombre</label>
              <input type="text" name="nombre" class="form__input" maxlength="120" placeholder="Ej: Horizontes del Sur" required>
            </div>
            <div class="form__group">
              <label class="form__label">Direccion o referencia</label>
              <input type="text" name="direccion" class="form__input" maxlength="180" placeholder="Ciudad Juarez, Chihuahua">
            </div>

            <div class="form__group fracc-field--full">
              <label class="form__label">Busqueda dinamica</label>
              <div class="fracc-search-card">
                <div class="form__group" style="margin:0">
                  <label class="form__label" for="buscarCiudad">Ciudad</label>
                  <input type="search" id="buscarCiudad" class="form__input" placeholder="Ej: Cd. Juarez, Chihuahua">
                </div>
                <div class="form__group" style="margin:0">
                  <label class="form__label" for="buscarFraccionamiento">Fraccionamiento</label>
                  <input type="search" id="buscarFraccionamiento" class="form__input" placeholder="Ej: Horizontes del Sur">
                </div>
              </div>
              <p id="estadoBusquedaMapa" class="fracc-map-status"></p>
              <div id="adminFraccMap" class="fracc-map"></div>
              <div class="fracc-actions">
                <div class="fracc-actions__left">
                  <button type="button" id="limpiarPoligono" class="admin-btn">Limpiar poligono</button>
                  <button type="button" id="limpiarBusquedaMapa" class="admin-btn">Limpiar busqueda</button>
                </div>
                <button type="submit" class="btn btn--primary">Guardar fraccionamiento</button>
              </div>
            </div>

            <div class="form__group fracc-field--full">
              <label class="form__label">Coordenadas del poligono</label>
              <textarea id="mapa_poligono" name="mapa_poligono" class="form__input fracc-json" rows="3" placeholder="[[31.741150,-106.489700],[31.742050,-106.481900],[31.738700,-106.478900]]"></textarea>
              <p class="form__hint">Formato: [[lat,lng],[lat,lng],...]. El mapa puede llenarlo automaticamente o puedes editarlo manualmente.</p>
            </div>
          </div>
        </form>
      </section>

      <div class="vecinos-grid">
        <?php foreach ($fraccionamientos as $f): ?>
          <div class="vecino-card">
            <div class="vecino-card__top">
              <div class="vecino-card__avatar"><?= strtoupper(substr($f['nombre'], 0, 2)) ?></div>
              <span class="vecino-card__badge <?= $f['activo'] ? 'vecino-card__badge--activo' : 'vecino-card__badge--bloqueado' ?>">
                <?= $f['activo'] ? 'Activo' : 'Inactivo' ?>
              </span>
            </div>
            <div class="vecino-card__info">
              <h3 class="vecino-card__name"><?= htmlspecialchars($f['nombre']) ?></h3>
              <p class="vecino-card__meta"><?= htmlspecialchars($f['direccion'] ?: 'Sin direccion registrada') ?></p>
              <p class="vecino-card__meta">Mapa: <?= $f['mapa_poligono'] ? 'Configurado' : 'Pendiente' ?></p>
              <p class="vecino-card__meta"><?= (int) $f['total_vecinos'] ?> vecino<?= (int) $f['total_vecinos'] !== 1 ? 's' : '' ?> registrado<?= (int) $f['total_vecinos'] !== 1 ? 's' : '' ?></p>
            </div>
            <div class="vecino-card__footer">
              <span class="vecino-card__stat">Registro permitido: <?= $f['activo'] ? 'Si' : 'No' ?></span>
              <div class="fracc-card-actions">
                <form method="POST" action="App/controllers/FraccionamientoController.php" style="margin:0">
                  <input type="hidden" name="accion" value="estado">
                  <input type="hidden" name="fraccionamiento_id" value="<?= (int) $f['id'] ?>">
                  <input type="hidden" name="activo" value="<?= $f['activo'] ? 0 : 1 ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">
                  <button type="submit" class="admin-btn <?= $f['activo'] ? '' : 'admin-btn--resolve' ?>">
                    <?= $f['activo'] ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>
                <form method="POST" action="App/controllers/FraccionamientoController.php" style="margin:0" onsubmit="return confirm('Eliminar este fraccionamiento? Esta accion no se puede deshacer.');">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="fraccionamiento_id" value="<?= (int) $f['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">
                  <button type="submit" class="admin-btn admin-btn--danger" <?= (int) $f['total_vecinos'] > 0 ? 'disabled title="No se puede eliminar con vecinos registrados"' : '' ?>>
                    Eliminar
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>

  <div class="toast" id="toast"></div>
  <script src="Carpeta JS/funciones.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const textareaPoligono = document.getElementById('mapa_poligono');
    const buscarCiudad = document.getElementById('buscarCiudad');
    const buscarFraccionamiento = document.getElementById('buscarFraccionamiento');
    const estadoBusquedaMapa = document.getElementById('estadoBusquedaMapa');
    const mapaAdmin = L.map('adminFraccMap', { scrollWheelZoom: false }).setView([31.7387, -106.4849], 15);
    const capaBusqueda = L.layerGroup().addTo(mapaAdmin);
    let puntosAdmin = [];
    let poligonoAdmin = null;
    let ciudadActual = '';
    let busquedaTimer = null;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapaAdmin);

    function renderPoligonoAdmin() {
      if (poligonoAdmin) {
        mapaAdmin.removeLayer(poligonoAdmin);
        poligonoAdmin = null;
      }

      if (puntosAdmin.length >= 3) {
        poligonoAdmin = L.polygon(puntosAdmin, {
          color: '#E85D26',
          weight: 2,
          fillColor: '#E85D26',
          fillOpacity: 0.18,
        }).addTo(mapaAdmin);
        mapaAdmin.fitBounds(poligonoAdmin.getBounds(), { padding: [18, 18] });
      }
    }

    function sincronizarDesdeTextarea() {
      try {
        const puntos = textareaPoligono.value.trim() ? JSON.parse(textareaPoligono.value) : [];
        puntosAdmin = Array.isArray(puntos) ? puntos : [];
      } catch (error) {
        puntosAdmin = [];
      }
      renderPoligonoAdmin();
    }

    function setEstadoBusqueda(texto) {
      estadoBusquedaMapa.textContent = texto || '';
    }

    function extraerPoligonoDesdeGeojson(geojson) {
      if (!geojson || !geojson.type || !geojson.coordinates) return null;

      if (geojson.type === 'Polygon') {
        return geojson.coordinates[0].map(([lng, lat]) => [
          Number(lat.toFixed(7)),
          Number(lng.toFixed(7)),
        ]);
      }

      if (geojson.type === 'MultiPolygon') {
        return geojson.coordinates[0][0].map(([lng, lat]) => [
          Number(lat.toFixed(7)),
          Number(lng.toFixed(7)),
        ]);
      }

      return null;
    }

    function limpiarBusquedaMapa() {
      capaBusqueda.clearLayers();
      setEstadoBusqueda('');
    }

    async function buscarLugar(query, tipo) {
      const texto = query.trim();
      if (texto.length < 3) {
        if (tipo === 'ciudad') ciudadActual = '';
        limpiarBusquedaMapa();
        return;
      }

      const consulta = tipo === 'fraccionamiento' && ciudadActual
        ? `${texto}, ${ciudadActual}`
        : texto;

      setEstadoBusqueda('Buscando en el mapa...');

      try {
        const url = 'https://nominatim.openstreetmap.org/search'
          + `?format=jsonv2&polygon_geojson=1&limit=1&q=${encodeURIComponent(consulta)}`;
        const respuesta = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const resultados = await respuesta.json();

        capaBusqueda.clearLayers();

        if (!Array.isArray(resultados) || resultados.length === 0) {
          setEstadoBusqueda('No se encontraron resultados. Prueba con ciudad, estado y pais.');
          return;
        }

        const lugar = resultados[0];
        const lat = Number(lugar.lat);
        const lng = Number(lugar.lon);
        const nombreLugar = lugar.display_name || consulta;
        const poligono = extraerPoligonoDesdeGeojson(lugar.geojson);

        if (tipo === 'ciudad') {
          ciudadActual = nombreLugar;
          mapaAdmin.setView([lat, lng], 13);
          L.marker([lat, lng]).addTo(capaBusqueda).bindPopup(nombreLugar).openPopup();
          setEstadoBusqueda('Ciudad centrada. Ahora puedes buscar o dibujar el fraccionamiento.');
          return;
        }

        if (poligono && poligono.length >= 3) {
          capaBusqueda.clearLayers();
          puntosAdmin = poligono;
          textareaPoligono.value = JSON.stringify(puntosAdmin);
          renderPoligonoAdmin();
          setEstadoBusqueda('Fraccionamiento encontrado con poligono. Puedes guardar o ajustar puntos.');
          return;
        }

        L.marker([lat, lng]).addTo(capaBusqueda).bindPopup(nombreLugar).openPopup();
        mapaAdmin.setView([lat, lng], 16);
        setEstadoBusqueda('Resultado marcado. Si no hay limite oficial, dibuja el poligono con clics.');
      } catch (error) {
        setEstadoBusqueda('No se pudo consultar el mapa. Revisa internet o intenta de nuevo.');
      }
    }

    function programarBusqueda(tipo) {
      clearTimeout(busquedaTimer);
      busquedaTimer = setTimeout(() => {
        const query = tipo === 'ciudad' ? buscarCiudad.value : buscarFraccionamiento.value;
        buscarLugar(query, tipo);
      }, 700);
    }

    mapaAdmin.on('click', (event) => {
      puntosAdmin.push([
        Number(event.latlng.lat.toFixed(7)),
        Number(event.latlng.lng.toFixed(7)),
      ]);
      textareaPoligono.value = JSON.stringify(puntosAdmin);
      renderPoligonoAdmin();
    });

    textareaPoligono.addEventListener('input', sincronizarDesdeTextarea);
    buscarCiudad.addEventListener('input', () => programarBusqueda('ciudad'));
    buscarFraccionamiento.addEventListener('input', () => programarBusqueda('fraccionamiento'));
    document.getElementById('limpiarPoligono').addEventListener('click', () => {
      puntosAdmin = [];
      textareaPoligono.value = '';
      renderPoligonoAdmin();
    });
    document.getElementById('limpiarBusquedaMapa').addEventListener('click', limpiarBusquedaMapa);
    sincronizarDesdeTextarea();
    setTimeout(() => mapaAdmin.invalidateSize(), 120);

    <?php if ($ok === 'creado'): ?>
      mostrarToast('Fraccionamiento creado correctamente.', 'success');
    <?php elseif ($ok === 'estado'): ?>
      mostrarToast('Estado actualizado.', 'success');
    <?php elseif ($ok === 'eliminado'): ?>
      mostrarToast('Fraccionamiento eliminado.', 'success');
    <?php elseif ($error === 'duplicado'): ?>
      mostrarToast('Ya existe un fraccionamiento con ese nombre.', 'error');
    <?php elseif ($error === 'poligono'): ?>
      mostrarToast('El poligono debe ser JSON valido con coordenadas lat/lng.', 'error');
    <?php elseif ($error === 'con_vecinos'): ?>
      mostrarToast('No se puede eliminar un fraccionamiento con vecinos registrados.', 'error');
    <?php elseif ($error): ?>
      mostrarToast('Revisa los datos e intenta de nuevo.', 'error');
    <?php endif; ?>
  </script>
</body>
</html>
