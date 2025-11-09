<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = "My Events";
$event = new Event();
$registration = new Registration();

$current_user = $auth->getCurrentUser();

// Get events created by user
$created_events = $event->getEventsByOrganizer($current_user->id);

// Get events user has registered for
$registered_events = $registration->getUserRegistrations($current_user->id);

include '../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="eventsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="created-tab" data-bs-toggle="tab" data-bs-target="#created" type="button" role="tab">
                    <i class="fas fa-calendar-plus me-2"></i>Events I Created
                    <span class="badge bg-primary ms-2"><?php echo count($created_events); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="registered-tab" data-bs-toggle="tab" data-bs-target="#registered" type="button" role="tab">
                    <i class="fas fa-ticket-alt me-2"></i>Events I'm Attending
                    <span class="badge bg-success ms-2"><?php echo count($registered_events); ?></span>
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="eventsTabContent">
            <!-- Created Events Tab -->
            <div class="tab-pane fade show active" id="created" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">
                                <i class="fas fa-calendar-plus me-2"></i>Events I Created
                            </h4>
                            <p class="text-muted mb-0">Manage your created events</p>
                        </div>
                        <?php if ($auth->hasRole('organizer') || $auth->hasRole('super_admin')): ?>
                        <a href="create-event.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Event
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($created_events)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Events Created</h4>
                            <p class="text-muted">You haven't created any events yet.</p>
                            <?php if ($auth->hasRole('organizer') || $auth->hasRole('super_admin')): ?>
                            <a href="create-event.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Your First Event
                            </a>
                            <?php else: ?>
                            <p class="text-muted small">Contact admin to become an event organizer.</p>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Event Title</th>
                                        <th>Date & Time</th>
                                        <th>Location</th>
                                        <th>Registrations</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($created_events as $event_item): 
                                        $available_spots = $event->getAvailableSpots($event_item->id);
                                        $registration_count = $event->getRegistrationCount($event_item->id);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($event_item->image): ?>
                                                <img src="<?php echo UPLOAD_URL . $event_item->image; ?>" 
                                                     alt="<?php echo htmlspecialchars($event_item->title); ?>" 
                                                     class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                                                <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-calendar-alt text-muted"></i>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($event_item->title); ?></h6>
                                                    <small class="text-muted">
                                                        <?php if ($event_item->price > 0): ?>
                                                        $<?php echo number_format($event_item->price, 2); ?>
                                                        <?php else: ?>
                                                        Free
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($event_item->date)); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo date('g:i A', strtotime($event_item->start_time)); ?> - 
                                                    <?php echo date('g:i A', strtotime($event_item->end_time)); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($event_item->venue_name ?: $event_item->location); ?></small>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <strong class="d-block"><?php echo $registration_count; ?>/<?php echo $event_item->capacity; ?></strong>
                                                <small class="text-muted">Registrations</small>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar <?php echo $registration_count >= $event_item->capacity ? 'bg-danger' : 'bg-success'; ?>" 
                                                         style="width: <?php echo min(100, ($registration_count / $event_item->capacity) * 100); ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($event_item->status) {
                                                    case 'published': echo 'success'; break;
                                                    case 'draft': echo 'secondary'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($event_item->status); ?>
                                            </span>
                                            <?php if ($event_item->is_featured): ?>
                                            <span class="badge bg-warning text-dark ms-1">
                                                <i class="fas fa-star me-1"></i>Featured
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="event-detail.php?id=<?php echo $event_item->id; ?>" class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-event.php?id=<?php echo $event_item->id; ?>" class="btn btn-outline-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="event-registrations.php?id=<?php echo $event_item->id; ?>" class="btn btn-outline-info" title="Registrations">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <?php if ($auth->hasRole('super_admin') || $event_item->user_id == $_SESSION['user_id']): ?>
                                                <a href="delete-event.php?id=<?php echo $event_item->id; ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this event?')"
                                                   title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Registered Events Tab -->
            <div class="tab-pane fade" id="registered" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-ticket-alt me-2"></i>Events I'm Attending
                        </h4>
                        <p class="text-muted mb-0">Events you have registered for</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registered_events)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Event Registrations</h4>
                            <p class="text-muted">You haven't registered for any events yet.</p>
                            <a href="events.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Browse Events
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Event Title</th>
                                        <th>Date & Time</th>
                                        <th>Location</th>
                                        <th>Ticket #</th>
                                        <th>Registration Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registered_events as $reg): 
                                        $event_details = $event->getById($reg->event_id);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($event_details->image): ?>
                                                <img src="<?php echo UPLOAD_URL . $event_details->image; ?>" 
                                                     alt="<?php echo htmlspecialchars($event_details->title); ?>" 
                                                     class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                                                <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-calendar-alt text-muted"></i>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($event_details->title); ?></h6>
                                                    <small class="text-muted">
                                                        <?php if ($event_details->price > 0): ?>
                                                        $<?php echo number_format($event_details->price, 2); ?>
                                                        <?php else: ?>
                                                        Free
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($event_details->date)); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo date('g:i A', strtotime($event_details->start_time)); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($event_details->venue_name ?: $event_details->location); ?></small>
                                        </td>
                                        <td>
                                            <code class="small"><?php echo $reg->ticket_number; ?></code>
                                            <?php if ($reg->guests_count > 0): ?>
                                            <br><small class="text-muted">+<?php echo $reg->guests_count; ?> guests</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($reg->status) {
                                                    case 'registered': echo 'primary'; break;
                                                    case 'attended': echo 'success'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                    case 'waitlisted': echo 'secondary'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($reg->status); ?>
                                            </span>
                                            <?php if (isset($reg->check_in_time) && $reg->check_in_time): ?>
                                            <br><small class="text-muted">Checked in: <?php echo date('M j, g:i A', strtotime($reg->check_in_time)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="event-detail.php?id=<?php echo $reg->event_id; ?>" class="btn btn-outline-primary" title="View Event">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="registration-details.php?id=<?php echo $reg->id; ?>" class="btn btn-outline-info" title="Registration Details">
                                                    <i class="fas fa-ticket-alt"></i>
                                                </a>
                                                <?php if ($reg->status === 'registered'): ?>
                                                <a href="cancel-registration.php?id=<?php echo $reg->id; ?>" 
                                                   class="btn btn-outline-warning"
                                                   onclick="return confirm('Are you sure you want to cancel your registration?')"
                                                   title="Cancel Registration">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tab functionality
document.addEventListener('DOMContentLoaded', function() {
    // Store the active tab in sessionStorage
    const tabEl = document.querySelector('button[data-bs-toggle="tab"]');
    if (tabEl) {
        tabEl.addEventListener('shown.bs.tab', function (event) {
            const activeTab = event.target.getAttribute('data-bs-target');
            sessionStorage.setItem('activeEventsTab', activeTab);
        });
    }

    // Retrieve active tab from sessionStorage
    const activeTab = sessionStorage.getItem('activeEventsTab');
    if (activeTab) {
        const triggerEl = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
    }
});
</script>

<?php include '../templates/footer.php'; ?>