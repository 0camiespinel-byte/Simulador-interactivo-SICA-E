<?php
/**
 * SICA-E · Portal de Visitantes (Kiosco de Acceso)
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Portal de Visitantes';
$activePortal = 'visitantes';

require_once __DIR__ . '/../includes/header.php';
?>

<main style="flex:1; padding: 2.5rem 1rem;">
  <div class="container container-sm">
    
    <div class="portal-hero">
      <div class="portal-hero-badge">
        <span>📍 Punto de Control de Entrada</span>
      </div>
      <h2>Bienvenido al Portal de Visitantes</h2>
      <p>
        Para garantizar la seguridad de nuestros estudiantes y docentes, por favor selecciona una opción para registrar tu entrada a la Institución Educativa Brighton Pamplona.
      </p>
    </div>

    <div class="visitor-choice-grid">
      <!-- Opción 1: Primer Registro -->
      <a href="<?= BASE_URL ?>/portal-visitantes/nuevo.php" class="visitor-choice-card primary">
        <div class="visitor-choice-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
        </div>
        <h3>Primer Registro</h3>
        <p>
          Es mi primera visita a la institución o deseo actualizar mis datos y fotografía biométrica.
        </p>
        <span class="btn btn-wine btn-lg" style="width:100%; margin-top:auto;">
          Registrarme por Primera Vez →
        </span>
      </a>

      <!-- Opción 2: Ya estoy Registrado -->
      <a href="<?= BASE_URL ?>/portal-visitantes/recurrente.php" class="visitor-choice-card">
        <div class="visitor-choice-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <h3>Ya estoy Registrado</h3>
        <p>
          Ya he visitado el colegio anteriormente. Solo digita tu documento para confirmar tu ingreso en 1 paso.
        </p>
        <span class="btn btn-navy btn-lg" style="width:100%; margin-top:auto;">
          Acceso Rápido por Documento →
        </span>
      </a>
    </div>

    <div style="text-align:center; margin-top:2.5rem;">
      <a href="<?= BASE_URL ?>/" style="font-size:0.88rem; color:var(--text-muted); font-weight:600;">
        ← Regresar a la página principal
      </a>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
