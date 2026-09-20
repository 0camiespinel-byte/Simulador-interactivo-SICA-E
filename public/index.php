<?php
/**
 * SICA-E · Página Principal / Landing Institucional
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Inicio';
$activePortal = 'inicio';

// Consultar métricas rápidas de la base de datos
$db = getDB();
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 16;
$totalVisitorsToday = $db->query("SELECT COUNT(*) FROM access_logs WHERE DATE(entrada) = CURRENT_DATE()")->fetchColumn() ?: 4;
$activeNow = $db->query("SELECT COUNT(*) FROM access_logs WHERE DATE(entrada) = CURRENT_DATE() AND salida IS NULL")->fetchColumn() ?: 3;

require_once __DIR__ . '/includes/header.php';
?>

<main style="flex:1;">
  <!-- Hero Section -->
  <section style="background: linear-gradient(180deg, #ffffff 0%, var(--bg-main) 100%); padding: 3.5rem 1rem 2.5rem; text-align: center; border-bottom: 1px solid var(--border);">
    <div class="container container-sm">
      <div style="display:inline-flex; align-items:center; gap:0.5rem; background:var(--wine-light); color:var(--wine); padding:0.4rem 1rem; border-radius:var(--radius-full); font-size:0.85rem; font-weight:700; margin-bottom:1.25rem;">
        <span>🛡️ SICA-E 2.0</span>
        <span>·</span>
        <span><?= INSTITUTION_NAME ?></span>
      </div>

      <h1 style="font-size: clamp(2rem, 5vw, 2.75rem); font-weight:800; color:var(--navy); line-height:1.2; margin-bottom:1rem;">
        Seguridad institucional <br><span style="color:var(--wine);">en tiempo real</span>
      </h1>

      <p style="font-size:1.05rem; color:var(--text-muted); line-height:1.6; margin-bottom:1.5rem;">
        Sistema Integral de Control de Acceso Escolar, registro biométrico de visitantes, toma interactiva de asistencia y reportes para la comunidad educativa Brighton Pamplona.
      </p>

      <div style="display:inline-block; background:#ffffff; border:1px solid var(--border); padding:0.4rem 1rem; border-radius:var(--radius-md); font-size:0.82rem; color:var(--navy); font-weight:600; box-shadow:var(--shadow-sm); margin-bottom:2rem;">
        Diseñado y desarrollado por: <span style="color:var(--wine);"><?= AUTHOR_NAME ?></span>
      </div>

      <!-- Métricas rápidas -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:1rem; max-width:600px; margin:0 auto;">
        <div class="card" style="padding:1rem; text-align:center;">
          <p style="font-size:1.6rem; font-weight:800; color:var(--wine);"><?= $totalVisitorsToday ?></p>
          <p style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Visitas Hoy</p>
        </div>
        <div class="card" style="padding:1rem; text-align:center;">
          <p style="font-size:1.6rem; font-weight:800; color:var(--success);"><?= $activeNow ?></p>
          <p style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">En la Institución</p>
        </div>
        <div class="card" style="padding:1rem; text-align:center;">
          <p style="font-size:1.6rem; font-weight:800; color:var(--navy);"><?= $totalStudents ?></p>
          <p style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Estudiantes Activos</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Selección de los 3 Portales -->
  <section style="padding: 3.5rem 1rem;">
    <div class="container">
      <div style="text-align:center; margin-bottom:2.5rem;">
        <h2 style="font-size:1.75rem; font-weight:800; color:var(--navy); margin-bottom:0.4rem;">
          Acceso a los Portales Institucionales
        </h2>
        <p style="color:var(--text-muted); font-size:0.95rem;">
          Selecciona tu rol para ingresar al módulo correspondiente
        </p>
      </div>

      <div class="visitor-choice-grid" style="grid-template-columns:repeat(auto-fit, minmax(310px, 1fr));">
        
        <!-- Portal 1: Visitantes -->
        <div class="visitor-choice-card primary">
          <div class="visitor-choice-icon">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <h3>Portal Visitantes</h3>
          <p>
            Registra tu ingreso a la institución de forma rápida y segura. Opciones para nuevos visitantes y acceso rápido para recurrentes.
          </p>
          <a href="<?= BASE_URL ?>/portal-visitantes/" class="btn btn-wine btn-lg" style="width:100%; margin-top:auto;">
            Soy Visitante →
          </a>
        </div>

        <!-- Portal 2: Administrativos -->
        <div class="visitor-choice-card">
          <div class="visitor-choice-icon">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M9 3v18"></path><path d="m14 9 3 3-3 3"></path></svg>
          </div>
          <h3>Portal Administrativo</h3>
          <p>
            Gestión completa de usuarios, visitantes, registro de accesos en tiempo real, estadísticas gráficas y reportes oficiales.
          </p>
          <a href="<?= BASE_URL ?>/login.php?role=admin" class="btn btn-navy btn-lg" style="width:100%; margin-top:auto;">
            Ingreso Administrativo →
          </a>
        </div>

        <!-- Portal 3: Docentes -->
        <div class="visitor-choice-card">
          <div class="visitor-choice-icon">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path></svg>
          </div>
          <h3>Portal Docentes</h3>
          <p>
            Toma de asistencia con 3 estados (Presente, Justificada, Ausente), gestión de salones asignados, excusas y seguimiento de alumnos.
          </p>
          <a href="<?= BASE_URL ?>/login.php?role=profesor" class="btn btn-navy btn-lg" style="width:100%; margin-top:auto;">
            Ingreso Docentes →
          </a>
        </div>

      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
