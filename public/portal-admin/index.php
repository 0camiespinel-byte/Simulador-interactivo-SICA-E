<?php
/**
 * SICA-E · Panel Administrativo Principal (Dashboard & Métricas)
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('admin');
$currentUser = Auth::user();
$pageTitle = 'Panel Administrativo';
$activePortal = 'admin';

$db = getDB();

// Consultar Métricas Principales
$today = date('Y-m-d');
$totalToday = $db->query("SELECT COUNT(*) FROM access_logs WHERE DATE(entrada) = '$today'")->fetchColumn() ?: 0;
$activeNow  = $db->query("SELECT COUNT(*) FROM access_logs WHERE DATE(entrada) = '$today' AND salida IS NULL")->fetchColumn() ?: 0;
$totalVisitors = $db->query("SELECT COUNT(*) FROM visitors")->fetchColumn() ?: 0;
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$totalExcusesPending = $db->query("SELECT COUNT(*) FROM excuses WHERE status = 'pendiente'")->fetchColumn() ?: 0;

// Últimos 10 accesos en vivo
$recentLogsStmt = $db->query("
    SELECT id, visitor_name, document_number, motivo, entrada, salida, status, source
    FROM access_logs
    ORDER BY entrada DESC
    LIMIT 10
");
$recentLogs = $recentLogsStmt->fetchAll();

// Datos para Gráfico: Accesos por Día (Últimos 7 días)
$chartDaysStmt = $db->query("
    SELECT DATE_FORMAT(entrada, '%d/%m') as dia, COUNT(*) as total
    FROM access_logs
    WHERE entrada >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(entrada), dia
    ORDER BY DATE(entrada) ASC
");
$chartDaysData = $chartDaysStmt->fetchAll();
$daysLabels = array_column($chartDaysData, 'dia');
$daysCounts = array_column($chartDaysData, 'total');

// Si no hay suficientes días, rellenar valores por defecto para mostrar el gráfico
if (empty($daysLabels)) {
    $daysLabels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Hoy'];
    $daysCounts = [12, 19, 15, 22, 18, 5, $totalToday];
}

// Datos para Gráfico: Motivos más Frecuentes
$chartMotivosStmt = $db->query("
    SELECT motivo, COUNT(*) as total
    FROM access_logs
    GROUP BY motivo
    ORDER BY total DESC
    LIMIT 5
");
$chartMotivosData = $chartMotivosStmt->fetchAll();
$motivosLabels = array_column($chartMotivosData, 'motivo');
$motivosCounts = array_column($chartMotivosData, 'total');

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Barra de Navegación del Panel Admin -->
<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-admin/" class="nav-tab-item active">
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
  <a href="<?= BASE_URL ?>/portal-admin/reportes.php" class="nav-tab-item">
    📑 Reportes y Descargas
  </a>
</nav>

<main style="flex:1; padding: 2rem 1rem;">
  <div class="container container-lg">

    <!-- Encabezado de Bienvenida y Acciones Rápidas -->
    <div class="quick-toolbar">
      <div>
        <h2 style="font-size:1.65rem; font-weight:800; color:var(--navy);">
          Panel de Control Administrativo
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Gestión integral de accesos, seguridad institucional y comunidad Brighton Pamplona
        </p>
      </div>

      <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <button type="button" id="btnSimulate" class="btn btn-sm btn-outline">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"></path><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"></path><circle cx="12" cy="12" r="2"></circle><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"></path><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"></path></svg>
          Simular Sensor
        </button>
        <button type="button" id="btnPurge" class="btn btn-sm btn-outline" style="color:var(--danger);">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          Purgar Logs Antiguos
        </button>
        <a href="<?= BASE_URL ?>/portal-admin/reportes.php" class="btn btn-sm btn-wine">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Descargar Reporte
        </a>
      </div>
    </div>

    <!-- Tarjetas de Métricas KPI -->
    <div class="grid-4" style="margin-bottom: 2rem;">
      <div class="stat-card">
        <div class="stat-icon wine">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="stat-info">
          <p class="stat-value"><?= $totalToday ?></p>
          <p class="stat-label">Ingresos Registrados Hoy</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon success">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
        </div>
        <div class="stat-info">
          <p class="stat-value" style="color:var(--success);"><?= $activeNow ?></p>
          <p class="stat-label">Visitantes en el Colegio</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon navy">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
          <p class="stat-value"><?= $totalVisitors ?></p>
          <p class="stat-label">Censo Total de Visitantes</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon gold">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path></svg>
        </div>
        <div class="stat-info">
          <p class="stat-value"><?= $totalStudents ?></p>
          <p class="stat-label">Estudiantes en Sistema</p>
        </div>
      </div>
    </div>

    <!-- Sección de Gráficos Estadísticos -->
    <div class="grid-2" style="margin-bottom: 2rem;">
      <!-- Gráfico 1: Ingresos por Día -->
      <div class="card">
        <div class="card-header">
          <div>
            <h3 class="card-title">📈 Frecuencia de Accesos Recientes</h3>
            <p class="card-subtitle">Volumen de ingresos en los últimos días</p>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="chartDays"></canvas>
        </div>
      </div>

      <!-- Gráfico 2: Motivos más Comunes -->
      <div class="card">
        <div class="card-header">
          <div>
            <h3 class="card-title">🎯 Distribución de Motivos de Visita</h3>
            <p class="card-subtitle">Razones principales de asistencia a la institución</p>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="chartMotivos"></canvas>
        </div>
      </div>
    </div>

    <!-- Tabla de Accesos en Vivo -->
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">⏱️ Registro de Accesos Recientes</h3>
          <p class="card-subtitle">Últimas personas ingresadas a la institución</p>
        </div>
        <a href="<?= BASE_URL ?>/portal-admin/accesos.php" class="btn btn-sm btn-outline">
          Ver Todos los Accesos →
        </a>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Visitante</th>
              <th>Documento</th>
              <th>Motivo</th>
              <th>Hora Entrada</th>
              <th>Hora Salida</th>
              <th>Estado</th>
              <th>Origen</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentLogs)): ?>
              <tr>
                <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">
                  No hay registros de accesos recientes.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentLogs as $log): ?>
                <tr id="log-row-<?= $log['id'] ?>">
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
                        Registrar Salida
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
document.addEventListener('DOMContentLoaded', () => {
  // Gráfico de Ingresos por Día
  const ctxDays = document.getElementById('chartDays').getContext('2d');
  new Chart(ctxDays, {
    type: 'bar',
    data: {
      labels: <?= json_encode($daysLabels) ?>,
      datasets: [{
        label: 'Visitas',
        data: <?= json_encode($daysCounts) ?>,
        backgroundColor: 'rgba(107, 23, 40, 0.85)',
        borderColor: '#6B1728',
        borderWidth: 1.5,
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });

  // Gráfico de Motivos
  const ctxMotivos = document.getElementById('chartMotivos').getContext('2d');
  new Chart(ctxMotivos, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(!empty($motivosLabels) ? $motivosLabels : ['Reunión docente', 'Trámites', 'Entrega docs']) ?>,
      datasets: [{
        data: <?= json_encode(!empty($motivosCounts) ? $motivosCounts : [45, 30, 25]) ?>,
        backgroundColor: ['#6B1728', '#162544', '#d97706', '#16a34a', '#64748b']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12 } }
      }
    }
  });

  // Marcar Salida
  window.marcarSalida = async function(logId) {
    if (!confirm('¿Deseas marcar la hora de salida de esta persona?')) return;
    const res = await API.post('<?= BASE_URL ?>/api/access_logs.php?action=mark_exit', { log_id: logId });
    if (res.success) {
      API.toast(res.message, 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      API.toast(res.message, 'error');
    }
  };

  // Simular Sensor
  document.getElementById('btnSimulate').addEventListener('click', async () => {
    const res = await API.post('<?= BASE_URL ?>/api/access_logs.php?action=simulate_sensor', {});
    if (res.success) {
      API.toast(res.message, 'success');
      setTimeout(() => location.reload(), 1000);
    }
  });

  // Purgar Logs
  document.getElementById('btnPurge').addEventListener('click', async () => {
    const days = prompt('¿Deseas eliminar registros anteriores a cuántos días?', '30');
    if (!days) return;
    const res = await API.post('<?= BASE_URL ?>/api/access_logs.php?action=purge_logs', { days: parseInt(days) });
    if (res.success) {
      API.toast(res.message, 'info');
      setTimeout(() => location.reload(), 1000);
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
