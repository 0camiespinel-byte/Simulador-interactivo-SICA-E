<?php
/**
 * SICA-E · Gestión de Excusas y Justificaciones de Inasistencia
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('profesor');
$currentUser = Auth::user();
$pageTitle = 'Excusas y Justificaciones';
$activePortal = 'docentes';

$db = getDB();

$myGrade  = $currentUser['titular_grade'] ?? 'Undécimo';
$myCourse = $currentUser['titular_course'] ?? '02';
$preselectStudent = intval($_GET['student_id'] ?? 0);

// Estudiantes del curso
$stmtSt = $db->prepare("SELECT id, full_name, document_number FROM students WHERE grade = ? AND course = ? ORDER BY full_name ASC");
$stmtSt->execute([$myGrade, $myCourse]);
$students = $stmtSt->fetchAll();

// Historial de excusas del grupo
$stmtExc = $db->prepare("
    SELECT e.id, e.motivo, e.fecha, e.descripcion, e.status, e.created_at,
           s.full_name as student_name, s.document_number,
           u.full_name as teacher_name
    FROM excuses e
    JOIN students s ON s.id = e.student_id
    LEFT JOIN users u ON u.id = e.teacher_id
    WHERE s.grade = ? AND s.course = ?
    ORDER BY e.fecha DESC
");
$stmtExc->execute([$myGrade, $myCourse]);
$excuses = $stmtExc->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-docentes/" class="nav-tab-item">
    🏠 Inicio Docente
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/asistencia.php" class="nav-tab-item">
    📝 Toma de Asistencia
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/estudiantes.php" class="nav-tab-item">
    👥 Lista de Estudiantes
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/excusas.php" class="nav-tab-item active">
    📑 Excusas y Justificaciones
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/reportes.php" class="nav-tab-item">
    📊 Reportes de Asistencia
  </a>
</nav>

<main style="flex:1; padding: 2rem 1rem;">
  <div class="container container-lg">

    <div class="quick-toolbar">
      <div>
        <h2 style="font-size:1.5rem; font-weight:800; color:var(--navy);">
          Gestión de Justificaciones e Incapacidades
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Radicación de permisos y sincronización inmediata con la lista de asistencia
        </p>
      </div>

      <button type="button" class="btn btn-wine btn-sm" onclick="document.getElementById('modalNewExcuse').classList.add('active')">
        + Radicar Nueva Excusa
      </button>
    </div>

    <!-- Historial de Excusas -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">📋 Registro de Excusas de <?= htmlspecialchars($myGrade . ' - ' . $myCourse) ?></h3>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Estudiante</th>
              <th>Fecha de Falta</th>
              <th>Motivo de la Excusa</th>
              <th>Detalles / Observaciones</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($excuses)): ?>
              <tr>
                <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">
                  No hay excusas radicadas para este grupo.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($excuses as $ex): ?>
                <tr>
                  <td>#<?= $ex['id'] ?></td>
                  <td><strong><?= htmlspecialchars($ex['student_name']) ?></strong></td>
                  <td><?= date('d/m/Y', strtotime($ex['fecha'])) ?></td>
                  <td><?= htmlspecialchars($ex['motivo']) ?></td>
                  <td style="font-size:0.82rem; color:var(--text-muted);"><?= htmlspecialchars($ex['descripcion'] ?: 'Sin observaciones') ?></td>
                  <td>
                    <span class="badge <?= $ex['status'] === 'aprobada' ? 'badge-success' : ($ex['status'] === 'pendiente' ? 'badge-warning' : 'badge-danger') ?>">
                      <?= htmlspecialchars($ex['status']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Modal Radicar Excusa -->
<div id="modalNewExcuse" class="modal-overlay <?= $preselectStudent > 0 ? 'active' : '' ?>">
  <div class="modal-card">
    <div class="modal-header">
      <h3 style="font-size:1.15rem; font-weight:700; color:var(--navy);">Radicar Excusa o Justificante</h3>
      <button type="button" onclick="document.getElementById('modalNewExcuse').classList.remove('active')" style="background:none; border:none; font-size:1.25rem; cursor:pointer;">&times;</button>
    </div>
    <form id="excuseForm">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Estudiante <span class="required">*</span></label>
          <select name="student_id" class="form-select" required>
            <option value="" disabled selected>-- Selecciona un estudiante --</option>
            <?php foreach ($students as $st): ?>
              <option value="<?= $st['id'] ?>" <?= $preselectStudent === $st['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['document_number']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Fecha de Inasistencia <span class="required">*</span></label>
            <input type="date" name="fecha" class="form-input" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Estado de la Excusa</label>
            <select name="status" class="form-select">
              <option value="aprobada" selected>Aprobada (Justifica falta)</option>
              <option value="pendiente">Pendiente de revisión</option>
              <option value="rechazada">Rechazada</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Motivo de la Falta <span class="required">*</span></label>
          <select name="motivo" class="form-select" required>
            <option value="Incapacidad médica EPS / Certificado">Incapacidad médica EPS / Certificado</option>
            <option value="Cita médica o odontológica">Cita médica u odontológica</option>
            <option value="Calamidad doméstica o familiar">Calamidad doméstica o familiar</option>
            <option value="Representación institucional / Deportiva">Representación institucional / Deportiva</option>
            <option value="Fuerza mayor / Traslado">Fuerza mayor / Traslado</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Descripción u Observaciones</label>
          <textarea name="descripcion" class="form-textarea" rows="3" placeholder="Detalles de la justificación presentada..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalNewExcuse').classList.remove('active')">Cancelar</button>
        <button type="submit" class="btn btn-wine">Guardar y Justificar</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('excuseForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const res = await API.post('<?= BASE_URL ?>/api/attendance.php?action=save_excuse', formData);
  if (res.success) {
    API.toast(res.message, 'success');
    setTimeout(() => location.href = '<?= BASE_URL ?>/portal-docentes/excusas.php', 800);
  } else {
    API.toast(res.message, 'error');
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
