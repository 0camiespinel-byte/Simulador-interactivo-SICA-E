<?php
/**
 * SICA-E · Reportes y Estadísticas de Asistencia Escolar
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('profesor');
$currentUser = Auth::user();
$pageTitle = 'Reportes de Asistencia';
$activePortal = 'docentes';

$db = getDB();

$myGrade  = $currentUser['titular_grade'] ?? 'Undécimo';
$myCourse = $currentUser['titular_course'] ?? '02';

$start = trim($_GET['start'] ?? date('Y-m-01'));
$end   = trim($_GET['end'] ?? date('Y-m-d'));

$stmt = $db->prepare("
    SELECT s.id, s.full_name, s.document_number,
           COUNT(CASE WHEN a.status = 'presente' THEN 1 END) as presentes,
           COUNT(CASE WHEN a.status = 'justificada' THEN 1 END) as justificadas,
           COUNT(CASE WHEN a.status = 'ausente' THEN 1 END) as ausentes,
           COUNT(a.id) as total_llamados
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id AND a.date BETWEEN ? AND ?
    WHERE s.grade = ? AND s.course = ?
    GROUP BY s.id
    ORDER BY s.full_name ASC
");
$stmt->execute([$start, $end, $myGrade, $myCourse]);
$reportStudents = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
  .site-header, .nav-tabs, .quick-toolbar, .site-footer, .no-print {
    display: none !important;
  }
  body { background: #ffffff !important; font-size: 11pt; }
  .card { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>

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
  <a href="<?= BASE_URL ?>/portal-docentes/excusas.php" class="nav-tab-item">
    📑 Excusas y Justificaciones
  </a>
  <a href="<?= BASE_URL ?>/portal-docentes/reportes.php" class="nav-tab-item active">
    📊 Reportes de Asistencia
  </a>
</nav>

<main style="flex:1; padding: 2rem 1rem;">
  <div class="container container-lg">

    <div class="quick-toolbar no-print">
      <div>
        <h2 style="font-size:1.5rem; font-weight:800; color:var(--navy);">
          Consolidado de Asistencia · <?= htmlspecialchars($myGrade . ' - ' . $myCourse) ?>
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Periodo del <?= date('d/m/Y', strtotime($start)) ?> al <?= date('d/m/Y', strtotime($end)) ?>
        </p>
      </div>

      <button type="button" class="btn btn-navy btn-sm" onclick="window.print()">
        🖨️ Imprimir Consolidado
      </button>
    </div>

    <!-- Filtro -->
    <div class="card no-print" style="margin-bottom:1.5rem; padding:1.25rem;">
      <form method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex:1; min-width:160px;">
          <label class="form-label">Fecha Desde</label>
          <input type="date" name="start" class="form-input" value="<?= htmlspecialchars($start) ?>">
        </div>
        <div style="flex:1; min-width:160px;">
          <label class="form-label">Fecha Hasta</label>
          <input type="date" name="end" class="form-input" value="<?= htmlspecialchars($end) ?>">
        </div>
        <button type="submit" class="btn btn-wine">Filtrar Periodo</button>
      </form>
    </div>

    <!-- Informe -->
    <div class="card" style="padding:2rem;">
      <div style="border-bottom:2px solid var(--navy); padding-bottom:1rem; margin-bottom:1.5rem;">
        <h2 style="font-size:1.35rem; font-weight:800; color:var(--navy);"><?= INSTITUTION_NAME ?></h2>
        <p style="font-size:0.95rem; font-weight:600; color:var(--wine);">
          SICA-E · Control de Inasistencias y Justificaciones Escolar
        </p>
        <p style="font-size:0.82rem; color:var(--text-muted);">
          Docente Titular: <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong> · Grado: <strong><?= htmlspecialchars($myGrade . ' ' . $myCourse) ?></strong>
        </p>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Estudiante</th>
              <th>Documento</th>
              <th>Presentes</th>
              <th>Justificadas</th>
              <th>Inasistencias</th>
              <th>% Asistencia</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $i = 1;
              foreach ($reportStudents as $st): 
                $total = $st['total_llamados'];
                $pct = $total > 0 ? round(($st['presentes'] / $total) * 100) : 100;
            ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($st['full_name']) ?></strong></td>
                <td><code><?= htmlspecialchars($st['document_number']) ?></code></td>
                <td><span class="badge badge-success"><?= $st['presentes'] ?></span></td>
                <td><span class="badge badge-warning"><?= $st['justificadas'] ?></span></td>
                <td>
                  <span class="badge <?= $st['ausentes'] > 0 ? 'badge-danger' : 'badge-navy' ?>">
                    <?= $st['ausentes'] ?>
                  </span>
                </td>
                <td>
                  <strong><?= $pct ?>%</strong>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
