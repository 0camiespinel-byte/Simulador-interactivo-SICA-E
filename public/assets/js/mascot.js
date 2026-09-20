/**
 * SICA-E · Mascota y Asistente Virtual Brighton
 * Muestra al estudiante con uniforme institucional y mensajes contextuales
 */

class VisitorMascot {
  constructor(containerId = 'mascotWidget', bubbleId = 'mascotBubble') {
    this.container = document.getElementById(containerId);
    this.bubble = document.getElementById(bubbleId);
    this.initListeners();
  }

  setMessage(msg) {
    if (!this.bubble) return;
    this.bubble.textContent = msg;
    this.bubble.style.animation = 'none';
    // Forzar reflow para reiniciar animación
    void this.bubble.offsetWidth;
    this.bubble.style.animation = 'bubblePop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
  }

  initListeners() {
    const fieldMessages = {
      'document_number': '¡Hola! Escribe tu número de documento de identidad para identificarte.',
      'document_type': 'Selecciona tu tipo de documento (Cédula, Tarjeta de Identidad, etc.).',
      'full_name': 'Ingresa tus nombres y apellidos completos tal como figuran en tu documento.',
      'phone': 'Tu número de teléfono nos ayuda a contactarte en caso de alguna eventualidad.',
      'email': 'Opcional: déjanos tu correo electrónico para recibir confirmaciones.',
      'role': '¿Nos visitas como padre/acudiente, representante de una entidad o universidad?',
      'org_or_university_name': 'Escribe el nombre de la institución o empresa que representas.',
      'motivo': 'Selecciona o escribe la razón por la que visitas hoy la Institución Educativa Brighton.',
    };

    Object.keys(fieldMessages).forEach(fieldName => {
      const el = document.querySelector(`[name="${fieldName}"]`) || document.getElementById(fieldName);
      if (el) {
        el.addEventListener('focus', () => {
          this.setMessage(fieldMessages[fieldName]);
        });
      }
    });

    window.addEventListener('photoCaptured', () => {
      this.setMessage('¡Excelente foto! Tu identificación biométrica quedó registrada correctamente.');
    });
  }
}

// Inicializar automáticamente si el elemento existe en el DOM
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('mascotWidget')) {
    window.sicaMascot = new VisitorMascot();
  }
});
