<?php
/**
 * SICA-E · Acceso Rápido para Visitantes Registrados
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Acceso Rápido - Visitante Registrado';
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
        Acceso Rápido para Visitantes Registrados
      </h2>
      <p style="font-size:0.9rem; color:var(--text-muted);">
        Ingresa tu número de documento de identidad para validar tus datos y confirmar tu entrada.
      </p>
    </div>

    <!-- Pantalla de Éxito -->
    <div id="quickSuccessCard" class="card" style="display:none; text-align:center; padding:2.5rem 1.5rem; margin-bottom:2rem; border-color:var(--success);">
      <div style="width:64px; height:64px; border-radius:50%; background:var(--success-light); color:var(--success); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:2rem;">
        ✓
      </div>
      <h3 style="font-size:1.5rem; font-weight:800; color:var(--navy); margin-bottom:0.5rem;" id="quickVisitorSuccessName">
        ¡Ingreso Confirmado!
      </h3>
      <p style="color:var(--text-muted); font-size:0.92rem; max-width:480px; margin:0 auto 1.5rem;">
        Tu acceso ha sido registrado en la bitácora institucional con fecha y hora actual.
      </p>
      <div style="display:inline-block; margin-bottom:1.5rem;">
        <span class="badge badge-success" style="font-size:0.9rem; padding:0.4rem 1rem;">
          Estado: Acceso Autorizado
        </span>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/portal-visitantes/" class="btn btn-navy">
          Volver a la Entrada
        </a>
      </div>
    </div>

    <!-- Búsqueda y Validación -->
    <div id="quickSearchCard" class="card">
      <form id="lookupForm">
        <div class="form-group">
          <label class="form-label" for="lookupDoc">Número de Documento de Identidad <span class="required">*</span></label>
          <div style="display:flex; gap:0.5rem;">
            <input type="text" id="lookupDoc" name="document_number" class="form-input" required 
                   placeholder="Digita tu número de cédula o documento" autocomplete="off" autofocus>
            <button type="submit" id="btnLookup" class="btn btn-navy" style="white-space:nowrap;">
              Buscar Datos
            </button>
          </div>
          <span class="form-hint">Escribe tu documento y presiona Enter o Buscar</span>
        </div>
      </form>

      <!-- Alerta si no existe -->
      <div id="notFoundAlert" class="alert alert-warning" style="display:none;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <div>
          <p style="font-weight:700; margin-bottom:0.25rem;">Documento no encontrado</p>
          <p style="font-size:0.85rem; margin-bottom:0.75rem;">Aún no tienes un registro previo en la Institución Educativa Brighton.</p>
          <a href="<?= BASE_URL ?>/portal-visitantes/nuevo.php" class="btn btn-sm btn-wine">
            Realizar mi Primer Registro →
          </a>
        </div>
      </div>

      <!-- Tarjeta de Confirmación de Datos del Visitante -->
      <div id="visitorProfileCard" style="display:none; margin-top:1.5rem; border-top:1px solid var(--border); padding-top:1.5rem;">
        <div style="display:flex; align-items:center; gap:1.25rem; background:var(--bg-muted); padding:1.25rem; border-radius:var(--radius-md); margin-bottom:1.5rem;">
          <img id="visPhoto" src="" alt="Foto" style="width:72px; height:72px; border-radius:var(--radius-md); object-fit:cover; border:2px solid #ffffff; box-shadow:var(--shadow-sm);">
          <div>
            <h4 id="visName" style="font-size:1.15rem; font-weight:700; color:var(--navy); margin-bottom:0.25rem;"></h4>
            <p id="visDoc" style="font-size:0.85rem; color:var(--text-muted);"></p>
            <div style="margin-top:0.4rem;">
              <span id="visRoleBadge" class="badge badge-navy"></span>
              <span id="visOrg" style="font-size:0.8rem; color:var(--text-muted); margin-left:0.5rem;"></span>
            </div>
          </div>
        </div>

        <form id="quickConfirmForm">
          <input type="hidden" id="confirmedDoc" name="document_number" value="">
          
          <div class="form-group">
            <label class="form-label" for="quickMotivo">Motivo de la Visita Hoy <span class="required">*</span></label>
            <select id="quickMotivoSelect" class="form-select" onchange="document.getElementById('quickMotivo').value = this.value" style="margin-bottom:0.5rem;">
              <option value="" disabled selected>-- Selecciona un motivo --</option>
              <?php foreach (VISITOR_MOTIVOS as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" id="quickMotivo" name="motivo" class="form-input" required 
                   placeholder="Describe el motivo de tu visita hoy">
          </div>

          <button type="submit" id="btnConfirmQuick" class="btn btn-wine btn-lg" style="width:100%;">
            Confirmar Ingreso en Tiempo Real →
          </button>
        </form>
      </div>

    </div>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const lookupForm = document.getElementById('lookupForm');
  const lookupDoc = document.getElementById('lookupDoc');
  const btnLookup = document.getElementById('btnLookup');
  const notFoundAlert = document.getElementById('notFoundAlert');
  const visitorProfileCard = document.getElementById('visitorProfileCard');
  const quickConfirmForm = document.getElementById('quickConfirmForm');
  const btnConfirmQuick = document.getElementById('btnConfirmQuick');

  // Buscar visitante
  lookupForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const doc = lookupDoc.value.trim();
    if (!doc) return;

    btnLookup.disabled = true;
    btnLookup.textContent = 'Buscando...';
    notFoundAlert.style.display = 'none';
    visitorProfileCard.style.display = 'none';

    const res = await API.get('<?= BASE_URL ?>/api/visitors.php', { action: 'lookup', document_number: doc });

    btnLookup.disabled = false;
    btnLookup.textContent = 'Buscar Datos';

    if (res.success && res.visitor) {
      const v = res.visitor;
      document.getElementById('visName').textContent = v.full_name;
      document.getElementById('visDoc').textContent = (v.document_type || 'CC') + ' ' + v.document_number;
      document.getElementById('visPhoto').src = v.photo_url || 'https://api.dicebear.com/9.x/avataaars/svg?seed=' + encodeURIComponent(v.full_name);
      document.getElementById('visRoleBadge').textContent = v.role;
      document.getElementById('visOrg').textContent = v.org_or_university_name || '';
      document.getElementById('confirmedDoc').value = v.document_number;
      
      const defMotivo = v.default_motivo || 'Reunión con docente';
      document.getElementById('quickMotivo').value = defMotivo;

      visitorProfileCard.style.display = 'block';
      document.getElementById('quickMotivo').focus();
    } else {
      notFoundAlert.style.display = 'flex';
    }
  });

  // Confirmar ingreso
  quickConfirmForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    btnConfirmQuick.disabled = true;
    btnConfirmQuick.textContent = 'Registrando ingreso...';

    const formData = new FormData(quickConfirmForm);
    const res = await API.post('<?= BASE_URL ?>/api/visitors.php?action=quick_entry', formData);

    btnConfirmQuick.disabled = false;
    btnConfirmQuick.textContent = 'Confirmar Ingreso en Tiempo Real →';

    if (res.success) {
      document.getElementById('quickSearchCard').style.display = 'none';
      document.getElementById('quickVisitorSuccessName').textContent = '¡Bienvenido(a), ' + (res.visitor_name || '') + '!';
      document.getElementById('quickSuccessCard').style.display = 'block';
    } else {
      API.toast(res.message || 'Error al registrar el acceso.', 'error');
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
