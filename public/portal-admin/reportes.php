<?php
/**
 * SICA-E · Generación de Reportes Institucionales y Exportación
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('admin');
$pageTitle = 'Reportes Oficiales';
$activePortal = 'admin';

$db = getDB();

$startDate = trim($_GET['start'] ?? date('Y-m-01'));
$endDate   = trim($_GET['end'] ?? date('Y-m-d'));

$stmt = $db->prepare("
    SELECT id, visitor_name, document_number, motivo, entrada, salida, status, source
    FROM access_logs
    WHERE DATE(entrada) BETWEEN ? AND ?
    ORDER BY entrada DESC
");
$stmt->execute([$startDate, $endDate]);
$reportLogs = $stmt->fetchAll();

$totalPeriodo = count($reportLogs);
$autorizados = count(array_filter($reportLogs, fn($l) => $l['status'] === 'autorizado'));

require_once __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
  .site-header, .nav-tabs, .quick-toolbar, .site-footer, .no-print {
    display: none !important;
  }
  body {
    background: #ffffff !important;
    font-size: 11pt;
  }
  .card {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
  }
}
</style>

<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-admin/" class="nav-tab-item">
    📊 Resumen y Métricas
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/accesos.php" class="nav-tab-item">
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
  <a href="<?= BASE_URL ?>/portal-admin/reportes.php" class="nav-tab-item active">
    📑 Reportes y Descargas
  </a>
</nav>

<main style="flex:1; padding: 2rem 1rem;">
  <div class="container container-lg">

    <div class="quick-toolbar no-print">
      <div>
        <h2 style="font-size:1.5rem; font-weight:800; color:var(--navy);">
          Informes y Reportes de Seguridad
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Generación de consolidados oficiales de acceso escolar
        </p>
      </div>

      <div style="display:flex; gap:0.5rem;">
        <button type="button" class="btn btn-sm btn-outline" onclick="window.print()">
          🖨️ Imprimir / Guardar en PDF
        </button>
        <a href="<?= BASE_URL ?>/api/access_logs.php?action=export_csv&start=<?= urlencode($startDate) ?>&end=<?= urlencode($endDate) ?>" class="btn btn-sm btn-wine">
          📥 Descargar en Excel (.csv)
        </a>
      </div>
    </div>

    <!-- Filtro de Rango de Fechas -->
    <div class="card no-print" style="margin-bottom:1.5rem; padding:1.25rem;">
      <form method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex:1; min-width:180px;">
          <label class="form-label">Fecha Inicial</label>
          <input type="date" name="start" class="form-input" value="<?= htmlspecialchars($startDate) ?>">
        </div>
        <div style="flex:1; min-width:180px;">
          <label class="form-label">Fecha Final</label>
          <input type="date" name="end" class="form-input" value="<?= htmlspecialchars($endDate) ?>">
        </div>
        <button type="submit" class="btn btn-navy">
          Generar Informe
        </button>
      </form>
    </div>

    <!-- Encabezado del Reporte Oficial -->
    <div class="card" style="padding:2rem;">
      <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid var(--navy); padding-bottom:1rem; margin-bottom:1.5rem;">
        <div>
          <h2 style="font-size:1.4rem; font-weight:800; color:var(--navy);"><?= INSTITUTION_NAME ?></h2>
          <p style="font-size:0.95rem; font-weight:600; color:var(--wine);"><?= APP_NAME ?> · Reporte Oficial de Bitácora de Accesos</p>
          <p style="font-size:0.8rem; color:var(--text-muted);">Periodo: <?= date('d/m/Y', strtotime($startDate)) ?> al <?= date('d/m/Y', strtotime($endDate)) ?></p>
        </div>
        <div style="text-align:right;">
          <p style="font-size:0.8rem; color:var(--text-muted);">Generado: <?= date('d/m/Y H:i') ?></p>
          <p style="font-size:0.8rem; color:var(--text-muted);">Total Registros: <strong><?= $totalPeriodo ?></strong></p>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Visitante</th>
              <th>Documento</th>
              <th>Motivo de la Visita</th>
              <th>Entrada</th>
              <th>Salida</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($reportLogs)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">
                  No hay movimientos registrados en el periodo seleccionado.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($reportLogs as $r): ?>
                <tr>
                  <td>#<?= $r['id'] ?></td>
                  <td><strong><?= htmlspecialchars($r['visitor_name']) ?></strong></td>
                  <td><code><?= htmlspecialchars($r['document_number']) ?></code></td>
                  <td><?= htmlspecialchars($r['motivo']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($r['entrada'])) ?></td>
                  <td>
                    <?= $r['salida'] ? date('d/m/Y H:i', strtotime($r['salida'])) : 'En institución' ?>
                  </td>
                  <td>
                    <span class="badge <?= $r['status'] === 'autorizado' ? 'badge-success' : 'badge-danger' ?>">
                      <?= htmlspecialchars($r['status']) ?>
                    </span>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
