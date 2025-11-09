<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user can view registrations (organizer or admin)
if (!$auth->hasRole('organizer') && !$auth->hasRole('super_admin')) {
    header('Location: dashboard.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Event Registrations";
$event_class = new Event();
$registration_class = new Registration();

$event_id = intval($_GET['id']);
$event = $event_class->getById($event_id);

// Check if event exists and user has permission
if (!$event) {
    header('Location: dashboard.php');
    exit;
}

// Check if user owns the event or is super admin
if ($event->user_id != $_SESSION['user_id'] && !$auth->hasRole('super_admin')) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables
$error = '';
$success = '';
$registrations = [];

try {
    // Get registrations for this event
    $registrations = $registration_class->getRegistrationsByEvent($event_id);
    
    // Handle actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['registration_id'])) {
            $registration_id = intval($_POST['registration_id']);
            
            switch ($_POST['action']) {
                case 'approve':
                    // In your system, there's no "pending" status, so approve might not be needed
                    $success = 'Registration is already active in this system.';
                    break;
                    
                case 'cancel':
                    if ($registration_class->updateStatus($registration_id, 'cancelled')) {
                        $success = 'Registration cancelled successfully!';
                    } else {
                        throw new Exception('Failed to cancel registration');
                    }
                    break;
                    
                case 'delete':
                    if ($registration_class->delete($registration_id)) {
                        $success = 'Registration deleted successfully!';
                    } else {
                        throw new Exception('Failed to delete registration');
                    }
                    break;
                    
                case 'check_in':
                    // Call checkIn without user_id parameter (organizer/admin checking someone in)
                    if ($registration_class->checkIn($registration_id)) {
                        $success = 'Attendee checked in successfully!';
                    } else {
                        throw new Exception('Failed to check in attendee');
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            
            // Refresh registrations after action
            $registrations = $registration_class->getRegistrationsByEvent($event_id);
        }
    }
    
    // Get stats
    $stats = $registration_class->getEventRegistrationStats($event_id);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

include '../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Event Registrations
                        </h4>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($event->title); ?></p>
                    </div>
                    <div class="btn-group">
                        <a href="event-detail.php?id=<?php echo $event_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i> View Event
                        </a>
                        <a href="edit-event.php?id=<?php echo $event_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-edit me-1"></i> Edit Event
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
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
                </div>
                <?php endif; ?>

                <!-- Event Summary -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><?php echo htmlspecialchars($event->title); ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('F j, Y', strtotime($event->date)); ?> 
                                    at <?php echo date('g:i A', strtotime($event->start_time)); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($event->venue_name ?: $event->location); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-primary mb-1"><?php echo $stats['total']; ?></h4>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-success mb-1"><?php echo $stats['confirmed']; ?></h4>
                                            <small class="text-muted">Active</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning mb-1"><?php echo $stats['checked_in']; ?></h4>
                                        <small class="text-muted">Attended</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0">Registrations (<?php echo count($registrations); ?>)</h5>
                    </div>
                    <div class="btn-group">
                        <a href="export-registrations.php?event_id=<?php echo $event_id; ?>&format=csv" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i> Export CSV
                        </a>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="printRegistrations()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Registrations Table -->
                <?php if (empty($registrations)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Registrations Yet</h4>
                    <p class="text-muted">Registrations will appear here when people sign up for your event.</p>
                    <a href="event-detail.php?id=<?php echo $event_id; ?>" class="btn btn-primary">
                        <i class="fas fa-share me-1"></i> Share Event
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="registrationsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Attendee</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Checked In</th>
                                <th>Ticket #</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $index => $registration): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $registration->user_avatar ?: '../assets/images/avatar.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($registration->user_name); ?>" 
                                             class="rounded-circle me-2" width="32" height="32">
                                        <div>
                                            <strong><?php echo htmlspecialchars($registration->user_name); ?></strong>
                                            <?php if ($registration->guests_count > 0): ?>
                                            <br><small class="text-muted">+<?php echo $registration->guests_count; ?> guests</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($registration->user_email); ?>">
                                        <?php echo htmlspecialchars($registration->user_email); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($registration->user_phone)): ?>
                                    <a href="tel:<?php echo htmlspecialchars($registration->user_phone); ?>">
                                        <?php echo htmlspecialchars($registration->user_phone); ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($registration->registration_date)); ?><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($registration->registration_date)); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($registration->status) {
                                            case 'registered': echo 'success'; break;
                                            case 'attended': echo 'primary'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($registration->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($registration->check_in_time): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>
                                        <?php echo date('M j, g:i A', strtotime($registration->check_in_time)); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Not checked in</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo $registration->ticket_number; ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <!-- Check In Button -->
                                        <?php if (!$registration->check_in_time && $registration->status === 'registered'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="registration_id" value="<?php echo $registration->id; ?>">
                                            <input type="hidden" name="action" value="check_in">
                                            <button type="submit" class="btn btn-outline-success" title="Check In">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <!-- Cancel Button -->
                                        <?php if ($registration->status !== 'cancelled'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="registration_id" value="<?php echo $registration->id; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-outline-warning" 
                                                    onclick="return confirm('Cancel this registration?')"
                                                    title="Cancel Registration">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="registration_id" value="<?php echo $registration->id; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this registration? This action cannot be undone.')"
                                                    title="Delete Registration">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<script>
function printRegistrations() {
    const table = document.getElementById('registrationsTable');
    const win = window.open('', '', 'height=700,width=1000');
    
    win.document.write(`
        <html>
            <head>
                <title>Registrations - <?php echo htmlspecialchars($event->title); ?></title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f8f9fa; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2><?php echo htmlspecialchars($event->title); ?></h2>
                    <h4>Registrations List</h4>
                    <p>Generated on: ${new Date().toLocaleDateString()}</p>
                </div>
                ${table.outerHTML}
            </body>
        </html>
    `);
    
    win.document.close();
    win.print();
}

// Add search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search registrations...';
    searchInput.className = 'form-control mb-3';
    searchInput.style.maxWidth = '300px';
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#registrationsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    const actionsBar = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-4');
    actionsBar.parentNode.insertBefore(searchInput, actionsBar.nextSibling);
});
</script>

<?php include '../templates/footer.php'; ?>