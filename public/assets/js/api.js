/**
 * SICA-E · Cliente API y Funciones Asíncronas
 * Institución Educativa Brighton Pamplona
 */

const API = {
  /**
   * Petición POST genérica con JSON o FormData
   */
  async post(url, data) {
    let options = { method: 'POST' };

    if (data instanceof FormData) {
      options.body = data;
    } else {
      options.headers = { 'Content-Type': 'application/json' };
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, options);
      const text = await response.text();
      try {
        return JSON.parse(text);
      } catch (err) {
        console.error("Respuesta no es JSON válido:", text);
        return { success: false, message: 'Respuesta inesperada del servidor: ' + text.slice(0, 100) };
      }
    } catch (error) {
      console.error("Error de red / API:", error);
      return { success: false, message: 'No se pudo comunicar con el servidor.' };
    }
  },

  /**
   * Petición GET genérica
   */
  async get(url, params = {}) {
    const urlObj = new URL(url, window.location.origin);
    Object.keys(params).forEach(k => urlObj.searchParams.append(k, params[k]));

    try {
      const response = await fetch(urlObj.toString());
      return await response.json();
    } catch (error) {
      console.error("Error GET API:", error);
      return { success: false, message: 'Error al consultar datos.' };
    }
  },

  /**
   * Helper visual para mostrar notificaciones toast elegantes
   */
  toast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || (() => {
      const div = document.createElement('div');
      div.id = 'toastContainer';
      div.style.cssText = 'position:fixed;top:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;pointer-events:none;';
      document.body.appendChild(div);
      return div;
    })();

    const toast = document.createElement('div');
    const colors = {
      success: 'background:#16a34a;color:#ffffff;',
      error: 'background:#dc2626;color:#ffffff;',
      warning: 'background:#ca8a04;color:#ffffff;',
      info: 'background:#162544;color:#ffffff;'
    };

    toast.style.cssText = `${colors[type] || colors.info}padding:0.75rem 1.25rem;border-radius:8px;font-size:0.88rem;font-weight:600;box-shadow:0 8px 16px rgba(0,0,0,0.15);pointer-events:auto;transition:all 0.3s ease;transform:translateY(-10px);opacity:0;`;
    toast.textContent = message;

    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
    }, 10);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-10px)';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }
};

window.API = API;
