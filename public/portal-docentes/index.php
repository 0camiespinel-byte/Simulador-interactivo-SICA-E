<?php
/**
 * SICA-E · Portal Docente Principal
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('profesor');
$currentUser = Auth::user();
$pageTitle = 'Portal Docente';
$activePortal = 'docentes';

$db = getDB();

$myGrade  = $currentUser['titular_grade'] ?? 'Undécimo';
$myCourse = $currentUser['titular_course'] ?? '02';
$today    = date('Y-m-d');

// Estudiantes a cargo
$stmtSt = $db->prepare("SELECT COUNT(*) FROM students WHERE grade = ? AND course = ?");
$stmtSt->execute([$myGrade, $myCourse]);
$myStudentsCount = $stmtSt->fetchColumn() ?: 0;

// Estado de asistencia de hoy
$stmtAtt = $db->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'presente' THEN 1 END) as presentes,
        COUNT(CASE WHEN status = 'justificada' THEN 1 END) as justificadas,
        COUNT(CASE WHEN status = 'ausente' THEN 1 END) as ausentes
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    WHERE s.grade = ? AND s.course = ? AND a.date = ?
");
$stmtAtt->execute([$myGrade, $myCourse, $today]);
$todayStats = $stmtAtt->fetch();
$totalMarked = ($todayStats['presentes'] ?? 0) + ($todayStats['justificadas'] ?? 0) + ($todayStats['ausentes'] ?? 0);

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-docentes/" class="nav-tab-item active">
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
  <a href="<?= BASE_URL ?>/portal-docentes/reportes.php" class="nav-tab-item">
    📊 Reportes de Asistencia
  </a>
</nav>

<main style="flex:1; padding: 2rem 1rem;">
  <div class="container container-lg">

    <!-- Tarjeta de Bienvenida del Docente -->
    <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, var(--navy-light) 100%); border-color: var(--navy-border);">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem;">
        <div style="display:flex; align-items:center; gap:1.25rem;">
          <img src="<?= htmlspecialchars($currentUser['photo_url'] ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($currentUser['full_name'])) ?>" 
               alt="Docente" 
               style="width:68px; height:68px; border-radius:var(--radius-md); object-fit:cover; border:2px solid #ffffff; box-shadow:var(--shadow-md);">
          <div>
            <h2 style="font-size:1.45rem; font-weight:800; color:var(--navy); line-height:1.2;">
              ¡Bienvenido, <?= htmlspecialchars($currentUser['full_name']) ?>!
            </h2>
            <p style="font-size:0.88rem; color:var(--text-muted); margin-top:0.25rem;">
              Docente Titular Asignado: <strong style="color:var(--wine);"><?= htmlspecialchars($myGrade) ?> - <?= htmlspecialchars($myCourse) ?></strong>
            </p>
          </div>
        </div>

        <a href="<?= BASE_URL ?>/portal-docentes/asistencia.php" class="btn btn-wine btn-lg">
          📝 Tomar Asistencia de Hoy →
        </a>
      </div>
    </div>

    <!-- Indicadores Rápidos -->
    <div class="grid-3" style="margin-bottom: 2rem;">
      <div class="stat-card">
        <div class="stat-icon navy">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
          <p class="stat-value"><?= $myStudentsCount ?></p>
          <p class="stat-label">Alumnos en <?= htmlspecialchars($myGrade . ' ' . $myCourse) ?></p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon success">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="stat-info">
          <p class="stat-value"><?= $todayStats['presentes'] ?? 0 ?></p>
          <p class="stat-label">Asistieron Hoy (<?= date('d/m/Y') ?>)</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon gold">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="stat-info">
          <p class="stat-value"><?= ($todayStats['inasistencias'] ?? 0) + ($todayStats['ausentes'] ?? 0) ?></p>
          <p class="stat-label">Inasistencias Registradas Hoy</p>
        </div>
      </div>
    </div>

    <!-- Módulos Principales de Gestión Escolar -->
    <div class="visitor-choice-grid">
      <div class="visitor-choice-card">
        <div class="visitor-choice-icon" style="background:var(--wine-light); color:var(--wine);">
          📝
        </div>
        <h3>Toma de Asistencia Interactiva</h3>
        <p>
          Control diario con 3 estados: Presente (✅), Justificada (🟡) y Ausente (❌). Soporta edición y registro de auditoría.
        </p>
        <a href="<?= BASE_URL ?>/portal-docentes/asistencia.php" class="btn btn-wine" style="width:100%; margin-top:auto;">
          Llamar a Lista
        </a>
      </div>

      <div class="visitor-choice-card">
        <div class="visitor-choice-icon" style="background:var(--navy-light); color:var(--navy);">
          👥
        </div>
        <h3>Listado y Fichas de Estudiantes</h3>
        <p>
          Consulta los datos personales, documentos de identidad y fotografías de cada alumno matriculado en tu curso.
        </p>
        <a href="<?= BASE_URL ?>/portal-docentes/estudiantes.php" class="btn btn-navy" style="width:100%; margin-top:auto;">
          Ver Estudiantes
        </a>
      </div>

      <div class="visitor-choice-card">
        <div class="visitor-choice-icon" style="background:var(--gold-light); color:var(--gold);">
          📑
        </div>
        <h3>Excusas y Justificantes</h3>
        <p>
          Radicación y aprobación de incapacidades médicas o permisos familiares presentados por los acudientes.
        </p>
        <a href="<?= BASE_URL ?>/portal-docentes/excusas.php" class="btn btn-outline" style="width:100%; margin-top:auto;">
          Gestionar Excusas
        </a>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
