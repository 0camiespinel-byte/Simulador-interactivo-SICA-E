<?php
/**
 * SICA-E · Toma de Asistencia Escolar Interactiva (3 Estados)
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('profesor');
$currentUser = Auth::user();
$pageTitle = 'Toma de Asistencia';
$activePortal = 'docentes';

$db = getDB();

// Selección de Grado, Curso y Fecha
$selGrade  = trim($_GET['grade'] ?? $currentUser['titular_grade'] ?? 'Undécimo');
$selCourse = trim($_GET['course'] ?? $currentUser['titular_course'] ?? '02');
$selDate   = trim($_GET['date'] ?? date('Y-m-d'));

// Consultar estudiantes del grupo con su estado para la fecha seleccionada
$stmt = $db->prepare("
    SELECT s.id, s.full_name, s.document_number, s.grade, s.course, s.section, s.photo_url,
           a.status as attendance_status, a.id as attendance_id, a.updated_at
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id AND a.date = ?
    WHERE s.grade = ? AND s.course = ?
    ORDER BY s.full_name ASC
");
$stmt->execute([$selDate, $selGrade, $selCourse]);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-docentes/" class="nav-tab-item">
    🏠 Inicio Docente
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/asistencia.php" class="nav-tab-item active">
    📝 Toma de Asistencia
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/estudiantes.php" class="nav-tab-item">
    👥 Lista de Estudiantes
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/excusas.php" class="nav-tab-item">
    📑 Excusas y Justificaciones
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/reportes.php" class="nav-tab-item">
    📊 Reportes de Asistencia
  </a>
</nav>

<main style="flex:1; padding: 2rem 1rem;">
  <div class="container container-lg">

    <!-- Selector de Grado, Curso y Fecha -->
    <div class="card" style="margin-bottom:1.75rem; padding:1.25rem;">
      <form method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex:1; min-width:180px;">
          <label class="form-label">Grado Académico</label>
          <select name="grade" class="form-select" onchange="this.form.submit()">
            <option value="Undécimo" <?= $selGrade === 'Undécimo' ? 'selected' : '' ?>>Undécimo (11°)</option>
            <option value="Décimo" <?= $selGrade === 'Décimo' ? 'selected' : '' ?>>Décimo (10°)</option>
            <option value="Noveno" <?= $selGrade === 'Noveno' ? 'selected' : '' ?>>Noveno (9°)</option>
            <option value="Octavo" <?= $selGrade === 'Octavo' ? 'selected' : '' ?>>Octavo (8°)</option>
            <option value="Séptimo" <?= $selGrade === 'Séptimo' ? 'selected' : '' ?>>Séptimo (7°)</option>
            <option value="Sexto" <?= $selGrade === 'Sexto' ? 'selected' : '' ?>>Sexto (6°)</option>
            <option value="Quinto" <?= $selGrade === 'Quinto' ? 'selected' : '' ?>>Quinto (5°)</option>
          </select>
        </div>

        <div style="width:140px;">
          <label class="form-label">Curso / Salón</label>
          <select name="course" class="form-select" onchange="this.form.submit()">
            <option value="01" <?= $selCourse === '01' ? 'selected' : '' ?>>Curso 01</option>
            <option value="02" <?= $selCourse === '02' ? 'selected' : '' ?>>Curso 02</option>
          </select>
        </div>

        <div style="width:170px;">
          <label class="form-label">Fecha de Llamado</label>
          <input type="date" name="date" class="form-input" value="<?= htmlspecialchars($selDate) ?>" onchange="this.form.submit()">
        </div>

        <button type="submit" class="btn btn-navy">
          Cargar Lista
        </button>

        <button type="button" class="btn btn-outline" onclick="marcarTodosPresentes()" style="margin-left:auto;">
          ✓ Todos Presentes
        </button>
      </form>
    </div>

    <!-- Barra Informativa de Conteo en Vivo -->
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem;">
      <div>
        <h3 style="font-size:1.35rem; font-weight:800; color:var(--navy);">
          <?= htmlspecialchars($selGrade) ?> - <?= htmlspecialchars($selCourse) ?>
        </h3>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Fecha: <strong><?= date('d/m/Y', strtotime($selDate)) ?></strong> · Total Alumnos: <strong><?= count($students) ?></strong>
        </p>
      </div>

      <div style="display:flex; gap:0.5rem; align-items:center;">
        <span class="badge badge-success" id="counterPresentes">Presentes: 0</span>
        <span class="badge badge-warning" id="counterJustificadas">Justificadas: 0</span>
        <span class="badge badge-danger" id="counterAusentes">Ausentes: 0</span>
      </div>
    </div>

    <!-- Lista de Estudiantes (Tarjetas Interactivas) -->
    <?php if (empty($students)): ?>
      <div class="card" style="text-align:center; padding:3rem 1rem;">
        <p style="color:var(--text-muted); font-size:1.05rem;">
          No hay estudiantes matriculados en <strong><?= htmlspecialchars($selGrade . ' ' . $selCourse) ?></strong>.
        </p>
      </div>
    <?php else: ?>
      <div class="attendance-grid">
        <?php foreach ($students as $st): ?>
          <?php 
            // Estado por defecto: Si ya está en BD usarlo; si no, 'presente'
            $currentStatus = $st['attendance_status'] ?: 'presente';
          ?>
          <div class="student-card" data-student-id="<?= $st['id'] ?>" data-status="<?= $currentStatus ?>">
            <div class="student-info-row">
              <div class="student-avatar">
                <img src="<?= htmlspecialchars($st['photo_url'] ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($st['full_name'])) ?>" alt="Foto">
              </div>
              <div class="student-details">
                <h4><?= htmlspecialchars($st['full_name']) ?></h4>
                <p>Doc: <?= htmlspecialchars($st['document_number']) ?></p>
              </div>
            </div>

            <!-- Botones Triad: Presente | Justificada | Ausente -->
            <div class="triad-group">
              <button type="button" 
                      class="btn-status <?= $currentStatus === 'presente' ? 'active-presente' : '' ?>"
                      onclick="setStatus(this, 'presente')">
                ✅ Presente
              </button>
              <button type="button" 
                      class="btn-status <?= $currentStatus === 'justificada' ? 'active-justificada' : '' ?>"
                      onclick="setStatus(this, 'justificada')">
                🟡 Justificada
              </button>
              <button type="button" 
                      class="btn-status <?= $currentStatus === 'ausente' ? 'active-ausente' : '' ?>"
                      onclick="setStatus(this, 'ausente')">
                ❌ Ausente
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Barra de Guardado Flotante -->
      <div style="position:sticky; bottom:1.5rem; margin-top:2rem; background:#ffffff; border:2px solid var(--wine-border); box-shadow:var(--shadow-xl); border-radius:var(--radius-lg); padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; z-index:30;">
        <div>
          <p style="font-weight:700; color:var(--navy); font-size:0.95rem;">
            Control de Asistencia del Día
          </p>
          <p style="font-size:0.8rem; color:var(--text-muted);">
            Guarda los cambios para sincronizar la base de datos institucional y la auditoría.
          </p>
        </div>

        <button type="button" id="btnSaveAttendance" class="btn btn-wine btn-lg" onclick="saveAttendance()">
          💾 Guardar Asistencia de Hoy
        </button>
      </div>
    <?php endif; ?>

  </div>
</main>

<script>
function setStatus(btn, status) {
  const card = btn.closest('.student-card');
  card.setAttribute('data-status', status);

  const buttons = card.querySelectorAll('.btn-status');
  buttons.forEach(b => {
    b.classList.remove('active-presente', 'active-justificada', 'active-ausente');
  });

  if (status === 'presente') btn.classList.add('active-presente');
  if (status === 'justificada') btn.classList.add('active-justificada');
  if (status === 'ausente') btn.classList.add('active-ausente');

  updateCounters();
}

function updateCounters() {
  const cards = document.querySelectorAll('.student-card');
  let pres = 0, just = 0, ause = 0;

  cards.forEach(c => {
    const s = c.getAttribute('data-status');
    if (s === 'presente') pres++;
    else if (s === 'justificada') just++;
    else if (s === 'ausente') ause++;
  });

  const cPres = document.getElementById('counterPresentes');
  const cJust = document.getElementById('counterJustificadas');
  const cAuse = document.getElementById('counterAusentes');

  if (cPres) cPres.textContent = `Presentes: ${pres}`;
  if (cJust) cJust.textContent = `Justificadas: ${just}`;
  if (cAuse) cAuse.textContent = `Ausentes: ${ause}`;
}

function marcarTodosPresentes() {
  document.querySelectorAll('.student-card').forEach(c => {
    const btnPres = c.querySelector('.btn-status:first-child');
    if (btnPres) setStatus(btnPres, 'presente');
  });
  API.toast('Se han marcado todos los alumnos como Presente.', 'info');
}

async function saveAttendance() {
  const btn = document.getElementById('btnSaveAttendance');
  btn.disabled = true;
  btn.textContent = 'Guardando asistencia...';

  const cards = document.querySelectorAll('.student-card');
  const records = [];

  cards.forEach(c => {
    records.push({
      student_id: c.getAttribute('data-student-id'),
      status: c.getAttribute('data-status') || 'presente'
    });
  });

  const payload = {
    date: '<?= $selDate ?>',
    records: records
  };

  const res = await API.post('<?= BASE_URL ?>/api/attendance.php?action=save_attendance', payload);
  btn.disabled = false;
  btn.textContent = '💾 Guardar Asistencia de Hoy';

  if (res.success) {
    API.toast(res.message, 'success');
  } else {
    API.toast(res.message || 'Error al guardar.', 'error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateCounters();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
