<?php
/**
 * SICA-E · Módulo de Autenticación y Control de Acceso por Roles
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    /**
     * Verifica si el usuario ha iniciado sesión
     */
    public static function check(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtiene el usuario autenticado actualmente
     */
    public static function user(): ?array {
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? '',
            'titular_section' => $_SESSION['titular_section'] ?? null,
            'titular_grade' => $_SESSION['titular_grade'] ?? null,
            'titular_course' => $_SESSION['titular_course'] ?? null,
            'phone' => $_SESSION['user_phone'] ?? null,
            'photo_url' => $_SESSION['user_photo'] ?? null,
        ];
    }

    /**
     * Intenta autenticar un usuario con email y contraseña
     */
    public static function attempt(string $email, string $password): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'activo' LIMIT 1");
        $stmt->execute([trim(strtolower($email))]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Credenciales inválidas o cuenta inactiva.'];
        }

        // Validación de contraseña:
        // Se admite password_verify estándar y un fallback directo para los usuarios semilla
        $valid = password_verify($password, $user['password_hash']);
        if (!$valid) {
            // Verificación para credenciales por defecto conocidas
            if (($user['email'] === 'brightonadmi@gmail.com' && $password === 'BrightonAdmin2026') ||
                ($user['email'] === 'jesus@gmail.com' && $password === 'profejesus')) {
                $valid = true;
                // Actualizar a hash nuevo
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $up = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $up->execute([$newHash, $user['id']]);
            }
        }

        if (!$valid) {
            return ['success' => false, 'message' => 'Contraseña incorrecta.'];
        }

        // Guardar sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['titular_section'] = $user['titular_section'];
        $_SESSION['titular_grade'] = $user['titular_grade'];
        $_SESSION['titular_course'] = $user['titular_course'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_photo'] = $user['photo_url'];

        return [
            'success' => true,
            'user' => $user,
            'redirect' => $user['role'] === 'admin' ? BASE_URL . '/portal-admin/' : BASE_URL . '/portal-docentes/'
        ];
    }

    /**
     * Cierra la sesión
     */
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Exige que el usuario esté autenticado
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/');
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }
    }

    /**
     * Exige un rol específico (admin o profesor)
     */
    public static function requireRole(string $requiredRole): void {
        self::requireAuth();
        $user = self::user();
        if ($user['role'] !== $requiredRole) {
            // Si es admin intentando entrar a docente o viceversa, redireccionar con aviso
            if ($user['role'] === 'admin') {
                header("Location: " . BASE_URL . "/portal-admin/");
            } else {
                header("Location: " . BASE_URL . "/portal-docentes/");
            }
            exit;
        }
    }
}
