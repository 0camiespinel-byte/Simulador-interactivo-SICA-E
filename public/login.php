<?php
/**
 * SICA-E · Inicio de Sesión Unificado (Personal Administrativo y Docente)
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Si ya está autenticado, redirigir
if (Auth::check()) {
    $u = Auth::user();
    if ($u['role'] === 'admin') {
        header("Location: " . BASE_URL . "/portal-admin/");
    } else {
        header("Location: " . BASE_URL . "/portal-docentes/");
    }
    exit;
}

$error = '';
$prefillEmail = '';
$roleHint = $_GET['role'] ?? '';

if ($roleHint === 'admin') {
    $prefillEmail = 'brightonadmi@gmail.com';
} elseif ($roleHint === 'profesor' || $roleHint === 'docente') {
    $prefillEmail = 'jesus@gmail.com';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $res = Auth::attempt($email, $password);
    if ($res['success']) {
        $redir = $_SESSION['redirect_after_login'] ?? $res['redirect'];
        unset($_SESSION['redirect_after_login']);
        header("Location: " . $redir);
        exit;
    } else {
        $error = $res['message'];
    }
}

$pageTitle = 'Acceso Institucional';
require_once __DIR__ . '/includes/header.php';
?>

<main style="flex:1; display:flex; align-items:center; justify-content:center; padding:3rem 1rem;">
  <div class="card" style="max-width:440px; width:100%; padding:2.25rem;">
    
    <div style="text-align:center; margin-bottom:2rem;">
      <div class="brand-logo-badge" style="margin:0 auto 1rem; width:52px; height:52px; font-size:1.5rem;">
        <?php if (file_exists(ROOT_PATH . '/assets/img/logo.png')): ?>
          <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo">
        <?php else: ?>
          B
        <?php endif; ?>
      </div>
      <h2 style="font-size:1.45rem; font-weight:800; color:var(--navy); margin-bottom:0.25rem;">
        Acceso al Sistema
      </h2>
      <p style="font-size:0.85rem; color:var(--text-muted);">
        Personal Directivo, Administrativo y Docente
      </p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="email">Correo Institucional <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-input" required 
               placeholder="usuario@brighton.edu.co" 
               value="<?= htmlspecialchars($_POST['email'] ?? $prefillEmail) ?>" autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña <span class="required">*</span></label>
        <input type="password" id="password" name="password" class="form-input" required 
               placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-navy btn-lg" style="width:100%; margin-top:0.75rem;">
        Iniciar Sesión Segura
      </button>
    </form>

    <!-- Credenciales rápidas de demostración -->
    <div style="margin-top:2rem; padding:1rem; background:var(--bg-muted); border-radius:var(--radius-md); font-size:0.78rem; border:1px dashed var(--border-strong);">
      <p style="font-weight:700; color:var(--navy); margin-bottom:0.4rem;">🔑 Cuentas de Acceso Rápido:</p>
      <div style="margin-bottom:0.4rem;">
        <span class="badge badge-wine">Admin</span>
        <strong>brightonadmi@gmail.com</strong> / <code>BrightonAdmin2026</code>
      </div>
      <div>
        <span class="badge badge-navy">Docente</span>
        <strong>jesus@gmail.com</strong> / <code>profejesus</code>
      </div>
    </div>

    <div style="text-align:center; margin-top:1.5rem;">
      <a href="<?= BASE_URL ?>/" style="font-size:0.85rem; color:var(--text-muted); font-weight:500;">
        ← Volver al inicio institucional
      </a>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
