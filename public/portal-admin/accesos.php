<?php
/**
 * SICA-E · Control Detallado de Accesos
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('admin');
$pageTitle = 'Control de Accesos';
$activePortal = 'admin';

$db = getDB();

// Filtros de búsqueda
$search = trim($_GET['q'] ?? '');
$filterDate = trim($_GET['date'] ?? '');
$filterSource = trim($_GET['source'] ?? '');

$sql = "SELECT id, visitor_name, document_number, motivo, entrada, salida, status, source, notes 
        FROM access_logs WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (visitor_name LIKE ? OR document_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterDate)) {
    $sql .= " AND DATE(entrada) = ?";
    $params[] = $filterDate;
}

if (!empty($filterSource)) {
    $sql .= " AND source = ?";
    $params[] = $filterSource;
}

$sql .= " ORDER BY entrada DESC LIMIT 300";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-admin/" class="nav-tab-item">
    📊 Resumen y Métricas
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/accesos.php" class="nav-tab-item active">
    ⏱️ Control de Accesos
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/visitantes.php" class="nav-tab-item">
    👥 Directorio de Visitantes
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/estudiantes.php" class="nav-tab-item">
    🎓 Censo de Estudiantes
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/usuarios.php" class="nav-tab-item">
    🛡️ Usuarios y Roles
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/reportes.php" class="nav-tab-item">
    📑 Reportes y Descargas
  </a>
</nav>

<main style="flex:1; padding: 2rem 1rem;">
  <div class="container container-lg">

    <div class="quick-toolbar">
      <div>
        <h2 style="font-size:1.5rem; font-weight:800; color:var(--navy);">
          Bitácora de Control de Accesos
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Total registros encontrados: <strong><?= count($logs) ?></strong>
        </p>
      </div>

      <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/api/access_logs.php?action=export_csv" class="btn btn-sm btn-outline">
          📥 Exportar a Excel
        </a>
      </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
      <form method="GET" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
        <div style="flex:1; min-width:240px;">
          <input type="text" name="q" class="form-input" placeholder="Buscar por nombre o número de cédula..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div style="width:160px;">
          <input type="date" name="date" class="form-input" value="<?= htmlspecialchars($filterDate) ?>" title="Filtrar por fecha">
        </div>

        <div style="width:140px;">
          <select name="source" class="form-select">
            <option value="">Todos los orígenes</option>
            <option value="manual" <?= $filterSource === 'manual' ? 'selected' : '' ?>>Manual</option>
            <option value="sensor" <?= $filterSource === 'sensor' ? 'selected' : '' ?>>Sensor</option>
          </select>
        </div>

        <button type="submit" class="btn btn-navy btn-sm">
          Filtrar
        </button>
        <?php if (!empty($search) || !empty($filterDate) || !empty($filterSource)): ?>
          <a href="<?= BASE_URL ?>/portal-admin/accesos.php" class="btn btn-outline btn-sm">
            Limpiar
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Persona / Visitante</th>
              <th>Documento</th>
              <th>Motivo de Ingreso</th>
              <th>Hora Entrada</th>
              <th>Hora Salida</th>
              <th>Estado</th>
              <th>Tipo</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="9" style="text-align:center; padding:2rem; color:var(--text-muted);">
                  No se encontraron registros con los filtros seleccionados.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td>#<?= $log['id'] ?></td>
                  <td><strong><?= htmlspecialchars($log['visitor_name']) ?></strong></td>
                  <td><code><?= htmlspecialchars($log['document_number']) ?></code></td>
                  <td><?= htmlspecialchars($log['motivo']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($log['entrada'])) ?></td>
                  <td>
                    <?php if ($log['salida']): ?>
                      <?= date('d/m/Y H:i', strtotime($log['salida'])) ?>
                    <?php else: ?>
                      <span class="badge badge-success">En el colegio</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $log['status'] === 'autorizado' ? 'badge-success' : 'badge-danger' ?>">
                      <?= htmlspecialchars($log['status']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= $log['source'] === 'sensor' ? 'badge-info' : 'badge-navy' ?>">
                      <?= htmlspecialchars($log['source']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!$log['salida']): ?>
                      <button type="button" class="btn btn-sm btn-outline" onclick="marcarSalida(<?= $log['id'] ?>)">
                        Marcar Salida
                      </button>
                    <?php else: ?>
                      <span style="font-size:0.75rem; color:var(--text-muted);">Completado</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<script>
async function marcarSalida(logId) {
  if (!confirm('¿Registrar salida para este acceso?')) return;
  const res = await API.post('<?= BASE_URL ?>/api/access_logs.php?action=mark_exit', { log_id: logId });
  if (res.success) {
    API.toast(res.message, 'success');
    setTimeout(() => location.reload(), 600);
  } else {
    API.toast(res.message, 'error');
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
