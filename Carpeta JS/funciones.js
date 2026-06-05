/* funciones.js - Funciones utilitarias compartidas entre todas las páginas
   VeciReport · Carpeta JS */


/* Abre/cierra el sidebar en móvil.
   Todas las páginas internas usan esta función. */
function toggleSidebar() {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  if (sidebar)  sidebar.classList.toggle('sidebar--open');
  if (overlay)  overlay.classList.toggle('overlay--visible');
}


/* Formatea una fecha en texto relativo (hace X tiempo).
   Uso: formatearFecha(new Date('2026-05-10')) → "Hace 1 día" */
function formatearFecha(fecha) {
  const ahora   = new Date();
  const diff    = Math.floor((ahora - new Date(fecha)) / 1000); // segundos

  if (diff < 60)                    return 'Hace un momento';
  if (diff < 3600)                  return `Hace ${Math.floor(diff / 60)} minutos`;
  if (diff < 86400)                 return `Hace ${Math.floor(diff / 3600)} horas`;
  if (diff < 604800)                return `Hace ${Math.floor(diff / 86400)} días`;
  return new Date(fecha).toLocaleDateString('es-MX');
}


/* Muestra un toast de notificación en la esquina inferior derecha.
   tipo: 'success' (verde) | 'error' (rojo)
   Uso: mostrarToast('Guardado correctamente', 'success') */
function mostrarToast(mensaje, tipo = 'success') {
  /* Busca el toast existente o lo crea si no existe */
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    document.body.appendChild(toast);
  }

  toast.textContent = mensaje;
  toast.className   = `toast toast--${tipo} toast--visible`;

  /* Lo oculta después de 3 segundos */
  setTimeout(() => { toast.className = 'toast'; }, 3000);
}


/* Valida que un correo tenga formato válido.
   Retorna true si es válido, false si no.
   Uso: validarCorreo('juan@gmail.com') → true */
function validarCorreo(correo) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
}


/* Cierra cualquier modal visible en la página.
   Busca el overlay por ID estándar. */
function cerrarModal() {
  const overlay = document.getElementById('modalOverlay');
  if (overlay) overlay.style.display = 'none';
}


/* Cierra el modal solo si el clic fue en el overlay (fondo oscuro)
   y no dentro del modal en sí.
   Uso en el onclick del overlay: cerrarModalSiOverlay(event) */
function cerrarModalSiOverlay(e) {
  const overlay = document.getElementById('modalOverlay');
  if (e.target === overlay) cerrarModal();
}


/* Listener global para cerrar modales con la tecla Escape */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') cerrarModal();
});


/* Activa las animaciones de entrada en elementos con clase .animate-in.
   Se llama al cargar cualquier página con tarjetas o listas. */
function activarAnimaciones(selector = '.kpi-card, .card, .paso, .mr-item, .worker-card-dir') {
  document.querySelectorAll(selector).forEach((el, i) => {
    el.style.animationDelay = `${i * 0.07}s`;
    el.classList.add('animate-in');
  });
}


/* Ejecuta activarAnimaciones automáticamente al cargar el DOM */
document.addEventListener('DOMContentLoaded', function() {
  activarAnimaciones();
});