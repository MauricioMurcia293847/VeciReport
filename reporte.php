<?php
// reporte.php
// Formulario para crear un nuevo reporte. Requiere sesión de vecino.
require_once 'App/config/database.php';
require_once 'App/helpers/auth.php';

requiereVecino();

$nombre    = $_SESSION['nombre'];
$apellidos = $_SESSION['apellidos'];
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidos, 0, 1));

// Obtiene el número de casa y color del vecino para pre-llenar el formulario
$pdo  = conectar();
$stmt = $pdo->prepare("SELECT num_casa, color_casa FROM vecinos WHERE usuario_id = ?");
$stmt->execute([usuarioActual()]);
$vecino = $stmt->fetch();
$num_casa   = $vecino['num_casa']   ?? '';
$color_casa = $vecino['color_casa'] ?? '';

// Cuenta reportes en proceso para el badge del sidebar
$stmt2 = $pdo->prepare("
    SELECT COUNT(*) AS total FROM reportes r
    JOIN vecinos v ON r.vecino_id = v.id
    WHERE v.usuario_id = ? AND r.estado = 'en_proceso'
");
$stmt2->execute([usuarioActual()]);
$en_proceso = $stmt2->fetchColumn();

// Mensajes de error o éxito desde el controlador
$error  = $_GET['error'] ?? '';
$ok     = $_GET['ok']    ?? '';
$rep_id = $_GET['id']    ?? '';

$errores_msg = [
    'datos_invalidos'       => 'Los datos enviados no son válidos.',
    'descripcion_corta'     => 'La descripción debe tener al menos 10 caracteres.',
    'campos_vacios'         => 'Todos los campos son obligatorios.',
    'vecino_no_encontrado'  => 'No se encontró tu perfil de vecino.',
    'foto_invalida'         => 'La foto debe ser JPG o PNG y pesar máximo 5 MB.',
    'error_servidor'        => 'Ocurrió un error. Inténtalo de nuevo.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Nuevo Reporte</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Carpeta CSS/style.css">
  <link rel="stylesheet" href="Carpeta CSS/dashboard.css">
  <link rel="stylesheet" href="Carpeta CSS/reporte.css">
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

      <a href="reporte.php" class="sidebar__link sidebar__link--active">
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
        <h1>Nuevo reporte</h1>
        <p>Describe el problema y lo asignamos al instante</p>
      </div>
      <div class="topbar__actions">
        <div class="topbar__avatar"><?= htmlspecialchars($iniciales) ?></div>
      </div>
    </header>

    <div class="dashboard-content">

      <!-- Mensaje de error del servidor si lo hay -->
      <?php if ($error && isset($errores_msg[$error])): ?>
        <div class="alert alert--error" style="margin-bottom:16px;padding:12px 16px;background:rgba(255,90,90,0.1);border:1px solid rgba(255,90,90,0.3);border-radius:8px;color:#FF5A5A;font-size:0.9rem;">
          ⚠ <?= $errores_msg[$error] ?>
        </div>
      <?php endif; ?>

      <div class="reporte-layout">

        <div class="reporte-form-wrap">

          <!-- El formulario ahora apunta al controlador PHP real -->
          <form
            class="reporte-form"
            action="App/controllers/ReporteController.php"
            method="POST"
            enctype="multipart/form-data"
            novalidate
            id="reporteForm"
          >
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCSRF()) ?>">

            <!-- PASO 1: Categoría -->
            <section class="reporte-section" id="seccion-categoria">
              <div class="reporte-section__header">
                <div class="reporte-section__num">1</div>
                <div>
                  <h2 class="reporte-section__title">¿Cuál es el tipo de problema?</h2>
                  <p class="reporte-section__desc">Selecciona la categoría que mejor describe la incidencia.</p>
                </div>
              </div>

              <div class="cat-selector" id="catSelector">
                <label class="cat-option cat-option--luz">
                  <input type="radio" name="categoria" value="luz" required>
                  <div class="cat-option__card">
                    <span class="cat-option__emoji">💡</span>
                    <p class="cat-option__name">Luz</p>
                    <p class="cat-option__hint">Alumbrado, cables, cortocircuitos</p>
                    <div class="cat-option__check">✓</div>
                  </div>
                </label>

                <label class="cat-option cat-option--agua">
                  <input type="radio" name="categoria" value="agua" required>
                  <div class="cat-option__card">
                    <span class="cat-option__emoji">💧</span>
                    <p class="cat-option__name">Agua</p>
                    <p class="cat-option__hint">Fugas, tuberías, suministro</p>
                    <div class="cat-option__check">✓</div>
                  </div>
                </label>

                <label class="cat-option cat-option--trabajadores">
                  <input type="radio" name="categoria" value="trabajadores" required>
                  <div class="cat-option__card">
                    <span class="cat-option__emoji">🔧</span>
                    <p class="cat-option__name">Trabajadores</p>
                    <p class="cat-option__hint">Solicitar servicio técnico</p>
                    <div class="cat-option__check">✓</div>
                  </div>
                </label>

                <label class="cat-option cat-option--otros">
                  <input type="radio" name="categoria" value="otros" required>
                  <div class="cat-option__card">
                    <span class="cat-option__emoji">📋</span>
                    <p class="cat-option__name">Otros</p>
                    <p class="cat-option__hint">Mantenimiento, áreas comunes</p>
                    <div class="cat-option__check">✓</div>
                  </div>
                </label>
              </div>
              <span class="form__error" id="error-categoria"></span>
            </section>

            <!-- PASO 2: Tipo -->
            <section class="reporte-section">
              <div class="reporte-section__header">
                <div class="reporte-section__num">2</div>
                <div>
                  <h2 class="reporte-section__title">¿A quién afecta el problema?</h2>
                  <p class="reporte-section__desc">Esto determina si se asigna un trabajador directamente o se notifica al administrador.</p>
                </div>
              </div>

              <div class="tipo-selector">
                <label class="tipo-option">
                  <input type="radio" name="tipo_problema" value="individual" required>
                  <div class="tipo-option__card">
                    <div class="tipo-option__icon">
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                      </svg>
                    </div>
                    <div>
                      <p class="tipo-option__name">Individual</p>
                      <p class="tipo-option__hint">Solo afecta a mi casa o propiedad</p>
                    </div>
                    <div class="tipo-option__check">✓</div>
                  </div>
                </label>

                <label class="tipo-option">
                  <input type="radio" name="tipo_problema" value="colectivo" required>
                  <div class="tipo-option__card">
                    <div class="tipo-option__icon">
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                      </svg>
                    </div>
                    <div>
                      <p class="tipo-option__name">Colectivo</p>
                      <p class="tipo-option__hint">Afecta a varias casas o toda la calle</p>
                    </div>
                    <div class="tipo-option__check">✓</div>
                  </div>
                </label>
              </div>
              <span class="form__error" id="error-tipo_problema"></span>
            </section>

            <!-- PASO 3: Descripción — color y número de casa pre-llenados -->
            <section class="reporte-section">
              <div class="reporte-section__header">
                <div class="reporte-section__num">3</div>
                <div>
                  <h2 class="reporte-section__title">Describe el problema</h2>
                  <p class="reporte-section__desc">Entre más detalle des, más rápido podremos atenderte.</p>
                </div>
              </div>

              <div class="form__group">
                <label class="form__label" for="descripcion">Descripción</label>
                <textarea
                  id="descripcion"
                  name="descripcion"
                  class="form__input form__textarea"
                  placeholder="Ej: El poste de luz frente a mi casa lleva 3 días apagado."
                  rows="4"
                  maxlength="500"
                  required
                ></textarea>
                <div class="textarea-count">
                  <span id="charCount">0</span>/500 caracteres
                </div>
                <span class="form__error" id="error-descripcion"></span>
              </div>

              <div class="form__row">
                <div class="form__group">
                  <label class="form__label" for="color_casa">Color de tu casa</label>
                  <div class="form__input-wrap">
                    <span class="form__icon">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                      </svg>
                    </span>
                    <!-- Pre-llenado con datos reales del vecino -->
                    <input type="text" id="color_casa" name="color_casa"
                           class="form__input"
                           value="<?= htmlspecialchars($color_casa) ?>"
                           placeholder="Ej: Beige" required>
                  </div>
                  <span class="form__error" id="error-color_casa"></span>
                </div>

                <div class="form__group">
                  <label class="form__label" for="num_casa">Número de tu casa</label>
                  <div class="form__input-wrap">
                    <span class="form__icon">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                      </svg>
                    </span>
                    <!-- Pre-llenado con datos reales del vecino -->
                    <input type="text" id="num_casa" name="num_casa"
                           class="form__input"
                           value="<?= htmlspecialchars($num_casa) ?>"
                           placeholder="Ej: #24" required>
                  </div>
                  <span class="form__error" id="error-num_casa"></span>
                </div>
              </div>
            </section>

            <!-- PASO 4: Foto opcional -->
            <section class="reporte-section">
              <div class="reporte-section__header">
                <div class="reporte-section__num">4</div>
                <div>
                  <h2 class="reporte-section__title">Foto del problema <span class="reporte-section__optional">(Opcional)</span></h2>
                  <p class="reporte-section__desc">Una imagen ayuda a los trabajadores a entender mejor la situación.</p>
                </div>
              </div>

              <div class="file-drop" id="fotoDropzone" onclick="document.getElementById('foto').click()">
                <div class="file-drop__icon" id="fotoIcon">
                  <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                  </svg>
                </div>
                <img id="fotoPreview" class="file-drop__preview" style="display:none" alt="Vista previa">
                <p class="file-drop__text">Haz clic o arrastra una foto aquí</p>
                <p class="file-drop__hint">JPG o PNG · Máx. 5MB</p>
                <p class="file-drop__name" id="fotoName" style="display:none"></p>
              </div>
              <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png" style="display:none" onchange="mostrarFoto(this)">
            </section>

            <!-- Botón de envío -->
            <div class="reporte-submit">
              <a href="dashboard.php" class="btn btn--ghost">Cancelar</a>
              <button type="submit" class="btn btn--primary btn--lg" id="btnEnviar">
                <span class="btn__text">Enviar reporte</span>
                <span class="btn__spinner" id="spinner" style="display:none"></span>
              </button>
            </div>

          </form>
        </div>

        <!-- Panel lateral de ayuda -->
        <aside class="reporte-aside">
          <div class="aside-card">
            <h3 class="aside-card__title">¿Qué pasa después?</h3>
            <div class="aside-steps">
              <div class="aside-step">
                <div class="aside-step__dot aside-step__dot--green"></div>
                <div>
                  <p class="aside-step__title">Reporte recibido</p>
                  <p class="aside-step__desc">Tu reporte se registra con un ID único.</p>
                </div>
              </div>
              <div class="aside-step">
                <div class="aside-step__dot aside-step__dot--yellow"></div>
                <div>
                  <p class="aside-step__title">Se asigna un trabajador</p>
                  <p class="aside-step__desc">El admin asignará al especialista disponible.</p>
                </div>
              </div>
              <div class="aside-step">
                <div class="aside-step__dot aside-step__dot--blue"></div>
                <div>
                  <p class="aside-step__title">Notificación al admin</p>
                  <p class="aside-step__desc">Si es colectivo, el guardia coordinará la atención.</p>
                </div>
              </div>
              <div class="aside-step">
                <div class="aside-step__dot aside-step__dot--accent"></div>
                <div>
                  <p class="aside-step__title">Problema resuelto</p>
                  <p class="aside-step__desc">Tu reporte se marcará como atendido.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Vista previa en tiempo real -->
          <div class="aside-card aside-card--preview" id="previewCard">
            <h3 class="aside-card__title">Vista previa del reporte</h3>
            <div class="preview-body">
              <div class="preview-row">
                <span class="preview-label">Categoría</span>
                <span class="preview-value" id="prev-cat">—</span>
              </div>
              <div class="preview-row">
                <span class="preview-label">Tipo</span>
                <span class="preview-value" id="prev-tipo">—</span>
              </div>
              <div class="preview-row">
                <span class="preview-label">Casa</span>
                <span class="preview-value" id="prev-casa">—</span>
              </div>
              <div class="preview-row">
                <span class="preview-label">Descripción</span>
                <span class="preview-value preview-value--desc" id="prev-desc">—</span>
              </div>
            </div>
          </div>
        </aside>

      </div>
    </div>
  </main>

  <!-- Modal de éxito — se muestra si el controlador redirigió con ?ok=1 -->
  <div class="modal-overlay" id="modalOverlay" style="display:none">
    <div class="modal">
      <div class="modal__icon">✓</div>
      <h2 class="modal__title">¡Reporte enviado!</h2>
      <p class="modal__desc">Tu reporte ha sido registrado. El administrador lo revisará y asignará un trabajador.</p>
      <p class="modal__id">ID del reporte: <strong id="modalReporteId"></strong></p>
      <div class="modal__actions">
        <a href="mis-reportes.php" class="btn btn--primary">Ver mis reportes</a>
        <a href="dashboard.php" class="btn btn--ghost">Ir al inicio</a>
      </div>
    </div>
  </div>

  <script src="Carpeta JS/funciones.js"></script>
  <script>

    // Si el controlador redirigió con ?ok=1, muestra el modal de éxito
    const params  = new URLSearchParams(window.location.search);
    const ok      = params.get('ok');
    const repId   = params.get('id');
    const tipoUrl = params.get('tipo');

    if (ok === '1' && repId) {
      document.getElementById('modalReporteId').textContent = '#' + repId.toString().padStart(4, '0');
      document.getElementById('modalOverlay').style.display = 'flex';
    }

    // Pre-selecciona categoría si viene en la URL (ej: reporte.php?tipo=agua)
    if (tipoUrl) {
      const radio = document.querySelector(`input[name="categoria"][value="${tipoUrl}"]`);
      if (radio) {
        radio.checked = true;
        radio.closest('.cat-option').classList.add('cat-option--selected');
        actualizarPreview();
      }
    }

    document.querySelectorAll('input[name="categoria"]').forEach(radio => {
      radio.addEventListener('change', function () {
        document.querySelectorAll('.cat-option').forEach(c => c.classList.remove('cat-option--selected'));
        this.closest('.cat-option').classList.add('cat-option--selected');
        actualizarPreview();
      });
    });

    document.querySelectorAll('input[name="tipo_problema"]').forEach(radio => {
      radio.addEventListener('change', function () {
        document.querySelectorAll('.tipo-option').forEach(c => c.classList.remove('tipo-option--selected'));
        this.closest('.tipo-option').classList.add('tipo-option--selected');
        actualizarPreview();
      });
    });

    document.getElementById('descripcion').addEventListener('input', function () {
      document.getElementById('charCount').textContent = this.value.length;
      actualizarPreview();
    });

    function actualizarPreview() {
      const cat   = document.querySelector('input[name="categoria"]:checked');
      const tipo  = document.querySelector('input[name="tipo_problema"]:checked');
      const desc  = document.getElementById('descripcion').value.trim();
      const casa  = document.getElementById('num_casa').value.trim();
      const color = document.getElementById('color_casa').value.trim();

      const emojis = { luz: '💡 Luz', agua: '💧 Agua', trabajadores: '🔧 Trabajadores', otros: '📋 Otros' };

      document.getElementById('prev-cat').textContent  = cat  ? emojis[cat.value]  : '—';
      document.getElementById('prev-tipo').textContent = tipo ? (tipo.value === 'individual' ? '👤 Individual' : '👥 Colectivo') : '—';
      document.getElementById('prev-casa').textContent = (color || casa) ? `${color} ${casa}`.trim() : '—';
      document.getElementById('prev-desc').textContent = desc ? (desc.length > 80 ? desc.substring(0, 80) + '...' : desc) : '—';
    }

    document.getElementById('num_casa').addEventListener('input', actualizarPreview);
    document.getElementById('color_casa').addEventListener('input', actualizarPreview);

    function mostrarFoto(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const preview = document.getElementById('fotoPreview');
          const icon    = document.getElementById('fotoIcon');
          const name    = document.getElementById('fotoName');
          preview.src           = e.target.result;
          preview.style.display = 'block';
          icon.style.display    = 'none';
          name.textContent      = '📎 ' + input.files[0].name;
          name.style.display    = 'block';
          document.getElementById('fotoDropzone').classList.add('file-drop--active');
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    const fotoZone = document.getElementById('fotoDropzone');
    fotoZone.addEventListener('dragover', e => { e.preventDefault(); fotoZone.classList.add('file-drop--hover'); });
    fotoZone.addEventListener('dragleave', () => fotoZone.classList.remove('file-drop--hover'));
    fotoZone.addEventListener('drop', e => {
      e.preventDefault();
      fotoZone.classList.remove('file-drop--hover');
      const archivo = e.dataTransfer.files[0];
      if (archivo) {
        const dt = new DataTransfer();
        dt.items.add(archivo);
        document.getElementById('foto').files = dt.files;
        mostrarFoto(document.getElementById('foto'));
      }
    });

    document.getElementById('reporteForm').addEventListener('submit', function(e) {
      e.preventDefault();
      let valido = true;

      ['categoria','tipo_problema','descripcion','color_casa','num_casa'].forEach(id => {
        const el = document.getElementById('error-' + id);
        if (el) el.textContent = '';
      });

      const cat       = document.querySelector('input[name="categoria"]:checked');
      const tipo      = document.querySelector('input[name="tipo_problema"]:checked');
      const desc      = document.getElementById('descripcion').value.trim();
      const colorCasa = document.getElementById('color_casa').value.trim();
      const numCasa   = document.getElementById('num_casa').value.trim();

      if (!cat)                { document.getElementById('error-categoria').textContent    = 'Selecciona una categoría.'; valido = false; }
      if (!tipo)               { document.getElementById('error-tipo_problema').textContent = 'Indica si es individual o colectivo.'; valido = false; }
      if (!desc)               { document.getElementById('error-descripcion').textContent   = 'La descripción es obligatoria.'; valido = false; }
      else if (desc.length<10) { document.getElementById('error-descripcion').textContent   = 'Mínimo 10 caracteres.'; valido = false; }
      if (!colorCasa)          { document.getElementById('error-color_casa').textContent    = 'El color de tu casa es obligatorio.'; valido = false; }
      if (!numCasa)            { document.getElementById('error-num_casa').textContent      = 'El número de tu casa es obligatorio.'; valido = false; }

      if (valido) {
        document.getElementById('spinner').style.display = 'inline-block';
        document.querySelector('.btn__text').textContent = 'Enviando...';
        document.getElementById('btnEnviar').disabled   = true;
        // Envía el formulario al controlador PHP
        this.submit();
      }
    });

  </script>

</body>
</html>
