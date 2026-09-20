<?php
/**
 * SICA-E · Lista de Estudiantes a Cargo del Docente
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('profesor');
$currentUser = Auth::user();
$pageTitle = 'Estudiantes Asignados';
$activePortal = 'docentes';

$db = getDB();

$myGrade  = $currentUser['titular_grade'] ?? 'Undécimo';
$myCourse = $currentUser['titular_course'] ?? '02';

$stmt = $db->prepare("
    SELECT s.*, 
           COUNT(CASE WHEN a.status = 'ausente' THEN 1 END) as total_ausente,
           COUNT(CASE WHEN a.status = 'justificada' THEN 1 END) as total_justificada,
           COUNT(CASE WHEN a.status = 'presente' THEN 1 END) as total_presente
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id
    WHERE s.grade = ? AND s.course = ?
    GROUP BY s.id
    ORDER BY s.full_name ASC
");
$stmt->execute([$myGrade, $myCourse]);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-docentes/" class="nav-tab-item">
    🏠 Inicio Docente
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/asistencia.php" class="nav-tab-item">
    📝 Toma de Asistencia
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/estudiantes.php" class="nav-tab-item active">
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

    <div class="quick-toolbar">
      <div>
        <h2 style="font-size:1.5rem; font-weight:800; color:var(--navy);">
          Alumnos Matriculados en <?= htmlspecialchars($myGrade . ' - ' . $myCourse) ?>
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Total en tu grupo titular: <strong><?= count($students) ?> estudiantes</strong>
        </p>
      </div>

      <a href="<?= BASE_URL ?>/portal-docentes/asistencia.php" class="btn btn-wine btn-sm">
        📝 Ir a Llamar a Lista
      </a>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Nombre del Alumno</th>
              <th>Documento</th>
              <th>Asistencias</th>
              <th>Justificadas</th>
              <th>Inasistencias</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">
                  No hay estudiantes asignados a tu grupo titular.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $st): ?>
                <tr>
                  <td>
                    <img src="<?= htmlspecialchars($st['photo_url'] ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($st['full_name'])) ?>" 
                         alt="Foto" 
                         style="width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border);">
                  </td>
                  <td><strong><?= htmlspecialchars($st['full_name']) ?></strong></td>
                  <td><code><?= htmlspecialchars($st['document_number']) ?></code></td>
                  <td><span class="badge badge-success"><?= $st['total_presente'] ?></span></td>
                  <td><span class="badge badge-warning"><?= $st['total_justificada'] ?></span></td>
                  <td>
                    <span class="badge <?= $st['total_ausente'] > 2 ? 'badge-danger' : 'badge-navy' ?>">
                      <?= $st['total_ausente'] ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/portal-docentes/excusas.php?student_id=<?= $st['id'] ?>" class="btn btn-sm btn-outline">
                      + Radicar Excusa
                    </a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
