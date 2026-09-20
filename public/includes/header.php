<?php
/**
 * SICA-E · Encabezado Institucional Común
 * Institución Educativa Brighton Pamplona
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = $pageTitle ?? APP_FULL_NAME;
$activePortal = $activePortal ?? '';
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> · <?= APP_NAME ?></title>
  
  <!-- Favicon / Brand -->
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">

  <!-- Tipografía Google Fonts Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Hojas de Estilo CSS de SICA-E -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/portals.css">

  <!-- Chart.js para gráficos -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a href="<?= BASE_URL ?>/" class="brand-link">
      <div class="brand-logo-badge">
        <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
          <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo SICA-E">
        <?php else: ?>
          B
        <?php endif; ?>
      </div>
      <div class="brand-text">
        <h1><?= APP_NAME ?> · Control de Acceso</h1>
        <p><?= INSTITUTION_NAME ?></p>
      </div>
    </a>

    <div style="display:flex; align-items:center; gap:0.85rem;">
      <?php if ($currentUser): ?>
        <div style="text-align:right; display:none; @media(min-width:640px){display:block;}">
          <p style="font-size:0.85rem; font-weight:700; color:var(--navy); line-height:1.2;">
            <?= htmlspecialchars($currentUser['full_name']) ?>
          </p>
          <p style="font-size:0.75rem; color:var(--text-muted); text-transform:capitalize;">
            <?= htmlspecialchars($currentUser['role']) ?>
            <?php if ($currentUser['role'] === 'profesor' && $currentUser['titular_grade']): ?>
              · <?= htmlspecialchars($currentUser['titular_grade'] . ' ' . $currentUser['titular_course']) ?>
            <?php endif; ?>
          </p>
        </div>

        <?php if ($currentUser['role'] === 'admin'): ?>
          <a href="<?= BASE_URL ?>/portal-admin/" class="btn btn-sm btn-outline <?= $activePortal === 'admin' ? 'btn-wine' : '' ?>">
            Panel Admin
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/portal-docentes/" class="btn btn-sm btn-outline <?= $activePortal === 'docentes' ? 'btn-navy' : '' ?>">
            Portal Docente
          </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline" title="Cerrar Sesión">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Salir
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/portal-visitantes/" class="btn btn-sm btn-outline <?= $activePortal === 'visitantes' ? 'btn-wine' : '' ?>">
          Soy Visitante
        </a>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-navy">
          Acceso Personal →
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
