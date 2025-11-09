<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user can delete events
if (!$auth->hasRole('organizer') && !$auth->hasRole('super_admin')) {
    header('Location: dashboard.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Delete Event";
$event = new Event();
$error = '';
$success = '';

$event_id = intval($_GET['id']);
$current_event = $event->getById($event_id);

// Check if event exists
if (!$current_event) {
    header('Location: dashboard.php');
    exit;
}

// Check if user owns the event or is super admin
if ($current_event->user_id != $_SESSION['user_id'] && !$auth->hasRole('super_admin')) {
    header('Location: dashboard.php');
    exit;
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user confirmed deletion
        if (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'yes') {
            throw new Exception('Deletion not confirmed');
        }

        // Get registration count before deletion
        $registration_count = $event->getRegistrationCount($event_id);

        // Delete event
        if ($event->delete($event_id)) {
            // Delete event image if exists
            if ($current_event->image && file_exists(UPLOAD_PATH . $current_event->image)) {
                unlink(UPLOAD_PATH . $current_event->image);
            }

            $success = 'Event deleted successfully!';
            
            // Redirect after 2 seconds
            header('Refresh: 2; URL=dashboard.php');
        } else {
            throw new Exception('Failed to delete event');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0 text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Event
                </h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <p class="mb-0">Redirecting to dashboard...</p>
                </div>
                <?php else: ?>

                <!-- Event Summary -->
                <div class="card border-danger mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">Event to Delete</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <?php if ($current_event->image): ?>
                                <img src="<?php echo UPLOAD_URL . $current_event->image; ?>" 
                                     class="img-fluid rounded" 
                                     alt="Event image" 
                                     style="max-height: 80px;">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-calendar-alt text-muted fa-2x"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-10">
                                <h5><?php echo htmlspecialchars($current_event->title); ?></h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('F j, Y', strtotime($current_event->date)); ?> 
                                    at <?php echo date('g:i A', strtotime($current_event->start_time)); ?>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($current_event->venue_name ?: $current_event->location); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-users me-1"></i>
                                    Event ID: <?php echo $current_event->id; ?> | 
                                    Created: <?php echo date('M j, Y', strtotime($current_event->created_at)); ?> |
                                    Status: <span class="badge bg-<?php echo $current_event->status === 'published' ? 'success' : ($current_event->status === 'draft' ? 'secondary' : 'danger'); ?>">
                                        <?php echo ucfirst($current_event->status); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warning Message -->
                <div class="alert alert-warning">
                    <h5 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Warning: This action cannot be undone!
                    </h5>
                    <p class="mb-2">Deleting this event will permanently remove:</p>
                    <ul class="mb-2">
                        <li>Event details and description</li>
                        <li>Event image</li>
                        <li>All registration records for this event</li>
                        <li>Any associated data (waitlists, etc.)</li>
                    </ul>
                    <?php $registration_count = $event->getRegistrationCount($event_id); ?>
                    <p class="mb-0"><strong><?php echo $registration_count; ?> attendees</strong> are currently registered for this event.</p>
                </div>

                <!-- Alternative Options -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Consider These Alternatives</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-grid">
                                    <a href="edit-event.php?id=<?php echo $event_id; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i> Edit Event Instead
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid">
                                    <a href="event-detail.php?id=<?php echo $event_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-eye me-1"></i> View Event Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <form method="POST" action="">
                    <div class="card border-danger">
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" value="yes" required>
                                <label class="form-check-label text-danger fw-bold" for="confirm_delete">
                                    I understand that this action cannot be undone and I want to permanently delete this event
                                </label>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This will permanently delete the event and all its data.')">
                                    <i class="fas fa-trash me-1"></i> Permanently Delete Event
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>