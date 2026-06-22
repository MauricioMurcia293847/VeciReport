<?php require_once 'App/helpers/auth.php'; generarCSRF(); $demo_activo = appDemoMode(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport - Iniciar Sesión</title>

  <!-- Mismas fuentes que el index para consistencia visual -->
  <!-- Syne: títulos | DM Sans: cuerpo de texto -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

  <!-- Hoja de estilos principal compartida con todo el proyecto -->
  <link rel="stylesheet" href="Carpeta CSS/style.css">

  <!-- Estilos exclusivos de esta página (login) -->
  <link rel="stylesheet" href="Carpeta CSS/login.css">
</head>

<body class="login-page">

  <!-- navbar
       En el login solo mostramos el logo/nombre
       No tiene menú hamburguesa porque es una
       página de acceso simple. -->
  <nav class="navbar navbar--simple">

    <!-- Logo: al hacer clic regresa al inicio -->
    <a href="index.php" class="navbar__brand">
      <div class="navbar__logo">
        <!-- Mismo SVG del index para consistencia -->
        <svg width="26" height="26" viewBox="0 0 28 28" fill="none">
          <circle cx="14" cy="14" r="13" stroke="#00D4AA" stroke-width="2"/>
          <path d="M14 7C10.134 7 7 10.134 7 14C7 17.866 10.134 21 14 21C17.866 21 21 17.866 21 14" stroke="#00D4AA" stroke-width="2" stroke-linecap="round"/>
          <circle cx="14" cy="14" r="3" fill="#00D4AA"/>
          <path d="M14 11V7M17 14H21" stroke="#00D4AA" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <span class="navbar__name">VeciReport</span>
    </a>

    <!-- Link para redirigir a registro -->
    <div class="navbar__actions">
      <span class="navbar__hint">¿No tienes cuenta?</span>
      <a href="registro.php" class="btn btn--primary">Registrarse</a>
    </div>
  </nav>


  <!-- 
       layaout principal del login
       Divide la pantalla en dos columnas
       - Izquierda (login__panel): el formulario
       - Derecha (login__showcase): panel decorativo
         con info del sistema (solo visible en desktop) -->
  <main class="login-layout">

    <!-- panel izquierdo - Formulario de login-->
    <div class="login__panel">
      <div class="login__form-wrap">

        <!-- Encabezado del formulario -->
        <div class="login__header">
          <p class="login__tag">Bienvenido de vuelta</p>
          <h1 class="login__title">Inicia sesión</h1>
          <p class="login__subtitle">
            Accede a tu cuenta para gestionar reportes en tu comunidad.
          </p>
        </div>

        <!-- Formulario de login -->
        <form class="login__form" action="App/controllers/UsuarioController.php" method="POST">

          <!-- Campo oculto para indicarle al controlador qué acción debe ejecutar -->
          <input type="hidden" name="accion" value="login">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

          <!-- CAMPO: Correo electrónico -->
          <div class="form__group">
            <label class="form__label" for="correo">
              Correo electrónico
            </label>
            <div class="form__input-wrap">
              <!-- Ícono decorativo dentro del input -->
              <span class="form__icon">
                <!-- SVG de sobre/email -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2"/>
                  <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
              </span>
              <input
                type="email"
                id="correo"
                name="correo"
                class="form__input"
                placeholder="tucorreo@ejemplo.com"
                autocomplete="email"
                required
              >
            </div>
            <!-- Mensaje de error que aparece con JS si la validación falla -->
            <span class="form__error" id="error-correo"></span>
          </div>

          <!-- campo: Contraseña -->
          <div class="form__group">
            <div class="form__label-row">
              <label class="form__label" for="password">Contraseña</label>
              <!-- Link para recuperar contraseña (funcionalidad futura) -->
              <a href="#" class="form__forgot">¿Olvidaste tu contraseña?</a>
            </div>
            <div class="form__input-wrap">
              <!-- Ícono de candado -->
              <span class="form__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
              <input
                type="password"
                id="password"
                name="password"
                class="form__input"
                placeholder="Tu contraseña"
                autocomplete="current-password"
                required
              >
              <!-- Botón para mostrar/ocultar contraseña -->
              <button type="button" class="form__toggle-pass" onclick="togglePassword()" aria-label="Mostrar contraseña">
                <!-- Ícono de ojo (mostrar) -->
                <svg id="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <!-- Ícono de ojo tachado (ocultar) - empieza oculto -->
                <svg id="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
            <span class="form__error" id="error-password"></span>
          </div>

          <!-- Checkbox "Recordarme" -->
          <div class="form__group form__group--check">
            <label class="form__check-label">
              <input type="checkbox" name="recordar" class="form__checkbox">
              <span class="form__check-custom"></span> <!-- Checkbox visual personalizado -->
              Mantener sesión iniciada
            </label>
          </div>

          <!-- Mensaje de error general del servidor (PHP lo puede llenar) -->
          <div class="form__alert form__alert--error" id="alertError" style="display:none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="alertErrorMsg">Correo o contraseña incorrectos.</span>
          </div>

          <!-- Mensaje de éxito (si venimos del registro) -->
          <div class="form__alert form__alert--success" id="alertSuccess" style="display:none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <span>¡Registro exitoso! Ahora puedes iniciar sesión.</span>
          </div>

          <?php if ($demo_activo): ?>
            <div class="login-demo">
              <div>
                <p class="login-demo__title">Demo publica</p>
                <p class="login-demo__text">Usa una cuenta de prueba para revisar el flujo completo.</p>
              </div>
              <div class="login-demo__actions">
                <button type="button" class="login-demo__btn" data-correo="admin@vecireport.com" data-password="VeciAdmin!2026#Demo">Admin</button>
                <button type="button" class="login-demo__btn" data-correo="vecino.demo@vecireport.com" data-password="VecinoDemo!2026#Ok">Vecino</button>
              </div>
            </div>
          <?php endif; ?>

          <!-- boton -->
          <button type="submit" class="btn btn--primary btn--full" id="btnLogin">
            <span class="btn__text">Iniciar sesión</span>
            <!-- Spinner de carga (se muestra mientras espera respuesta del servidor) -->
            <span class="btn__spinner" id="spinner" style="display:none"></span>
          </button>

          <!-- Link para ir al registro -->
          <p class="login__register-link">
            ¿No tienes cuenta?
            <a href="registro.php">Regístrate aquí</a>
          </p>

        </form>
        <!-- Fin del formulario -->

      </div>
    </div>
    <!-- Fin del panel izquierdo -->


    <!--
         panel derecho - Decorativo / Informativo
         Solo visible en pantallas >= 992px (desktop) -->
    <div class="login__showcase">

      <!-- Fondo con círculos decorativos (igual que el hero del index) -->
      <div class="showcase__bg">
        <div class="showcase__circle showcase__circle--1"></div>
        <div class="showcase__circle showcase__circle--2"></div>
        <div class="showcase__grid"></div>
      </div>

      <!-- Contenido del panel decorativo -->
      <div class="showcase__content">

        <!-- Título del panel -->
        <div class="showcase__header">
          <p class="showcase__tag">Sistema VeciReport</p>
          <h2 class="showcase__title">
            Tu comunidad,<br>
            <span class="showcase__title--accent">más conectada.</span>
          </h2>
          <p class="showcase__desc">
            Gestiona incidencias, contacta trabajadores y mantén informado a tu administrador desde un solo lugar.
          </p>
        </div>

        <!-- Lista de características del sistema -->
        <div class="showcase__features">

          <!-- Feature 1: Reportes -->
          <div class="showcase__feature">
            <div class="showcase__feature-icon showcase__feature-icon--green">
              <!-- Ícono de reporte/archivo -->
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
              </svg>
            </div>
            <div>
              <p class="showcase__feature-title">Reportes en tiempo real</p>
              <p class="showcase__feature-desc">Levanta y da seguimiento a tus incidencias al instante.</p>
            </div>
          </div>

          <!-- Feature 2: Trabajadores -->
          <div class="showcase__feature">
            <div class="showcase__feature-icon showcase__feature-icon--orange">
              <!-- Ícono de persona/trabajador -->
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <div>
              <p class="showcase__feature-title">Directorio de trabajadores</p>
              <p class="showcase__feature-desc">Contacta directamente al especialista que necesitas.</p>
            </div>
          </div>

          <!-- Feature 3: Notificaciones -->
          <div class="showcase__feature">
            <div class="showcase__feature-icon showcase__feature-icon--blue">
              <!-- Ícono de campana/notificación -->
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
              </svg>
            </div>
            <div>
              <p class="showcase__feature-title">Notificaciones de estatus</p>
              <p class="showcase__feature-desc">Recibe alertas cuando tu reporte sea atendido.</p>
            </div>
          </div>

        </div>

        <!-- Mini tarjeta de reporte en el panel (igual que en el hero del index) -->
        <div class="showcase__card">
          <div class="showcase__card-header">
            <div class="showcase__card-dot showcase__card-dot--green"></div>
            <span>Sistema activo</span>
          </div>
          <div class="showcase__card-body">
            <p class="showcase__card-text">Último reporte atendido hace</p>
            <p class="showcase__card-time">23 minutos</p>
          </div>
        </div>

      </div>
    </div>
    <!-- Fin del panel derecho -->

  </main>
  <!-- Fin del layout principal -->


  <!-- Script principal del proyecto -->
  <script src="Carpeta JS/funciones.js"></script>

  <script>

    // función: Mostrar/ocultar la contraseña

    function togglePassword() {
      const input    = document.getElementById('password');
      const iconEye    = document.getElementById('icon-eye');
      const iconEyeOff = document.getElementById('icon-eye-off');

      if (input.type === 'password') {
        input.type = 'text';            // Muestra la contraseña
        iconEye.style.display    = 'none';
        iconEyeOff.style.display = 'block';
      } else {
        input.type = 'password';        // Oculta la contraseña
        iconEye.style.display    = 'block';
        iconEyeOff.style.display = 'none';
      }
    }

    //Validación del formulario del cliente
    // Se ejecuta ANTES de enviar al servidor PHP.
    // Esto es validación front-end (no reemplaza
    // la validación en PHP, solo mejora la UX).

    document.querySelectorAll('.login-demo__btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('correo').value = btn.dataset.correo;
        document.getElementById('password').value = btn.dataset.password;
        document.getElementById('alertError').style.display = 'none';
      });
    });

    document.querySelector('.login__form').addEventListener('submit', function(e) {
      e.preventDefault(); // Evita el envío inmediato para validar primero

      let valido = true; // Bandera: si queda true, enviamos el formulario

      // Obtenemos los valores de los campos
      const correo   = document.getElementById('correo').value.trim();
      const password = document.getElementById('password').value;

      // Limpiamos errores anteriores
      document.getElementById('error-correo').textContent   = '';
      document.getElementById('error-password').textContent = '';
      document.getElementById('alertError').style.display   = 'none';

      // --- Validación del correo ---
      // Expresión regular para verificar formato de email
      const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!correo) {
        document.getElementById('error-correo').textContent = 'El correo es obligatorio.';
        valido = false;
      } else if (!regexEmail.test(correo)) {
        document.getElementById('error-correo').textContent = 'Ingresa un correo válido.';
        valido = false;
      }

      // --- Validación de la contraseña ---
      if (!password) {
        document.getElementById('error-password').textContent = 'La contraseña es obligatoria.';
        valido = false;
      } else if (password.length < 6) {
        document.getElementById('error-password').textContent = 'Mínimo 6 caracteres.';
        valido = false;
      }

      // Si pasó todas las validaciones, enviamos el formulario
      if (valido) {
        // Mostramos el spinner y deshabilitamos el botón
        document.getElementById('spinner').style.display  = 'inline-block';
        document.querySelector('.btn__text').textContent  = 'Verificando...';
        document.getElementById('btnLogin').disabled      = true;

        // Enviamos el formulario al servidor PHP
        this.submit();
      }
    });

    // detecta si venimos del registro
    // El registro.php agrega ?registro=ok a la URL
    // Si lo detectamos, mostramos el mensaje de éxito
    const params = new URLSearchParams(window.location.search);
    if (params.get('registro') === 'ok') {
      document.getElementById('alertSuccess').style.display = 'flex';
    }

    // detecta error enviado desde php
    const error = params.get('error');
    if (error === '1') {
      document.getElementById('alertError').style.display = 'flex';
    } else if (error === 'pendiente') {
      document.getElementById('alertErrorMsg').textContent = 'Tu cuenta aún no ha sido aprobada por el administrador.';
      document.getElementById('alertError').style.display = 'flex';
    } else if (error === 'bloqueado') {
      document.getElementById('alertErrorMsg').textContent = 'Tu cuenta ha sido bloqueada. Contacta al administrador.';
      document.getElementById('alertError').style.display = 'flex';
    }
  </script>

</body>
</html>
