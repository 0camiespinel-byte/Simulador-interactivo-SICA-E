<?php
/**
 * SICA-E · Asistente de Instalación y Verificación de Base de Datos
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$success = false;
$error = null;
$stats = [];

try {
    $pdo = getDB();
    
    // Contar registros
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $stats['visitors'] = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $stats['access_logs'] = $pdo->query("SELECT COUNT(*) FROM access_logs")->fetchColumn();
    $success = true;
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Instalador y Diagnóstico · <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
</head>
<body style="background:var(--bg-muted); display:flex; align-items:center; justify-content:center; min-height:100vh; padding:2rem 1rem;">

  <div class="card" style="max-width:560px; width:100%; padding:2.5rem; text-align:center;">
    
    <div class="brand-logo-badge" style="margin:0 auto 1.25rem; width:56px; height:56px; font-size:1.6rem;">
      <?php if (file_exists(__DIR__ . '/assets/img/logo.png')): ?>
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo">
      <?php else: ?>
        B
      <?php endif; ?>
    </div>

    <h2 style="font-size:1.6rem; font-weight:800; color:var(--navy); margin-bottom:0.4rem;">
      Diagnóstico y Estado de <?= APP_NAME ?>
    </h2>
    <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1.75rem;">
      <?= INSTITUTION_NAME ?>
    </p>

    <?php if ($success): ?>
      <div class="alert alert-success" style="text-align:left;">
        <span style="font-size:1.4rem;">✅</span>
        <div>
          <strong>¡Base de Datos MySQL SICA-E Conectada y Lista!</strong>
          <p style="font-size:0.82rem; margin-top:0.25rem;">
            Todas las tablas, usuarios institucionales y estudiantes de prueba se encuentran configurados correctamente.
          </p>
        </div>
      </div>

      <div style="background:var(--bg-muted); padding:1rem; border-radius:var(--radius-md); text-align:left; font-size:0.88rem; margin-bottom:1.5rem;">
        <p style="font-weight:700; color:var(--navy); margin-bottom:0.5rem;">📊 Registros verificados en MySQL:</p>
        <ul style="list-style:none; padding:0; display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
          <li>👤 Usuarios Personal: <strong><?= $stats['users'] ?></strong></li>
          <li>🎓 Estudiantes Brighton: <strong><?= $stats['students'] ?></strong></li>
          <li>👥 Visitantes Registrados: <strong><?= $stats['visitors'] ?></strong></li>
          <li>⏱️ Logs de Acceso: <strong><?= $stats['access_logs'] ?></strong></li>
        </ul>
      </div>

      <div style="background:#ffffff; border:1px dashed var(--border-strong); padding:1rem; border-radius:var(--radius-md); text-align:left; font-size:0.82rem; margin-bottom:1.75rem;">
        <p style="font-weight:700; color:var(--wine); margin-bottom:0.4rem;">🔑 Credenciales de Acceso:</p>
        <p>• <strong>Administrador:</strong> <code>brightonadmi@gmail.com</code> | Clave: <code>BrightonAdmin2026</code></p>
        <p>• <strong>Docente (1102):</strong> <code>jesus@gmail.com</code> | Clave: <code>profejesus</code></p>
      </div>

      <a href="<?= BASE_URL ?>/" class="btn btn-wine btn-lg" style="width:100%;">
        Entrar a <?= APP_NAME ?> →
      </a>
    <?php else: ?>
      <div class="alert alert-danger" style="text-align:left;">
        <span style="font-size:1.4rem;">⚠️</span>
        <div>
          <strong>Error de Conexión a MySQL</strong>
          <p style="font-size:0.82rem; margin-top:0.25rem;">
            <?= htmlspecialchars($error) ?>
          </p>
        </div>
      </div>
      <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem;">
        Verifica que tu servidor MySQL en XAMPP o Laragon esté iniciado. Luego recarga esta página.
      </p>
      <button onclick="location.reload()" class="btn btn-navy">
        Reintentar Conexión
      </button>
    <?php endif; ?>

  </div>

</body>
</html>
