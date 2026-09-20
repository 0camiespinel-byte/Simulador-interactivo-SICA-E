<?php
/**
 * SICA-E · Primer Registro de Visitante con Captura Facial y Asistente
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Primer Registro de Visitante';
$activePortal = 'visitantes';

require_once __DIR__ . '/../includes/header.php';
?>

<main style="flex:1; padding: 2.5rem 1rem;">
  <div class="container container-sm">

    <div style="margin-bottom: 1.5rem;">
      <a href="<?= BASE_URL ?>/portal-visitantes/" style="font-size:0.85rem; color:var(--text-muted); font-weight:600; display:inline-flex; align-items:center; gap:0.35rem;">
        ← Volver a opciones de visitantes
      </a>
      <h2 style="font-size:1.75rem; font-weight:800; color:var(--navy); margin-top:0.5rem;">
        Primer Registro de Visitante
      </h2>
      <p style="font-size:0.9rem; color:var(--text-muted);">
        Por favor diligencia tus datos personales y captura tu fotografía para autorizar tu ingreso.
      </p>
    </div>

    <div id="registrationSuccessCard" class="card" style="display:none; text-align:center; padding:2.5rem 1.5rem; margin-bottom:2rem; border-color:var(--success);">
      <div style="width:64px; height:64px; border-radius:50%; background:var(--success-light); color:var(--success); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:2rem;">
        ✓
      </div>
      <h3 style="font-size:1.5rem; font-weight:800; color:var(--navy); margin-bottom:0.5rem;" id="successVisitorName">
        ¡Ingreso Autorizado!
      </h3>
      <p style="color:var(--text-muted); font-size:0.92rem; max-width:480px; margin:0 auto 1.5rem;">
        Tu registro ha sido completado y validado en el sistema SICA-E. Puedes ingresar a las instalaciones institucionales.
      </p>
      <div style="display:inline-block; margin-bottom:1.5rem;">
        <span class="badge badge-success" style="font-size:0.9rem; padding:0.4rem 1rem;">
          Estado: Acceso Autorizado
        </span>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/portal-visitantes/" class="btn btn-navy">
          Finalizar y Volver al Inicio
        </a>
      </div>
    </div>

    <!-- Formulario de Registro -->
    <div id="registrationFormContainer" class="card">
      <form id="newVisitorForm">
        
        <div class="grid-2">
          <!-- Tipo de Documento -->
          <div class="form-group">
            <label class="form-label" for="document_type">Tipo de Documento <span class="required">*</span></label>
            <select id="document_type" name="document_type" class="form-select" required>
              <option value="CC" selected>Cédula de Ciudadanía (CC)</option>
              <option value="TI">Tarjeta de Identidad (TI)</option>
              <option value="CE">Cédula de Extranjería (CE)</option>
              <option value="PAS">Pasaporte</option>
              <option value="PEP">Permiso Especial / PPT</option>
            </select>
          </div>

          <!-- Número de Documento -->
          <div class="form-group">
            <label class="form-label" for="document_number">Número de Documento <span class="required">*</span></label>
            <input type="text" id="document_number" name="document_number" class="form-input" required 
                   placeholder="Ej: 1090123456" autocomplete="off">
            <span class="form-hint">Digita solo números sin puntos ni comas</span>
          </div>
        </div>

        <!-- Nombre Completo -->
        <div class="form-group">
          <label class="form-label" for="full_name">Nombre Completo <span class="required">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-input" required 
                 placeholder="Ej: María Fernanda Gómez Rojas">
        </div>

        <div class="grid-2">
          <!-- Teléfono / Celular -->
          <div class="form-group">
            <label class="form-label" for="phone">Teléfono de Contacto <span class="required">*</span></label>
            <input type="tel" id="phone" name="phone" class="form-input" required 
                   placeholder="Ej: 3101234567">
          </div>

          <!-- Correo Electrónico -->
          <div class="form-group">
            <label class="form-label" for="email">Correo Electrónico (Opcional)</label>
            <input type="email" id="email" name="email" class="form-input" 
                   placeholder="ejemplo@correo.com">
          </div>
        </div>

        <!-- Rol del Visitante -->
        <div class="form-group">
          <label class="form-label" for="role">Tipo de Visitante / Rol <span class="required">*</span></label>
          <select id="role" name="role" class="form-select" required onchange="toggleOrgField(this.value)">
            <option value="padre" selected>Padre / Madre / Acudiente</option>
            <option value="organizacion">Representante de Organización o Entidad Externa</option>
            <option value="universidad">Estudiante Universitario / Prácticas Docentes</option>
          </select>
        </div>

        <!-- Nombre de Organización (Condicional) -->
        <div class="form-group" id="orgGroup" style="display:none;">
          <label class="form-label" for="org_or_university_name">Nombre de la Entidad o Universidad <span class="required">*</span></label>
          <input type="text" id="org_or_university_name" name="org_or_university_name" class="form-input" 
                 placeholder="Ej: Universidad de Pamplona / Secretaría de Salud">
        </div>

        <!-- Motivo de la Visita -->
        <div class="form-group">
          <label class="form-label" for="motivo">Motivo de la Visita <span class="required">*</span></label>
          <select id="motivoSelect" class="form-select" onchange="handleMotivoChange(this.value)" style="margin-bottom:0.5rem;">
            <option value="" disabled selected>-- Selecciona un motivo --</option>
            <?php foreach (VISITOR_MOTIVOS as $m): ?>
              <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" id="motivo" name="motivo" class="form-input" required 
                 placeholder="Escribe el motivo de tu visita (ej: Entrega de documentos para 1102)">
        </div>

        <!-- Módulo de Captura Facial con Cámara Web -->
        <div class="camera-container">
          <label class="form-label" style="text-align:center; margin-bottom:0.75rem;">
            Fotografía de Identificación Biométrica
          </label>

          <div class="camera-preview-box">
            <video id="cameraVideo" playsinline autoplay muted style="display:none;"></video>
            <img id="cameraPreviewImg" alt="Foto capturada" style="display:none;">
            <div id="cameraPlaceholder" class="camera-placeholder">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path><circle cx="12" cy="13" r="3"></circle></svg>
              <span>Cámara desactivada</span>
            </div>
          </div>

          <div id="cameraError" class="alert alert-danger" style="display:none; margin:0 auto 0.75rem; max-width:400px; font-size:0.8rem;"></div>

          <div class="camera-actions">
            <button type="button" id="btnStartCamera" class="btn btn-navy btn-sm">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path><circle cx="12" cy="13" r="3"></circle></svg>
              Activar Cámara
            </button>

            <button type="button" id="btnSnapCamera" class="btn btn-wine btn-sm" style="display:none;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
              Tomar Fotografía
            </button>

            <button type="button" id="btnRetakeCamera" class="btn btn-outline btn-sm" style="display:none;">
              Tomar otra foto
            </button>

            <label class="btn btn-outline btn-sm" style="margin:0; cursor:pointer;">
              <input type="file" id="photoFileInput" accept="image/*" style="display:none;">
              Subir desde archivo
            </label>
          </div>
          
          <input type="hidden" id="photoDataInput" name="photo_data" value="">
        </div>

        <button type="submit" id="btnSubmitVisitor" class="btn btn-wine btn-lg" style="width:100%;">
          Completar Registro y Acceder →
        </button>

      </form>
    </div>

  </div>
</main>

<!-- Widget Asistente Virtual Brighton -->
<div id="mascotWidget" class="mascot-widget">
  <div id="mascotBubble" class="mascot-bubble">
    ¡Hola! Bienvenido al Colegio Brighton. Estoy aquí para guiarte en tu registro.
  </div>
  <div class="mascot-character">
    <img src="<?= BASE_URL ?>/assets/img/mascot.svg" alt="Asistente Brighton">
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/camera.js"></script>
<script src="<?= BASE_URL ?>/assets/js/mascot.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Inicializar cámara
  const cam = new CameraCapture();

  // Cambio de rol
  window.toggleOrgField = function(role) {
    const orgGroup = document.getElementById('orgGroup');
    const orgInput = document.getElementById('org_or_university_name');
    if (role === 'organizacion' || role === 'universidad') {
      orgGroup.style.display = 'block';
      orgInput.required = true;
    } else {
      orgGroup.style.display = 'none';
      orgInput.required = false;
      orgInput.value = '';
    }
  };

  // Cambio de motivo rápido
  window.handleMotivoChange = function(val) {
    const input = document.getElementById('motivo');
    input.value = val;
    input.focus();
  };

  // Enviar formulario
  const form = document.getElementById('newVisitorForm');
  const btnSubmit = document.getElementById('btnSubmitVisitor');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Procesando registro...';

    const formData = new FormData(form);
    const res = await API.post('<?= BASE_URL ?>/api/visitors.php?action=register_new', formData);

    btnSubmit.disabled = false;
    btnSubmit.textContent = 'Completar Registro y Acceder →';

    if (res.success) {
      document.getElementById('registrationFormContainer').style.display = 'none';
      document.getElementById('successVisitorName').textContent = '¡Bienvenido(a), ' + (res.visitor_name || '') + '!';
      document.getElementById('registrationSuccessCard').style.display = 'block';
      
      if (window.sicaMascot) {
        window.sicaMascot.setMessage('¡Registro completado! Que tengas un excelente día en la institución.');
      }
    } else {
      API.toast(res.message || 'Ocurrió un error al registrar.', 'error');
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
