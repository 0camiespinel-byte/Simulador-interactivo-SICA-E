<?php
/**
 * SICA-E · Directorio de Visitantes Registrados
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('admin');
$pageTitle = 'Directorio de Visitantes';
$activePortal = 'admin';

$db = getDB();

$search = trim($_GET['q'] ?? '');
$filterRole = trim($_GET['role'] ?? '');

$sql = "
    SELECT v.id, v.document_type, v.document_number, v.full_name, v.phone, v.email, 
           v.role, v.org_or_university_name, v.photo_url, v.created_at,
           COUNT(a.id) as total_visitas,
           MAX(a.entrada) as ultima_visita
    FROM visitors v
    LEFT JOIN access_logs a ON a.visitor_id = v.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (v.full_name LIKE ? OR v.document_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterRole)) {
    $sql .= " AND v.role = ?";
    $params[] = $filterRole;
}

$sql .= " GROUP BY v.id ORDER BY v.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$visitors = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="nav-tabs">
  <a href="<?= BASE_URL ?>/portal-admin/" class="nav-tab-item">
    📊 Resumen y Métricas
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/accesos.php" class="nav-tab-item">
    ⏱️ Control de Accesos
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/visitantes.php" class="nav-tab-item active">
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
          Censo de Visitantes Registrados
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Total de personas registradas: <strong><?= count($visitors) ?></strong>
        </p>
      </div>

      <a href="<?= BASE_URL ?>/portal-visitantes/nuevo.php" target="_blank" class="btn btn-sm btn-wine">
        + Registrar Nuevo Visitante
      </a>
    </div>

    <!-- Buscador -->
    <div class="card" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
      <form method="GET" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
        <div style="flex:1; min-width:260px;">
          <input type="text" name="q" class="form-input" placeholder="Buscar visitante por nombre o documento..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div style="width:180px;">
          <select name="role" class="form-select">
            <option value="">Todos los roles</option>
            <option value="padre" <?= $filterRole === 'padre' ? 'selected' : '' ?>>Padres / Acudientes</option>
            <option value="organizacion" <?= $filterRole === 'organizacion' ? 'selected' : '' ?>>Organizaciones</option>
            <option value="universidad" <?= $filterRole === 'universidad' ? 'selected' : '' ?>>Universidades</option>
          </select>
        </div>

        <button type="submit" class="btn btn-navy btn-sm">Buscar</button>
        <?php if (!empty($search) || !empty($filterRole)): ?>
          <a href="<?= BASE_URL ?>/portal-admin/visitantes.php" class="btn btn-outline btn-sm">Limpiar</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Nombre Completo</th>
              <th>Documento</th>
              <th>Rol</th>
              <th>Entidad / Org</th>
              <th>Teléfono</th>
              <th>Total Visitas</th>
              <th>Último Ingreso</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($visitors)): ?>
              <tr>
                <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">
                  No hay visitantes registrados con esos criterios.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($visitors as $v): ?>
                <tr>
                  <td>
                    <img src="<?= htmlspecialchars($v['photo_url'] ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($v['full_name'])) ?>" 
                         alt="Foto" 
                         style="width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border);">
                  </td>
                  <td><strong><?= htmlspecialchars($v['full_name']) ?></strong></td>
                  <td><code><?= htmlspecialchars($v['document_type'] . ' ' . $v['document_number']) ?></code></td>
                  <td>
                    <span class="badge badge-navy">
                      <?= htmlspecialchars($v['role']) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($v['org_or_university_name'] ?: '—') ?></td>
                  <td><?= htmlspecialchars($v['phone'] ?: '—') ?></td>
                  <td><strong style="color:var(--wine);"><?= $v['total_visitas'] ?></strong></td>
                  <td>
                    <?= $v['ultima_visita'] ? date('d/m/Y H:i', strtotime($v['ultima_visita'])) : 'Sin ingresos' ?>
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
