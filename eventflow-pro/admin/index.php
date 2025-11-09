<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('super_admin')) {
    header('Location: login.php');
    exit;
}

$page_title = "Admin Dashboard";
$event = new Event();
$user = new User();
$registration = new Registration();

// Get statistics
$db = new Database();
$db->query('SELECT COUNT(*) as total FROM users WHERE is_active = 1');
$total_users = $db->single()->total;

$db->query('SELECT COUNT(*) as total FROM events WHERE status = "published"');
$total_events = $db->single()->total;

$db->query('SELECT COUNT(*) as total FROM registrations WHERE status = "registered"');
$total_registrations = $db->single()->total;

$db->query('SELECT COUNT(*) as total FROM events WHERE status = "draft"');
$draft_events = $db->single()->total;

// Get recent activities - FIXED: changed registered_at to registration_date
$db->query('SELECT * FROM events ORDER BY created_at DESC LIMIT 5');
$recent_events = $db->resultSet();

$db->query('SELECT u.name, u.email, r.* FROM registrations r 
           JOIN users u ON r.user_id = u.id 
           ORDER BY r.registration_date DESC LIMIT 5'); // FIXED COLUMN NAME
$recent_registrations = $db->resultSet();

include 'templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'templates/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <!-- <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Print</button> -->
                    </div>
                    <a href="events.php?action=create" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> New Event
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Users
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Active Events
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_events; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Registrations
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_registrations; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Draft Events
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $draft_events; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-edit fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Events -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Events</h6>
                            <a href="events.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_events as $event_item): ?>
                                        <tr>
                                            <td>
                                                <a href="../pages/event-detail.php?id=<?php echo $event_item->id; ?>" class="text-decoration-none">
                                                    <?php echo truncateText($event_item->title, 30); ?>
                                                </a>
                                            </td>
                                            <td><?php echo formatDate($event_item->date, 'M j'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $event_item->status === 'published' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($event_item->status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Registrations</h6>
                            <a href="registrations.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Event</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_registrations as $reg): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reg->name); ?></td>
                                            <td>
                                                <?php 
                                                // Get event title
                                                $db->query('SELECT title FROM events WHERE id = :event_id');
                                                $db->bind(':event_id', $reg->event_id);
                                                $event = $db->single();
                                                echo truncateText($event->title ?? 'Event', 25); 
                                                ?>
                                            </td>
                                            <td><?php echo getRelativeTime($reg->registration_date); ?></td> <!-- FIXED COLUMN NAME -->
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-2 mb-3">
                                    <a href="../pages/create-event.php" class="btn btn-success w-100 h-100 py-3">
                                        <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                        Create Event
                                    </a>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <a href="users.php" class="btn btn-info w-100 h-100 py-3">
                                        <i class="fas fa-user-plus fa-2x mb-2"></i><br>
                                        Manage Users
                                    </a>
                                </div>
                                
                                <div class="col-md-2 mb-3">
                                    <a href="../pages/dashboard.php" class="btn btn-outline-primary w-100 h-100 py-3">
                                        <i class="fas fa-external-link-alt fa-2x mb-2"></i><br>
                                        Frontend
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>