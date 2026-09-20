<?php
/**
 * SICA-E · API de Gestión de Usuarios y Personal
 * Institución Educativa Brighton Pamplona
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('admin');
$currentUser = Auth::user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$jsonInput = json_decode(file_get_contents('php://input'), true);
if (is_array($jsonInput)) {
    if (empty($action) && isset($jsonInput['action'])) {
        $action = $jsonInput['action'];
    }
    $_POST = array_merge($_POST, $jsonInput);
}

$db = getDB();

try {
    switch ($action) {
        // ==========================================================
        // 1. CREAR NUEVO USUARIO (ADMIN O DOCENTE)
        // ==========================================================
        case 'create_user':
            $fullName = trim($_POST['full_name'] ?? '');
            $email    = trim(strtolower($_POST['email'] ?? ''));
            $password = trim($_POST['password'] ?? '');
            $role     = trim($_POST['role'] ?? 'profesor');
            $grade    = trim($_POST['titular_grade'] ?? '');
            $course   = trim($_POST['titular_course'] ?? '');
            $section  = trim($_POST['titular_section'] ?? 'secundaria');
            $phone    = trim($_POST['phone'] ?? '');

            if (empty($fullName) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Nombre, correo y contraseña son obligatorios.']);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
                exit;
            }

            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
                exit;
            }

            // Verificar si el correo ya existe
            $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este correo ya está registrado en el sistema.']);
                exit;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $photo = 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($fullName) . '&backgroundColor=b6e3f4,ffd5dc';

            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, password_hash, role, titular_section, titular_grade, titular_course, phone, photo_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')
            ");
            $stmt->execute([
                $fullName,
                $email,
                $hash,
                $role,
                $role === 'profesor' ? ($section ?: 'secundaria') : null,
                $role === 'profesor' ? ($grade ?: 'Undécimo') : null,
                $role === 'profesor' ? ($course ?: '02') : null,
                $phone ?: null,
                $photo
            ]);

            echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente.']);
            exit;

        // ==========================================================
        // 2. CAMBIAR CONTRASEÑA DE UN USUARIO
        // ==========================================================
        case 'reset_password':
            $userId = intval($_POST['user_id'] ?? 0);
            $newPw  = trim($_POST['new_password'] ?? '');

            if ($userId <= 0 || strlen($newPw) < 6) {
                echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
                exit;
            }

            $hash = password_hash($newPw, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);

            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
            exit;

        // ==========================================================
        // 3. ALTERNAR ESTADO ACTIVO / INACTIVO
        // ==========================================================
        case 'toggle_status':
            $userId = intval($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario inválido.']);
                exit;
            }

            if ($userId === intval($currentUser['id'])) {
                echo json_encode(['success' => false, 'message' => 'No puedes desactivar tu propia cuenta actual.']);
                exit;
            }

            $get = $db->prepare("SELECT status FROM users WHERE id = ?");
            $get->execute([$userId]);
            $u = $get->fetch();
            if (!$u) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
                exit;
            }

            $newStatus = $u['status'] === 'activo' ? 'inactivo' : 'activo';
            $up = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $up->execute([$newStatus, $userId]);

            echo json_encode(['success' => true, 'message' => "Estado cambiado a {$newStatus}.", 'new_status' => $newStatus]);
            exit;

        // ==========================================================
        // 4. ELIMINAR USUARIO
        // ==========================================================
        case 'delete_user':
            $userId = intval($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID inválido.']);
                exit;
            }

            if ($userId === intval($currentUser['id'])) {
                echo json_encode(['success' => false, 'message' => 'No puedes eliminarte a ti mismo.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
