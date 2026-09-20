<?php
/**
 * SICA-E · Gestión de Personal, Usuarios y Roles
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireRole('admin');
$currentUser = Auth::user();
$pageTitle = 'Usuarios y Roles';
$activePortal = 'admin';

$db = getDB();

$usersStmt = $db->query("
    SELECT id, full_name, email, role, titular_grade, titular_course, titular_section, phone, photo_url, status, created_at 
    FROM users 
    ORDER BY role ASC, full_name ASC
");
$users = $usersStmt->fetchAll();

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
  <a href="<?= BASE_URL ?>/portal-admin/estudiantes.php" class="nav-tab-item">
    🎓 Censo de Estudiantes
  </a>
  <a href="<?= BASE_URL ?>/portal-admin/usuarios.php" class="nav-tab-item active">
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
          Gestión de Personal y Cuentas de Acceso
        </h2>
        <p style="font-size:0.85rem; color:var(--text-muted);">
          Administración de docentes, roles institucionales y credenciales seguras
        </p>
      </div>

      <button type="button" class="btn btn-wine btn-sm" onclick="document.getElementById('modalNewUser').classList.add('active')">
        + Crear Usuario Personal
      </button>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Correo Institucional</th>
              <th>Rol en el Sistema</th>
              <th>Grupo Titular (Docente)</th>
              <th>Teléfono</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>
                  <div style="display:flex; align-items:center; gap:0.75rem;">
                    <img src="<?= htmlspecialchars($u['photo_url'] ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed=' . urlencode($u['full_name'])) ?>" 
                         alt="Avatar" 
                         style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid var(--border);">
                    <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <span class="badge <?= $u['role'] === 'admin' ? 'badge-wine' : 'badge-navy' ?>">
                    <?= htmlspecialchars($u['role']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($u['role'] === 'profesor' && $u['titular_grade']): ?>
                    <strong><?= htmlspecialchars($u['titular_grade']) ?> - <?= htmlspecialchars($u['titular_course']) ?></strong>
                  <?php else: ?>
                    <span style="color:var(--text-muted);">No aplica</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                <td>
                  <span class="badge <?= $u['status'] === 'activo' ? 'badge-success' : 'badge-danger' ?>">
                    <?= htmlspecialchars($u['status']) ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex; gap:0.35rem;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="resetPw(<?= $u['id'] ?>)" title="Restablecer Contraseña">
                      🔑 Clave
                    </button>
                    <?php if ($u['id'] != $currentUser['id']): ?>
                      <button type="button" class="btn btn-sm btn-outline" onclick="toggleStatus(<?= $u['id'] ?>)">
                        <?= $u['status'] === 'activo' ? 'Desactivar' : 'Activar' ?>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Modal Nuevo Usuario -->
<div id="modalNewUser" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-header">
      <h3 style="font-size:1.15rem; font-weight:700; color:var(--navy);">Crear Nuevo Usuario Personal</h3>
      <button type="button" onclick="document.getElementById('modalNewUser').classList.remove('active')" style="background:none; border:none; font-size:1.25rem; cursor:pointer;">&times;</button>
    </div>
    <form id="newUserForm">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nombre Completo <span class="required">*</span></label>
          <input type="text" name="full_name" class="form-input" required placeholder="Ej: Lic. Jesús Ramón Valero">
        </div>
        <div class="form-group">
          <label class="form-label">Correo Electrónico Institucional <span class="required">*</span></label>
          <input type="email" name="email" class="form-input" required placeholder="ejemplo@brighton.edu.co">
        </div>
        <div class="form-group">
          <label class="form-label">Contraseña Temporal <span class="required">*</span></label>
          <input type="password" name="password" class="form-input" required minlength="6" placeholder="Mínimo 6 caracteres">
        </div>
        <div class="form-group">
          <label class="form-label">Rol Asignado <span class="required">*</span></label>
          <select name="role" class="form-select" onchange="toggleTeacherAssignment(this.value)">
            <option value="profesor" selected>Docente / Profesor</option>
            <option value="admin">Administrador Institucional</option>
          </select>
        </div>
        <div id="teacherAssignmentGroup">
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Grado Titular</label>
              <select name="titular_grade" class="form-select">
                <option value="Undécimo">Undécimo (11°)</option>
                <option value="Décimo">Décimo (10°)</option>
                <option value="Noveno">Noveno (9°)</option>
                <option value="Octavo">Octavo (8°)</option>
                <option value="Séptimo">Séptimo (7°)</option>
                <option value="Sexto">Sexto (6°)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Curso</label>
              <select name="titular_course" class="form-select">
                <option value="01">01</option>
                <option value="02" selected>02</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalNewUser').classList.remove('active')">Cancelar</button>
        <button type="submit" class="btn btn-wine">Crear Cuenta</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleTeacherAssignment(role) {
  document.getElementById('teacherAssignmentGroup').style.display = role === 'profesor' ? 'block' : 'none';
}

document.getElementById('newUserForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const res = await API.post('<?= BASE_URL ?>/api/users.php?action=create_user', formData);
  if (res.success) {
    API.toast(res.message, 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    API.toast(res.message, 'error');
  }
});

async function resetPw(userId) {
  const newPw = prompt('Escribe la nueva contraseña para este usuario (mínimo 6 caracteres):');
  if (!newPw) return;
  const res = await API.post('<?= BASE_URL ?>/api/users.php?action=reset_password', { user_id: userId, new_password: newPw });
  if (res.success) {
    API.toast(res.message, 'success');
  } else {
    API.toast(res.message, 'error');
  }
}

async function toggleStatus(userId) {
  const res = await API.post('<?= BASE_URL ?>/api/users.php?action=toggle_status', { user_id: userId });
  if (res.success) {
    API.toast(res.message, 'success');
    setTimeout(() => location.reload(), 600);
  } else {
    API.toast(res.message, 'error');
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
