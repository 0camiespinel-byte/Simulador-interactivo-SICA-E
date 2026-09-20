<?php
/**
 * SICA-E · Gestión del Censo de Estudiantes
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('admin');
$pageTitle = 'Censo de Estudiantes';
$activePortal = 'admin';

$db = getDB();

// Procesar Creación de Estudiante
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_student') {
    $name = trim($_POST['full_name'] ?? '');
    $doc  = trim($_POST['document_number'] ?? '');
    $sec  = trim($_POST['section'] ?? 'secundaria');
    $grd  = trim($_POST['grade'] ?? 'Undécimo');
    $crs  = trim($_POST['course'] ?? '02');
    $photo = 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($name) . '&backgroundColor=b6e3f4,ffd5dc,d1d4f9';

    if (!empty($name) && !empty($doc)) {
        $stmt = $db->prepare("
            INSERT INTO students (full_name, document_number, section, grade, course, photo_url)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), grade = VALUES(grade), course = VALUES(course)
        ");
        $stmt->execute([$name, $doc, $sec, $grd, $crs, $photo]);
        $msg = 'Estudiante guardado exitosamente.';
    }
}

// Filtros
$filterGrade = trim($_GET['grade'] ?? '');
$filterCourse = trim($_GET['course'] ?? '');
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT s.*, 
           COUNT(CASE WHEN a.status = 'ausente' THEN 1 END) as inasistencias,
           COUNT(CASE WHEN a.status = 'justificada' THEN 1 END) as justificadas
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id
    WHERE 1=1
";
$params = [];

if (!empty($filterGrade)) {
    $sql .= " AND s.grade = ?";
    $params[] = $filterGrade;
}
if (!empty($filterCourse)) {
    $sql .= " AND s.course = ?";
    $params[] = $filterCourse;
}
if (!empty($search)) {
    $sql .= " AND (s.full_name LIKE ? OR s.document_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY s.id ORDER BY s.grade DESC, s.course ASC, s.full_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

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
  <a href="<?= BASE_URL ?>/portal-admin/estudiantes.php" class="nav-tab-item active">
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

    <?php if (!empty($msg)): ?>
      <div class="alert alert-success">
        ✓ <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="quick-toolbar">
      <div>
        <h2 style="font-size:1.5rem; font-weight:800; color:var(--navy);">
          Censo de Estudiantes Brighton
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Total de alumnos matriculados: <strong><?= count($students) ?></strong>
        </p>
      </div>

      <button type="button" class="btn btn-wine btn-sm" onclick="document.getElementById('modalNewStudent').classList.add('active')">
        + Registrar Estudiante
      </button>
    </div>

    <!-- Filtros -->
    <div class="card" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
      <form method="GET" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
        <div style="flex:1; min-width:240px;">
          <input type="text" name="q" class="form-input" placeholder="Buscar por nombre o documento..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div style="width:160px;">
          <select name="grade" class="form-select">
            <option value="">Todos los grados</option>
            <option value="Undécimo" <?= $filterGrade === 'Undécimo' ? 'selected' : '' ?>>Undécimo (11°)</option>
            <option value="Décimo" <?= $filterGrade === 'Décimo' ? 'selected' : '' ?>>Décimo (10°)</option>
            <option value="Noveno" <?= $filterGrade === 'Noveno' ? 'selected' : '' ?>>Noveno (9°)</option>
            <option value="Octavo" <?= $filterGrade === 'Octavo' ? 'selected' : '' ?>>Octavo (8°)</option>
            <option value="Séptimo" <?= $filterGrade === 'Séptimo' ? 'selected' : '' ?>>Séptimo (7°)</option>
            <option value="Sexto" <?= $filterGrade === 'Sexto' ? 'selected' : '' ?>>Sexto (6°)</option>
            <option value="Quinto" <?= $filterGrade === 'Quinto' ? 'selected' : '' ?>>Quinto</option>
            <option value="Cuarto" <?= $filterGrade === 'Cuarto' ? 'selected' : '' ?>>Cuarto</option>
            <option value="Tercero" <?= $filterGrade === 'Tercero' ? 'selected' : '' ?>>Tercero</option>
            <option value="Segundo" <?= $filterGrade === 'Segundo' ? 'selected' : '' ?>>Segundo</option>
            <option value="Primero" <?= $filterGrade === 'Primero' ? 'selected' : '' ?>>Primero</option>
          </select>
        </div>

        <div style="width:120px;">
          <select name="course" class="form-select">
            <option value="">Curso</option>
            <option value="01" <?= $filterCourse === '01' ? 'selected' : '' ?>>Curso 01</option>
            <option value="02" <?= $filterCourse === '02' ? 'selected' : '' ?>>Curso 02</option>
          </select>
        </div>

        <button type="submit" class="btn btn-navy btn-sm">Filtrar</button>
        <?php if (!empty($search) || !empty($filterGrade) || !empty($filterCourse)): ?>
          <a href="<?= BASE_URL ?>/portal-admin/estudiantes.php" class="btn btn-outline btn-sm">Limpiar</a>
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
              <th>Nombre del Estudiante</th>
              <th>Documento</th>
              <th>Sección</th>
              <th>Grado y Curso</th>
              <th>Inasistencias</th>
              <th>Justificadas</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">
                  No hay estudiantes registrados para los filtros indicados.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $st): ?>
                <tr>
                  <td>
                    <img src="<?= htmlspecialchars($st['photo_url'] ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($st['full_name'])) ?>" 
                         alt="Foto" 
                         style="width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border);">
                  </td>
                  <td><strong><?= htmlspecialchars($st['full_name']) ?></strong></td>
                  <td><code><?= htmlspecialchars($st['document_number']) ?></code></td>
                  <td><span class="badge badge-navy"><?= ucfirst($st['section']) ?></span></td>
                  <td>
                    <strong><?= htmlspecialchars($st['grade']) ?> - <?= htmlspecialchars($st['course']) ?></strong>
                  </td>
                  <td>
                    <span class="badge <?= $st['inasistencias'] > 0 ? 'badge-danger' : 'badge-success' ?>">
                      <?= $st['inasistencias'] ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge badge-warning">
                      <?= $st['justificadas'] ?>
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

<!-- Modal Nuevo Estudiante -->
<div id="modalNewStudent" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-header">
      <h3 style="font-size:1.15rem; font-weight:700; color:var(--navy);">Registrar Nuevo Estudiante</h3>
      <button type="button" onclick="document.getElementById('modalNewStudent').classList.remove('active')" style="background:none; border:none; font-size:1.25rem; cursor:pointer;">&times;</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="create_student">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="stName">Nombre Completo <span class="required">*</span></label>
          <input type="text" id="stName" name="full_name" class="form-input" required placeholder="Ej: Camilo Andrés Rojas">
        </div>
        <div class="form-group">
          <label class="form-label" for="stDoc">Número de Documento <span class="required">*</span></label>
          <input type="text" id="stDoc" name="document_number" class="form-input" required placeholder="Ej: 10901110299">
        </div>
        <div class="grid-3">
          <div class="form-group">
            <label class="form-label">Sección</label>
            <select name="section" class="form-select">
              <option value="secundaria">Secundaria</option>
              <option value="primaria">Primaria</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Grado</label>
            <select name="grade" class="form-select">
              <option value="Undécimo">Undécimo</option>
              <option value="Décimo">Décimo</option>
              <option value="Noveno">Noveno</option>
              <option value="Octavo">Octavo</option>
              <option value="Séptimo">Séptimo</option>
              <option value="Sexto">Sexto</option>
              <option value="Quinto">Quinto</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Curso</label>
            <select name="course" class="form-select">
              <option value="01">01</option>
              <option value="02" selected>02</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalNewStudent').classList.remove('active')">Cancelar</button>
        <button type="submit" class="btn btn-wine">Guardar Estudiante</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
