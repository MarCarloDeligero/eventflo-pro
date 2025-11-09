<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('super_admin')) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = "Manage Events - Admin";
$event = new Event();
$db = new Database();

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete':
            if (isset($_GET['id'])) {
                $event_id = intval($_GET['id']);
                $db->query('DELETE FROM events WHERE id = :id');
                $db->bind(':id', $event_id);
                if ($db->execute()) {
                    $_SESSION['flash_message'] = 'Event deleted successfully';
                    $_SESSION['flash_type'] = 'success';
                }
            }
            break;
            
        case 'publish':
            if (isset($_GET['id'])) {
                $event_id = intval($_GET['id']);
                $db->query('UPDATE events SET status = "published" WHERE id = :id');
                $db->bind(':id', $event_id);
                if ($db->execute()) {
                    $_SESSION['flash_message'] = 'Event published successfully';
                    $_SESSION['flash_type'] = 'success';
                }
            }
            break;
            
        case 'unpublish':
            if (isset($_GET['id'])) {
                $event_id = intval($_GET['id']);
                $db->query('UPDATE events SET status = "draft" WHERE id = :id');
                $db->bind(':id', $event_id);
                if ($db->execute()) {
                    $_SESSION['flash_message'] = 'Event unpublished successfully';
                    $_SESSION['flash_type'] = 'success';
                }
            }
            break;
    }
    header('Location: events.php');
    exit;
}

// Get all events with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = [];
$params = [];

if (isset($_GET['status']) && in_array($_GET['status'], ['draft', 'published', 'cancelled'])) {
    $where_conditions[] = 'e.status = :status';
    $params[':status'] = $_GET['status'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = '(e.title LIKE :search OR e.description LIKE :search)';
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get events
$db->query("SELECT e.*, u.name as organizer_name, c.name as category_name,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'registered') as registered_count
           FROM events e 
           LEFT JOIN users u ON e.user_id = u.id 
           LEFT JOIN categories c ON e.category_id = c.id 
           $where_clause
           ORDER BY e.created_at DESC 
           LIMIT $offset, $per_page");

foreach ($params as $key => $value) {
    $db->bind($key, $value);
}

$events = $db->resultSet();

// Get total count for pagination
$db->query("SELECT COUNT(*) as total FROM events e $where_clause");
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$total_events = $db->single()->total;
$total_pages = ceil($total_events / $per_page);

include 'templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'templates/sidebar.php'; ?>

        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Events</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="../pages/create-event.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Event
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo $_GET['search'] ?? ''; ?>" placeholder="Search events...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo ($_GET['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="events.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Events Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Events (<?php echo $total_events; ?>)</h5>
                    <!-- <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                        </ul>
                    </div> -->
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Organizer</th>
                                    <th>Date</th>
                                    <th>Registrations</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No events found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($events as $event_item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event_item->title); ?></strong>
                                            <?php if ($event_item->is_featured): ?>
                                            <span class="badge bg-warning text-dark ms-1">Featured</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($event_item->category_name); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($event_item->organizer_name); ?></td>
                                        <td>
                                            <?php echo formatDate($event_item->date, 'M j, Y'); ?><br>
                                            <small class="text-muted"><?php echo formatTime($event_item->start_time); ?></small>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <?php
                                                $progress = calculateEventProgress($event_item->capacity, $event_item->registered_count);
                                                $progress_class = $progress >= 80 ? 'bg-danger' : ($progress >= 50 ? 'bg-warning' : 'bg-success');
                                                ?>
                                                <div class="progress-bar <?php echo $progress_class; ?>" 
                                                     style="width: <?php echo $progress; ?>%">
                                                    <?php echo $progress; ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $event_item->registered_count; ?>/<?php echo $event_item->capacity; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $event_item->status === 'published' ? 'success' : 
                                                     ($event_item->status === 'draft' ? 'secondary' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($event_item->status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../pages/event-detail.php?id=<?php echo $event_item->id; ?>" 
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../pages/edit-event.php?id=<?php echo $event_item->id; ?>" 
                                                   class="btn btn-outline-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-outline-info dropdown-toggle" 
                                                            data-bs-toggle="dropdown" title="More Actions">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($event_item->status === 'draft'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="?action=publish&id=<?php echo $event_item->id; ?>">
                                                                <i class="fas fa-check me-2"></i>Publish
                                                            </a>
                                                        </li>
                                                        <?php elseif ($event_item->status === 'published'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="?action=unpublish&id=<?php echo $event_item->id; ?>">
                                                                <i class="fas fa-times me-2"></i>Unpublish
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        <li>
                                                            <a class="dropdown-item" href="event-registrations.php?id=<?php echo $event_item->id; ?>">
                                                                <i class="fas fa-users me-2"></i>View Registrations
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" 
                                                               href="?action=delete&id=<?php echo $event_item->id; ?>" 
                                                               onclick="return confirm('Are you sure you want to delete this event?')">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </a>
                                                        </li>
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
                    <nav aria-label="Events pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>">Next</a>
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