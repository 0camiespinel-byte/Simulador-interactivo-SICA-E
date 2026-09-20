<?php
/**
 * SICA-E · API de Visitantes
 * Institución Educativa Brighton Pamplona
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Leer JSON del cuerpo si aplica
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
        // 1. BUSCAR VISITANTE POR NÚMERO DE DOCUMENTO (LOOKUP)
        // ==========================================================
        case 'lookup':
            $doc = trim($_GET['document_number'] ?? $_POST['document_number'] ?? '');
            if (empty($doc)) {
                echo json_encode(['success' => false, 'message' => 'Número de documento requerido.']);
                exit;
            }

            $stmt = $db->prepare("SELECT id, document_type, document_number, full_name, phone, email, role, org_or_university_name, photo_url, default_motivo FROM visitors WHERE document_number = ? LIMIT 1");
            $stmt->execute([$doc]);
            $visitor = $stmt->fetch();

            if (!$visitor) {
                echo json_encode(['success' => false, 'not_found' => true, 'message' => 'No se encontró registro con este documento.']);
                exit;
            }

            echo json_encode(['success' => true, 'visitor' => $visitor]);
            exit;

        // ==========================================================
        // 2. REGISTRAR PRIMER VISITANTE (NUEVO REGISTRO)
        // ==========================================================
        case 'register_new':
            $docType = trim($_POST['document_type'] ?? 'CC');
            $docNum  = trim($_POST['document_number'] ?? '');
            $name    = trim($_POST['full_name'] ?? '');
            $phone   = trim($_POST['phone'] ?? '');
            $email   = trim($_POST['email'] ?? '');
            $role    = trim($_POST['role'] ?? 'padre');
            $orgName = trim($_POST['org_or_university_name'] ?? '');
            $motivo  = trim($_POST['motivo'] ?? '');
            $photoData = $_POST['photo_data'] ?? '';

            if (empty($docNum) || empty($name) || empty($motivo)) {
                echo json_encode(['success' => false, 'message' => 'Por favor completa todos los campos obligatorios (*).']);
                exit;
            }

            $photoUrl = null;
            // Procesar foto en base64 si fue capturada por la cámara
            if (!empty($photoData) && strpos($photoData, 'data:image') === 0) {
                $dir = UPLOADS_PATH . '/visitors';
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                
                $parts = explode(',', $photoData);
                $imageBinary = base64_decode($parts[1] ?? '');
                if ($imageBinary) {
                    $filename = 'vis_' . preg_replace('/[^a-zA-Z0-9]/', '', $docNum) . '_' . time() . '.jpg';
                    $filepath = $dir . '/' . $filename;
                    if (file_put_contents($filepath, $imageBinary)) {
                        $photoUrl = BASE_URL . '/uploads/visitors/' . $filename;
                    }
                }
            }

            // Si no se capturó foto, generar avatar temático DiceBear como identificador
            if (!$photoUrl) {
                $photoUrl = 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($name) . '&backgroundColor=b6e3f4,ffd5dc,d1d4f9';
            }

            // Guardar o actualizar registro en la tabla de visitantes
            $stmt = $db->prepare("
                INSERT INTO visitors (document_type, document_number, full_name, phone, email, role, org_or_university_name, photo_url, default_motivo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    document_type = VALUES(document_type),
                    full_name = VALUES(full_name),
                    phone = VALUES(phone),
                    email = VALUES(email),
                    role = VALUES(role),
                    org_or_university_name = VALUES(org_or_university_name),
                    photo_url = COALESCE(VALUES(photo_url), photo_url),
                    default_motivo = VALUES(default_motivo)
            ");
            $stmt->execute([$docType, $docNum, $name, $phone ?: null, $email ?: null, $role, $orgName ?: null, $photoUrl, $motivo]);

            // Obtener el ID del visitante
            $getVis = $db->prepare("SELECT id FROM visitors WHERE document_number = ? LIMIT 1");
            $getVis->execute([$docNum]);
            $visRow = $getVis->fetch();
            $visitorId = $visRow ? $visRow['id'] : null;

            // Registrar log de acceso en tiempo real
            $logStmt = $db->prepare("
                INSERT INTO access_logs (visitor_id, visitor_name, document_number, motivo, status, source)
                VALUES (?, ?, ?, ?, 'autorizado', 'manual')
            ");
            $logStmt->execute([$visitorId, $name, $docNum, $motivo]);
            $accessId = $db->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => '¡Registro exitoso! Bienvenido a la Institución Educativa Brighton Pamplona.',
                'visitor_id' => $visitorId,
                'access_id' => $accessId,
                'visitor_name' => $name
            ]);
            exit;

        // ==========================================================
        // 3. REGISTRO RÁPIDO (YA ESTOY REGISTRADO)
        // ==========================================================
        case 'quick_entry':
            $docNum = trim($_POST['document_number'] ?? '');
            $motivo = trim($_POST['motivo'] ?? '');

            if (empty($docNum) || empty($motivo)) {
                echo json_encode(['success' => false, 'message' => 'Documento y motivo son requeridos.']);
                exit;
            }

            $stmt = $db->prepare("SELECT * FROM visitors WHERE document_number = ? LIMIT 1");
            $stmt->execute([$docNum]);
            $visitor = $stmt->fetch();

            if (!$visitor) {
                echo json_encode(['success' => false, 'not_found' => true, 'message' => 'No encontramos tu registro previo. Por favor realiza tu Primer Registro.']);
                exit;
            }

            // Insertar acceso
            $logStmt = $db->prepare("
                INSERT INTO access_logs (visitor_id, visitor_name, document_number, motivo, status, source)
                VALUES (?, ?, ?, ?, 'autorizado', 'manual')
            ");
            $logStmt->execute([$visitor['id'], $visitor['full_name'], $visitor['document_number'], $motivo]);
            $accessId = $db->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => '¡Ingreso confirmado! Bienvenido de nuevo, ' . $visitor['full_name'] . '.',
                'access_id' => $accessId,
                'visitor_name' => $visitor['full_name']
            ]);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    exit;
}
