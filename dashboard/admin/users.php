<?php
// dashboard/admin/users.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('admin');

$userModel = new User();
$user = currentUser();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    $targetId = (int)$_POST['user_id'];
    
    if ($_POST['action'] === 'suspend') {
        $userModel->setStatus($targetId, 'suspended');
    } elseif ($_POST['action'] === 'activate') {
        $userModel->setStatus($targetId, 'active');
    } elseif ($_POST['action'] === 'delete' && $targetId !== $user['id']) {
        $userModel->delete($targetId);
    }
    header("Location: users.php?success=1");
    exit;
}

$filters = [
    'q'      => $_GET['q'] ?? '',
    'role'   => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? ''
];
$allUsers = $userModel->getAll($filters);

layoutHead('Manage Users');
layoutNavbar('admin', $user['name']);
layoutSidebar('admin', 'Users');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-700 m-0">👥 User Directory</h3>
        <p class="text-muted small">Manage all registered customers and sellers</p>
    </div>
</div>

<!-- Filters -->
<div class="card p-3 mb-4 border-0 shadow-sm">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Search name, email..." value="<?php echo h($filters['q']); ?>">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">All Roles</option>
                <option value="customer" <?php echo $filters['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                <option value="seller" <?php echo $filters['role'] === 'seller' ? 'selected' : ''; ?>>Seller</option>
                <option value="admin" <?php echo $filters['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="suspended" <?php echo $filters['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100 shadow-0">Filter</button>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">User Info</th>
                    <th>Role</th>
                    <th>Contact Details</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allUsers as $u): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px; font-weight:600;">
                                <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-600"><?php echo h($u['name']); ?></div>
                                <div class="text-muted small"><?php echo h($u['email']); ?></div>
                                <div class="x-small text-muted mt-1">Joined: <?php echo date('M d, Y', strtotime($u['created_at'])); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill <?php echo $u['role'] === 'admin' ? 'bg-danger' : ($u['role'] === 'seller' ? 'bg-primary' : 'bg-info'); ?>">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="small fw-600"><span class="material-icons fs-6 align-middle me-1">phone</span><?php echo h($u['phone'] ?: 'N/A'); ?></div>
                        <div class="small text-muted"><span class="material-icons fs-6 align-middle me-1">location_on</span><?php echo h($u['city'] ?: 'N/A'); ?></div>
                        <div class="x-small text-muted"><?php echo h($u['address'] ?: ''); ?></div>
                    </td>
                    <td>
                        <span class="badge <?php echo $u['status'] === 'active' ? 'bg-success' : 'bg-warning text-dark'; ?> shadow-0">
                            <?php echo ucfirst($u['status']); ?>
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><span class="material-icons">more_vert</span></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <?php if ($u['status'] === 'active'): ?>
                                <li>
                                    <form method="POST" class="d-inline">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="action" value="suspend" class="dropdown-item text-warning">
                                            <span class="material-icons fs-6 align-middle me-2">block</span> Suspend
                                        </button>
                                    </form>
                                </li>
                                <?php else: ?>
                                <li>
                                    <form method="POST" class="d-inline">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="action" value="activate" class="dropdown-item text-success">
                                            <span class="material-icons fs-6 align-middle me-2">check_circle</span> Activate
                                        </button>
                                    </form>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($u['id'] !== $user['id']): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user permanently?');">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="action" value="delete" class="dropdown-item text-danger">
                                            <span class="material-icons fs-6 align-middle me-2">delete</span> Delete
                                        </button>
                                    </form>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
