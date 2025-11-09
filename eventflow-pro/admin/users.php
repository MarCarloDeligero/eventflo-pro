<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('super_admin')) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = "Manage Users - Admin";
$user = new User();
$db = new Database();

// Handle actions
if (isset($_GET['action'])) {
    $user_id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'make_organizer':
            $db->query('UPDATE users SET role = "organizer" WHERE id = :id');
            $db->bind(':id', $user_id);
            if ($db->execute()) {
                $_SESSION['flash_message'] = 'User promoted to organizer';
                $_SESSION['flash_type'] = 'success';
            }
            break;
            
        case 'make_attendee':
            $db->query('UPDATE users SET role = "attendee" WHERE id = :id');
            $db->bind(':id', $user_id);
            if ($db->execute()) {
                $_SESSION['flash_message'] = 'User set as attendee';
                $_SESSION['flash_type'] = 'success';
            }
            break;
            
        case 'deactivate':
            $db->query('UPDATE users SET is_active = 0 WHERE id = :id');
            $db->bind(':id', $user_id);
            if ($db->execute()) {
                $_SESSION['flash_message'] = 'User deactivated';
                $_SESSION['flash_type'] = 'success';
            }
            break;
            
        case 'activate':
            $db->query('UPDATE users SET is_active = 1 WHERE id = :id');
            $db->bind(':id', $user_id);
            if ($db->execute()) {
                $_SESSION['flash_message'] = 'User activated';
                $_SESSION['flash_type'] = 'success';
            }
            break;
            
        case 'delete':
            $db->query('DELETE FROM users WHERE id = :id AND id != :current_user');
            $db->bind(':id', $user_id);
            $db->bind(':current_user', $_SESSION['user_id']);
            if ($db->execute()) {
                $_SESSION['flash_message'] = 'User deleted successfully';
                $_SESSION['flash_type'] = 'success';
            }
            break;
    }
    header('Location: users.php');
    exit;
}

// Get users with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = [];
$params = [];

if (isset($_GET['role']) && in_array($_GET['role'], ['super_admin', 'organizer', 'attendee'])) {
    $where_conditions[] = 'role = :role';
    $params[':role'] = $_GET['role'];
}

if (isset($_GET['status']) && $_GET['status'] === 'inactive') {
    $where_conditions[] = 'is_active = 0';
} else {
    $where_conditions[] = 'is_active = 1';
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = '(name LIKE :search OR email LIKE :search)';
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users
$db->query("SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $offset, $per_page");
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$users = $db->resultSet();

// Get total count
$db->query("SELECT COUNT(*) as total FROM users $where_clause");
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$total_users = $db->single()->total;
$total_pages = ceil($total_users / $per_page);

include 'templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'templates/sidebar.php'; ?>

        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Users</h1>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo $_GET['search'] ?? ''; ?>" placeholder="Search users...">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <option value="super_admin" <?php echo ($_GET['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                <option value="organizer" <?php echo ($_GET['role'] ?? '') === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                <option value="attendee" <?php echo ($_GET['role'] ?? '') === 'attendee' ? 'selected' : ''; ?>>Attendee</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($_GET['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Users (<?php echo $total_users; ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No users found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user_item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                            
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user_item->name); ?></strong>
                                                    <?php if ($user_item->id == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info ms-1">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user_item->email); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user_item->role === 'super_admin' ? 'danger' : 
                                                     ($user_item->role === 'organizer' ? 'warning text-dark' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $user_item->role)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo getRelativeTime($user_item->created_at); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user_item->is_active ? 'success' : 'danger'; ?>">
                                                <?php echo $user_item->is_active ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="user-detail.php?id=<?php echo $user_item->id; ?>" 
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-outline-info dropdown-toggle" 
                                                            data-bs-toggle="dropdown" title="Actions">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($user_item->role === 'attendee' && $user_item->id != $_SESSION['user_id']): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="?action=make_organizer&id=<?php echo $user_item->id; ?>">
                                                                <i class="fas fa-user-tie me-2"></i>Make Organizer
                                                            </a>
                                                        </li>
                                                        <?php elseif ($user_item->role === 'organizer' && $user_item->id != $_SESSION['user_id']): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="?action=make_attendee&id=<?php echo $user_item->id; ?>">
                                                                <i class="fas fa-user me-2"></i>Make Attendee
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($user_item->is_active && $user_item->id != $_SESSION['user_id']): ?>
                                                        <li>
                                                            <a class="dropdown-item text-warning" href="?action=deactivate&id=<?php echo $user_item->id; ?>">
                                                                <i class="fas fa-user-slash me-2"></i>Deactivate
                                                            </a>
                                                        </li>
                                                        <?php elseif (!$user_item->is_active): ?>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="?action=activate&id=<?php echo $user_item->id; ?>">
                                                                <i class="fas fa-user-check me-2"></i>Activate
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        
                                                        <li><hr class="dropdown-divider"></li>
                                                        
                                                        <?php if ($user_item->id != $_SESSION['user_id']): ?>
                                                        <li>
                                                            <a class="dropdown-item text-danger" 
                                                               href="?action=delete&id=<?php echo $user_item->id; ?>" 
                                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Users pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['role']) ? '&role=' . $_GET['role'] : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['role']) ? '&role=' . $_GET['role'] : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['role']) ? '&role=' . $_GET['role'] : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>