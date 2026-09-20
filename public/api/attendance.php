<?php
/**
 * SICA-E · API de Asistencia Escolar y Excusas
 * Institución Educativa Brighton Pamplona
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAuth();
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
        // 1. OBTENER ASISTENCIA DEL DÍA PARA UN CURSO
        // ==========================================================
        case 'get_course_attendance':
            $grade  = trim($_GET['grade'] ?? $_POST['grade'] ?? 'Undécimo');
            $course = trim($_GET['course'] ?? $_POST['course'] ?? '02');
            $date   = trim($_GET['date'] ?? $_POST['date'] ?? date('Y-m-d'));

            // Obtener estudiantes del curso
            $stmt = $db->prepare("
                SELECT s.id, s.full_name, s.document_number, s.grade, s.course, s.section, s.photo_url,
                       a.status AS attendance_status, a.id AS attendance_id, a.updated_at
                FROM students s
                LEFT JOIN attendance a ON a.student_id = s.id AND a.date = ?
                WHERE s.grade = ? AND s.course = ?
                ORDER BY s.full_name ASC
            ");
            $stmt->execute([$date, $grade, $course]);
            $students = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'date' => $date,
                'grade' => $grade,
                'course' => $course,
                'students' => $students
            ]);
            exit;

        // ==========================================================
        // 2. GUARDAR ASISTENCIA EN LOTE (CON AUDITORÍA DE 3 ESTADOS)
        // ==========================================================
        case 'save_attendance':
            $date = trim($_POST['date'] ?? date('Y-m-d'));
            $records = $_POST['records'] ?? [];

            if (!is_array($records) || empty($records)) {
                echo json_encode(['success' => false, 'message' => 'No se enviaron registros de asistencia.']);
                exit;
            }

            $db->beginTransaction();

            $checkStmt = $db->prepare("SELECT id, status FROM attendance WHERE student_id = ? AND date = ? LIMIT 1");
            $insertStmt = $db->prepare("INSERT INTO attendance (student_id, date, status, recorded_by) VALUES (?, ?, ?, ?)");
            $updateStmt = $db->prepare("UPDATE attendance SET status = ?, recorded_by = ? WHERE id = ?");
            $auditStmt = $db->prepare("INSERT INTO attendance_audit (attendance_id, student_id, date, old_status, new_status, changed_by) VALUES (?, ?, ?, ?, ?, ?)");

            $savedCount = 0;

            foreach ($records as $rec) {
                $studentId = intval($rec['student_id'] ?? 0);
                $newStatus = trim($rec['status'] ?? 'presente');

                if (!in_array($newStatus, ['presente', 'justificada', 'ausente'])) {
                    $newStatus = 'presente';
                }

                if ($studentId <= 0) continue;

                $checkStmt->execute([$studentId, $date]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    if ($existing['status'] !== $newStatus) {
                        // Guardar en auditoría
                        $auditStmt->execute([$existing['id'], $studentId, $date, $existing['status'], $newStatus, $currentUser['id']]);
                        // Actualizar
                        $updateStmt->execute([$newStatus, $currentUser['id'], $existing['id']]);
                    }
                } else {
                    // Nuevo registro
                    $insertStmt->execute([$studentId, $date, $newStatus, $currentUser['id']]);
                    $attId = $db->lastInsertId();
                    $auditStmt->execute([$attId, $studentId, $date, null, $newStatus, $currentUser['id']]);
                }
                $savedCount++;
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Asistencia guardada exitosamente ({$savedCount} estudiantes procesados)."
            ]);
            exit;

        // ==========================================================
        // 3. REGISTRAR EXCUSA DE ESTUDIANTE
        // ==========================================================
        case 'save_excuse':
            $studentId = intval($_POST['student_id'] ?? 0);
            $motivo    = trim($_POST['motivo'] ?? '');
            $fecha     = trim($_POST['fecha'] ?? date('Y-m-d'));
            $desc      = trim($_POST['descripcion'] ?? '');
            $status    = trim($_POST['status'] ?? 'aprobada');

            if ($studentId <= 0 || empty($motivo)) {
                echo json_encode(['success' => false, 'message' => 'Estudiante y motivo son obligatorios.']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO excuses (student_id, teacher_id, motivo, fecha, descripcion, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$studentId, $currentUser['id'], $motivo, $fecha, $desc ?: null, $status]);

            // Si se aprueba la excusa para esta fecha, sincronizar asistencia a 'justificada'
            if ($status === 'aprobada') {
                $sync = $db->prepare("
                    INSERT INTO attendance (student_id, date, status, recorded_by)
                    VALUES (?, ?, 'justificada', ?)
                    ON DUPLICATE KEY UPDATE status = 'justificada', recorded_by = VALUES(recorded_by)
                ");
                $sync->execute([$studentId, $fecha, $currentUser['id']]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Excusa registrada y procesada exitosamente.'
            ]);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            exit;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
