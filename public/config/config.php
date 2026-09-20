<?php
/**
 * SICA-E · Configuración General del Sistema
 * Institución Educativa Brighton Pamplona
 */

// Zona Horaria (Colombia)
date_default_timezone_set('America/Bogota');

// Constantes institucionales
define('APP_NAME', 'SICA-E');
define('APP_FULL_NAME', 'Sistema Inteligente de Control de Acceso Escolar');
define('INSTITUTION_NAME', 'Institución Educativa Brighton Pamplona');
define('AUTHOR_NAME', 'Yersson Eduardo Niño Gómez (1102)');
define('APP_VERSION', '2.0.0');

// Detección de URL base compatible con Vercel y subcarpetas locales.
// En Vercel, los encabezados X-Forwarded-* contienen la URL original.
$forwardedProto = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]);
$isHttps = $forwardedProto === 'https'
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
$protocol = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
$basePath = trim((string) (getenv('APP_BASE_PATH') ?: ''), '/');
$basePath = $basePath === '' ? '' : '/' . $basePath;

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', getenv('UPLOADS_PATH') ?: ROOT_PATH . '/uploads');

// Las sesiones locales funcionan en una instancia de Vercel, pero no son
// persistentes entre escalados. Para producción, configura un almacén externo.
$sessionPath = getenv('SESSION_SAVE_PATH');
if ($sessionPath && is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Motivos de visita oficiales
define('VISITOR_MOTIVOS', [
    'Reunión con docente',
    'Reunión con coordinación / rectoría',
    'Trámite administrativo / secretaría',
    'Entrega de documentos',
    'Recoger estudiante',
    'Proveedor / mantenimiento',
    'Prácticas pedagógicas / académicas',
    'Otro motivo institucional'
]);

// Grados y secciones oficiales Brighton
define('ACADEMIC_SECTIONS', [
    'primaria' => [
        'name' => 'Primaria',
        'grades' => ['Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto']
    ],
    'secundaria' => [
        'name' => 'Secundaria y Media',
        'grades' => ['Sexto', 'Séptimo', 'Octavo', 'Noveno', 'Décimo', 'Undécimo']
    ]
]);

define('ACADEMIC_COURSES', ['01', '02']);
