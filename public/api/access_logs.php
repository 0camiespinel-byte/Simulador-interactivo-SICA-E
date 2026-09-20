<?php
/**
 * SICA-E · API de Control de Accesos
 * Institución Educativa Brighton Pamplona
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

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
        // 1. MARCAR SALIDA DE VISITANTE
        // ==========================================================
        case 'mark_exit':
            Auth::requireAuth();
            $logId = intval($_POST['log_id'] ?? 0);
            if ($logId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de acceso inválido.']);
                exit;
            }

            $stmt = $db->prepare("UPDATE access_logs SET salida = NOW() WHERE id = ? AND salida IS NULL");
            $stmt->execute([$logId]);

            echo json_encode([
                'success' => true,
                'message' => 'Salida registrada correctamente.'
            ]);
            exit;

        // ==========================================================
        // 2. SIMULADOR DE SENSOR DE ACCESO (SENSOR INFRARROJO)
        // ==========================================================
        case 'simulate_sensor':
            Auth::requireRole('admin');
            $names = [
                "Carolina Mendoza",
                "Jorge Luis Rivera",
                "Patricia Salinas",
                "Mauricio Vélez",
                "Adriana Torres",
                "Felipe Aristizábal"
            ];
            $motivos = [
                "Reunión con docente",
                "Entrega de documentos",
                "Trámite administrativo",
                "Recoger estudiante"
            ];

            $count = rand(2, 4);
            $inserted = 0;
            $stmt = $db->prepare("
                INSERT INTO access_logs (visitor_name, document_number, motivo, status, source, notes)
                VALUES (?, ?, ?, 'autorizado', 'sensor', 'Detección automática por sensor de acceso')
            ");

            for ($i = 0; $i < $count; $i++) {
                $name = $names[array_rand($names)];
                $doc = 'SIM-' . rand(10000, 89999);
                $motivo = $motivos[array_rand($motivos)];
                $stmt->execute([$name, $doc, $motivo]);
                $inserted++;
            }

            echo json_encode([
                'success' => true,
                'message' => "Simulación completada: {$inserted} accesos detectados por sensor.",
                'count' => $inserted
            ]);
            exit;

        // ==========================================================
        // 3. PURGAR REGISTROS ANTIGUOS
        // ==========================================================
        case 'purge_logs':
            Auth::requireRole('admin');
            $days = intval($_POST['days'] ?? 30);
            if ($days < 1) $days = 30;

            $stmt = $db->prepare("DELETE FROM access_logs WHERE entrada < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$days]);
            $deleted = $stmt->rowCount();

            echo json_encode([
                'success' => true,
                'message' => "Se han purgado {$deleted} registros anteriores a {$days} días.",
                'deleted' => $deleted
            ]);
            exit;

        // ==========================================================
        // 4. EXPORTAR REPORTE A CSV (COMPATIBLE CON EXCEL)
        // ==========================================================
        case 'export_csv':
            Auth::requireAuth();
            
            $start = $_GET['start'] ?? date('Y-m-01');
            $end   = $_GET['end'] ?? date('Y-m-d');

            $stmt = $db->prepare("
                SELECT id, visitor_name, document_number, motivo, entrada, salida, status, source
                FROM access_logs
                WHERE DATE(entrada) BETWEEN ? AND ?
                ORDER BY entrada DESC
            ");
            $stmt->execute([$start, $end]);
            $rows = $stmt->fetchAll();

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_accesos_sica_' . date('Ymd_His') . '.csv"');

            // BOM UTF-8 para apertura correcta en Microsoft Excel
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Visitante', 'Documento', 'Motivo', 'Fecha y Hora Entrada', 'Fecha y Hora Salida', 'Estado', 'Origen']);

            foreach ($rows as $r) {
                fputcsv($output, [
                    $r['id'],
                    $r['visitor_name'],
                    $r['document_number'],
                    $r['motivo'],
                    $r['entrada'],
                    $r['salida'] ?? 'En institución',
                    $r['status'],
                    $r['source']
                ]);
            }
            fclose($output);
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
