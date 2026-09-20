<?php
/**
 * SICA-E · Cierre de Sesión Seguro
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

Auth::logout();
header("Location: " . BASE_URL . "/login.php?logged_out=1");
exit;
