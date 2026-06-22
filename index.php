<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VeciReport — Comunidad Conectada</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="Carpeta CSS/style.css">
</head>

<body>

  <!-- Barra de navegación fija -->
  <nav class="navbar">
    <div class="navbar__brand">
      <div class="navbar__logo">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
          <circle cx="14" cy="14" r="13" stroke="#E85D26" stroke-width="2"/>
          <path d="M14 7C10.134 7 7 10.134 7 14C7 17.866 10.134 21 14 21C17.866 21 21 17.866 21 14" stroke="#E85D26" stroke-width="2" stroke-linecap="round"/>
          <circle cx="14" cy="14" r="3" fill="#E85D26"/>
          <path d="M14 11V7M17 14H21" stroke="#E85D26" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <span class="navbar__name">VeciReport</span>
    </div>

    <div class="navbar__actions">
      <a href="login.php" class="btn btn--ghost">Iniciar sesión</a>
      <a href="registro.php" class="btn btn--primary">Comenzar gratis</a>
    </div>

    <button class="navbar__toggle" onclick="toggleMenu()" aria-label="Menú">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </nav>

  <div class="mobile-menu" id="mobileMenu">
    <a href="login.php">Iniciar sesión</a>
    <a href="registro.php">Comenzar gratis</a>
  </div>


  <!-- HERO — Sección principal -->
  <section class="hero">

    <div class="hero__bg">
      <div class="hero__circle hero__circle--1"></div>
      <div class="hero__circle hero__circle--2"></div>
      <div class="hero__circle hero__circle--3"></div>
      <div class="hero__grid"></div>
    </div>

    <div class="hero__inner">

    <!-- Columna izquierda: contenido -->
    <div class="hero__content">

      <div class="hero__badge">
        <span class="badge__dot"></span>
        Sistema activo · Fraccionamiento conectado
      </div>

      <h1 class="hero__title">
        Reporta problemas.<br>
        <span class="hero__title--accent">Conecta tu comunidad.</span>
      </h1>

      <p class="hero__subtitle">
        Gestiona incidencias de luz, agua y servicios en tu fraccionamiento.
        Rápido, simple y directo con quienes pueden ayudarte.
      </p>

      <div class="hero__cta">
        <a href="registro.php" class="btn btn--lg btn--primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5l7 7-7 7"/>
          </svg>
          Crear mi cuenta
        </a>
        <a href="#categorias" class="btn btn--lg btn--outline">Ver categorías</a>
      </div>

      <div class="hero__stats">
        <div class="stat">
          <span class="stat__num">+120</span>
          <span class="stat__label">Vecinos activos</span>
        </div>
        <div class="stat__divider"></div>
        <div class="stat">
          <span class="stat__num">+48</span>
          <span class="stat__label">Reportes resueltos</span>
        </div>
        <div class="stat__divider"></div>
        <div class="stat">
          <span class="stat__num">12</span>
          <span class="stat__label">Trabajadores listos</span>
        </div>
      </div>
    </div>

    <!-- Columna derecha: mock interactivo + chips flotantes -->
    <div class="hero__visual">

      <!-- Chip flotante superior-izquierdo -->
      <div class="hero__float hero__float--1">
        <div class="hero__float-icon">✅</div>
        <div>
          <div class="hero__float-text">Reporte atendido</div>
          <div class="hero__float-sub">Hace 3 min</div>
        </div>
      </div>

      <!-- Chip flotante inferior-derecho -->
      <div class="hero__float hero__float--2">
        <div class="hero__float-icon">🔧</div>
        <div>
          <div class="hero__float-text">Trabajador asignado</div>
          <div class="hero__float-sub">Juan R. · Plomero</div>
        </div>
      </div>

      <div class="hero__card-mock">
        <div class="mock__header">
          <div class="mock__dot mock__dot--red"></div>
          <div class="mock__dot mock__dot--yellow"></div>
          <div class="mock__dot mock__dot--green"></div>
          <span class="mock__title">Reporte #0047</span>
        </div>
        <div class="mock__body">
          <div class="mock__tag mock__tag--agua">💧 Agua</div>
          <p class="mock__desc">Fuga de agua en calle principal frente a casa #12</p>
          <div class="mock__meta">
            <span class="mock__status mock__status--proceso">⏳ En proceso</span>
            <span class="mock__time">Hace 2 horas</span>
          </div>
          <div class="mock__worker">
            <div class="mock__avatar">JR</div>
            <div>
              <p class="mock__worker-name">Juan Rodríguez</p>
              <p class="mock__worker-role">Plomero asignado</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    </div><!-- /.hero-inner -->
  </section>


  <!-- CATEGORÍAS -->
  <section class="categorias" id="categorias">
    <div class="container">

      <div class="section-header">
        <p class="section-tag">¿Qué puedes reportar?</p>
        <h2 class="section-title">Categorías de reporte</h2>
        <p class="section-sub">Selecciona la categoría que corresponde a tu problema para que podamos asignarlo al especialista correcto.</p>
      </div>

      <div class="cards-grid">

        <div class="card card--luz" onclick="window.location.href='reporte.php?tipo=luz'">
          <div class="card__icon-wrap">
            <img src="Carpeta Img/luz.jpg" alt="Luz" class="card__img">
            <div class="card__icon-glow card__icon-glow--luz"></div>
          </div>
          <div class="card__content">
            <h3 class="card__title">Luz</h3>
            <p class="card__desc">Alumbrado público apagado, cables caídos o cortocircuitos en la colonia.</p>
            <span class="card__link">Reportar →</span>
          </div>
          <div class="card__badge">Urgente</div>
        </div>

        <div class="card card--agua" onclick="window.location.href='reporte.php?tipo=agua'">
          <div class="card__icon-wrap">
            <img src="Carpeta Img/agua.jpg" alt="Agua" class="card__img">
            <div class="card__icon-glow card__icon-glow--agua"></div>
          </div>
          <div class="card__content">
            <h3 class="card__title">Agua</h3>
            <p class="card__desc">Fugas, falta de suministro, tuberías dañadas o problemas con el servicio de agua.</p>
            <span class="card__link">Reportar →</span>
          </div>
        </div>

        <div class="card card--trabajadores" onclick="window.location.href='directorio.php'">
          <div class="card__icon-wrap">
            <img src="Carpeta Img/Trabajadores.jpg" alt="Trabajadores" class="card__img">
            <div class="card__icon-glow card__icon-glow--trabajadores"></div>
          </div>
          <div class="card__content">
            <h3 class="card__title">Trabajadores</h3>
            <p class="card__desc">Consulta el directorio de trabajadores disponibles y contacta al especialista adecuado.</p>
            <span class="card__link">Ver directorio →</span>
          </div>
        </div>

        <div class="card card--otros" onclick="window.location.href='reporte.php?tipo=otros'">
          <div class="card__icon-wrap">
            <img src="Carpeta Img/otros.jpg" alt="Otros" class="card__img">
            <div class="card__icon-glow card__icon-glow--otros"></div>
          </div>
          <div class="card__content">
            <h3 class="card__title">Otros</h3>
            <p class="card__desc">Mantenimiento general, áreas comunes, banquetas, jardines u otro tipo de incidencia.</p>
            <span class="card__link">Reportar →</span>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- FEATURES — ¿Por qué VeciReport? -->
  <section class="features">
    <div class="container">

      <div class="section-header">
        <p class="section-tag">Beneficios</p>
        <h2 class="section-title">¿Por qué VeciReport?</h2>
        <p class="section-sub">Una plataforma diseñada para hacer la gestión vecinal más eficiente, transparente y accesible para todos.</p>
      </div>

      <div class="features-grid">

        <div class="feature-card">
          <div class="feature-card__icon-wrap">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E85D26" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
            </svg>
          </div>
          <h3 class="feature-card__title">Respuesta en minutos</h3>
          <p class="feature-card__desc">Los reportes se asignan automáticamente al trabajador disponible más adecuado. Sin llamadas, sin filas, sin esperas innecesarias.</p>
        </div>

        <div class="feature-card">
          <div class="feature-card__icon-wrap">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E85D26" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <h3 class="feature-card__title">Seguimiento en tiempo real</h3>
          <p class="feature-card__desc">Consulta el estado de tus reportes — pendiente, en proceso o atendido — desde tu panel personal, en cualquier momento.</p>
        </div>

        <div class="feature-card">
          <div class="feature-card__icon-wrap">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E85D26" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <h3 class="feature-card__title">Comunidad organizada</h3>
          <p class="feature-card__desc">Reportes individuales y colectivos para problemas que afectan a toda la calle. La voz de todos, en un solo lugar.</p>
        </div>

      </div>
    </div>
  </section>


  <!-- CÓMO FUNCIONA -->
  <section class="como-funciona">
    <div class="container">

      <div class="section-header">
        <p class="section-tag">Proceso</p>
        <h2 class="section-title">¿Cómo funciona?</h2>
        <p class="section-sub">Cuatro pasos simples para resolver cualquier incidencia en tu fraccionamiento.</p>
      </div>

      <div class="pasos">

        <div class="paso">
          <div class="paso__num">01</div>
          <div class="paso__content">
            <h3>Regístrate</h3>
            <p>Crea tu cuenta como vecino verificado con tu comprobante de domicilio.</p>
          </div>
        </div>

        <div class="paso__arrow">→</div>

        <div class="paso">
          <div class="paso__num">02</div>
          <div class="paso__content">
            <h3>Reporta</h3>
            <p>Selecciona la categoría, describe el problema e indica si es individual o colectivo.</p>
          </div>
        </div>

        <div class="paso__arrow">→</div>

        <div class="paso">
          <div class="paso__num">03</div>
          <div class="paso__content">
            <h3>Se asigna</h3>
            <p>El administrador asigna un trabajador disponible y el estado cambia a "En proceso".</p>
          </div>
        </div>

        <div class="paso__arrow">→</div>

        <div class="paso">
          <div class="paso__num">04</div>
          <div class="paso__content">
            <h3>Resuelto</h3>
            <p>Recibes la confirmación cuando tu reporte sea atendido y marcado como resuelto.</p>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- CTA BANNER — llamada a acción final -->
  <section class="cta-banner">
    <div class="container">
      <div class="cta-banner__inner">
        <div>
          <p class="cta-banner__label">Únete hoy</p>
          <h2 class="cta-banner__title">¿Listo para mejorar tu comunidad?</h2>
          <p class="cta-banner__sub">Regístrate en menos de 2 minutos y empieza a reportar incidencias de forma organizada.</p>
        </div>
        <div class="cta-banner__actions">
          <a href="registro.php" class="btn btn--lg btn--primary">Crear mi cuenta</a>
          <a href="login.php" class="btn btn--lg btn--ghost">Ya tengo cuenta</a>
        </div>
      </div>
    </div>
  </section>


  <!-- FOOTER -->
  <footer class="footer">
    <div class="container footer__inner">

      <div class="footer__brand">
        <span class="footer__logo">VeciReport</span>
        <p>Plataforma de gestión vecinal para comunidades conectadas y organizadas.</p>
      </div>

      <div class="footer__links">
        <p class="footer__link-title">Sistema</p>
        <a href="login.php">Iniciar sesión</a>
        <a href="registro.php">Registrarse</a>
        <a href="directorio.php">Directorio</a>
      </div>

      <div class="footer__links">
        <p class="footer__link-title">Reportes</p>
        <a href="reporte.php?tipo=luz">Luz</a>
        <a href="reporte.php?tipo=agua">Agua</a>
        <a href="reporte.php?tipo=otros">Otros</a>
      </div>

    </div>

    <div class="footer__bottom">
      <p>© 2026 VeciReport — Comunidad Conectada · UTCJ DMS52</p>
    </div>
  </footer>


  <script src="Carpeta JS/funciones.js"></script>

  <script>
    function toggleMenu() {
      document.getElementById('mobileMenu').classList.toggle('active');
    }

    // IntersectionObserver con stagger suave escalonado
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.classList.add('animate-in');
          }, i * 90);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });

    document.querySelectorAll('.card, .paso, .feature-card').forEach(el => observer.observe(el));
  </script>

</body>
</html>
