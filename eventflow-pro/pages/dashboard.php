<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = "Dashboard";
$user = new User();
$event = new Event();
$registration = new Registration();

$current_user = $auth->getCurrentUser();

// Use getEventsByOrganizer instead of getByUser
$user_events = $event->getEventsByOrganizer($current_user->id);

// Use getUserRegistrations instead of getRegisteredEvents
$registered_events = $registration->getUserRegistrations($current_user->id);

// Get events count
$events_count = count($user_events);

include '../templates/header.php';
?>

<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $events_count; ?></h4>
                        <p class="mb-0">Events Created</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-plus fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo count($registered_events); ?></h4>
                        <p class="mb-0">Events Attending</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-ticket-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo date('M Y'); ?></h4>
                        <p class="mb-0">Current Month</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">Active</h4>
                        <p class="mb-0">Status</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- My Events -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar me-2"></i>My Events
                </h5>
                <?php if ($auth->hasRole('organizer') || $auth->hasRole('super_admin')): ?>
                <a href="create-event.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Create New
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($user_events)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-plus fa-2x text-muted mb-3"></i>
                    <p class="text-muted">You haven't created any events yet.</p>
                    <?php if ($auth->hasRole('organizer') || $auth->hasRole('super_admin')): ?>
                    <a href="create-event.php" class="btn btn-primary">
                        Create Your First Event
                    </a>
                    <?php else: ?>
                    <p class="text-muted small">Contact admin to become an event organizer.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($user_events, 0, 5) as $event_item): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($event_item->title); ?></h6>
                            <small class="text-muted">
                                <?php echo date('M j, Y', strtotime($event_item->date)); ?> • 
                                <?php echo $event_item->registered_count ?? 0; ?> registrations
                            </small>
                        </div>
                        <span class="badge bg-<?php echo $event_item->status === 'published' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($event_item->status); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($user_events) > 5): ?>
                <div class="text-center mt-3">
                    <a href="events.php?filter=my_events" class="btn btn-outline-primary btn-sm">View All Events</a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Registered Events -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-ticket-alt me-2"></i>My Registrations
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($registered_events)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-ticket-alt fa-2x text-muted mb-3"></i>
                    <p class="text-muted">You haven't registered for any events yet.</p>
                    <a href="events.php" class="btn btn-primary">Browse Events</a>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($registered_events, 0, 5) as $registration): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($registration->event_title); ?></h6>
                            <small class="text-muted">
                                <?php echo date('M j, Y', strtotime($registration->event_date)); ?> • 
                                Ticket: <?php echo $registration->ticket_number; ?>
                            </small>
                        </div>
                        <span class="badge bg-<?php echo $registration->status === 'registered' ? 'primary' : 'success'; ?>">
                            <?php echo ucfirst($registration->status); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($registered_events) > 5): ?>
                <div class="text-center mt-3">
                    <a href="my-registrations.php" class="btn btn-outline-primary btn-sm">View All Registrations</a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="events.php" class="btn btn-outline-primary w-100 h-100 py-3">
                            <i class="fas fa-search fa-2x mb-2"></i><br>
                            Browse Events
                        </a>
                    </div>
                    <?php if ($auth->hasRole('organizer') || $auth->hasRole('super_admin')): ?>
                    <div class="col-md-3">
                        <a href="create-event.php" class="btn btn-outline-success w-100 h-100 py-3">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                            Create Event
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <a href="profile.php" class="btn btn-outline-info w-100 h-100 py-3">
                            <i class="fas fa-user-edit fa-2x mb-2"></i><br>
                            Edit Profile
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="my-registrations.php" class="btn btn-outline-warning w-100 h-100 py-3">
                            <i class="fas fa-ticket-alt fa-2x mb-2"></i><br>
                            My Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>