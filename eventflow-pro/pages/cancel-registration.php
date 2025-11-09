<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['registration_id'])) {
    header('Location: my-registrations.php');
    exit;
}

$page_title = "Cancel Registration";
$registration_class = new Registration();
$event_class = new Event();

$registration_id = intval($_GET['registration_id']);
$registration = $registration_class->getById($registration_id);

// Check if registration exists and belongs to current user
if (!$registration || $registration->user_id != $_SESSION['user_id']) {
    header('Location: my-registrations.php');
    exit;
}

// Get event details
$event = $event_class->getById($registration->event_id);

// Check if event has already passed
$event_datetime = strtotime($event->date . ' ' . $event->start_time);
$is_past_event = $event_datetime < time();

// Check if registration is already cancelled or attended
$is_cancellable = in_array($registration->status, ['registered']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_cancellable) {
    try {
        $reason = trim($_POST['cancellation_reason'] ?? '');
        
        if (empty($reason)) {
            throw new Exception('Please provide a reason for cancellation');
        }

        // Cancel the registration
        if ($registration_class->cancel($registration_id, $_SESSION['user_id'], $reason)) {
            $success = 'Registration cancelled successfully!';
            // Refresh registration data
            $registration = $registration_class->getById($registration_id);
            $is_cancellable = false;
        } else {
            throw new Exception('Failed to cancel registration');
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
            <div class="card-header <?php echo $is_cancellable ? 'bg-warning text-dark' : 'bg-secondary text-white'; ?>">
                <h4 class="card-title mb-0 text-center">
                    <i class="fas fa-times-circle me-2"></i>
                    <?php echo $is_cancellable ? 'Cancel Registration' : 'Registration Status'; ?>
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
                </div>
                <?php endif; ?>

                <!-- Registration Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Registration Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><?php echo htmlspecialchars($event->title); ?></h6>
                                <div class="event-meta small text-muted">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas fa-calendar me-2"></i>
                                        <?php echo date('l, F j, Y', strtotime($event->date)); ?>
                                    </div>
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas fa-clock me-2"></i>
                                        <?php echo date('g:i A', strtotime($event->start_time)); ?>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <?php echo htmlspecialchars($event->location); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="registration-details">
                                    <strong>Ticket Number:</strong>
                                    <div class="ticket-number text-primary fw-bold">
                                        <?php echo $registration->ticket_number; ?>
                                    </div>
                                    
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <strong>Status:</strong>
                                            <span class="badge bg-<?php 
                                                echo $registration->status === 'registered' ? 'primary' : 
                                                     ($registration->status === 'cancelled' ? 'secondary' : 'success'); 
                                            ?>">
                                                <?php echo ucfirst($registration->status); ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <strong>Guests:</strong>
                                            <?php echo $registration->guests_count; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <strong>Registered:</strong><br>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($registration->registration_date)); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!$is_cancellable): ?>
                <!-- Already Cancelled or Cannot Cancel -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Registration Status</h6>
                    <p class="mb-0">
                        <?php if ($registration->status === 'cancelled'): ?>
                        This registration was cancelled on 
                        <strong><?php echo $registration->check_in_time ? date('F j, Y', strtotime($registration->check_in_time)) : 'a previous date'; ?></strong>.
                        <?php if ($registration->cancellation_reason): ?>
                        <br>Reason: "<?php echo htmlspecialchars($registration->cancellation_reason); ?>"
                        <?php endif; ?>
                        <?php elseif ($registration->status === 'attended'): ?>
                        This registration has already been marked as attended and cannot be cancelled.
                        <?php elseif ($is_past_event): ?>
                        This event has already passed and registrations can no longer be cancelled.
                        <?php else: ?>
                        This registration cannot be cancelled at this time.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="text-center">
                    <a href="my-registrations.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to My Registrations
                    </a>
                </div>

                <?php else: ?>
                <!-- Cancellation Form -->
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0 text-dark">
                            <i class="fas fa-exclamation-triangle me-2"></i>Cancellation Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-warning me-2"></i>Please Confirm Cancellation</h6>
                            <p class="mb-0">
                                You are about to cancel your registration for <strong><?php echo htmlspecialchars($event->title); ?></strong>. 
                                This action cannot be undone.
                            </p>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="cancellation_reason" class="form-label">
                                    <strong>Reason for Cancellation *</strong>
                                </label>
                                <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" 
                                          rows="4" required 
                                          placeholder="Please let us know why you're cancelling your registration..."><?php echo isset($_POST['cancellation_reason']) ? htmlspecialchars($_POST['cancellation_reason']) : ''; ?></textarea>
                                <div class="form-text">Your feedback helps us improve our events.</div>
                            </div>

                            <!-- Cancellation Policy -->
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-file-contract me-2"></i>Cancellation Policy</h6>
                                    <ul class="small mb-0">
                                        <li>Your spot will be made available to other attendees</li>
                                        <li>If you registered with guests, all guest spots will also be cancelled</li>
                                        <li>You will receive a confirmation email of the cancellation</li>
                                        <?php if ($event->price > 0): ?>
                                        <li>Refund processing may take 5-7 business days</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="text-center mt-4">
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn btn-danger btn-lg" 
                                            onclick="return confirm('Are you absolutely sure you want to cancel this registration? This action cannot be undone.')">
                                        <i class="fas fa-times-circle me-2"></i>Confirm Cancellation
                                    </button>
                                    <a href="my-registrations.php" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>Keep Registration
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Information -->
                <?php if ($is_cancellable && $event->price > 0): ?>
                <div class="alert alert-info">
                    <h6><i class="fas fa-credit-card me-2"></i>Payment Information</h6>
                    <p class="mb-0">
                        Since this was a paid event, your refund of <strong>$<?php echo number_format($event->price * (1 + $registration->guests_count), 2); ?></strong> 
                        will be processed within 5-7 business days. You will receive an email confirmation when the refund is initiated.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <i class="fas fa-headset fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">
                    Need help? <a href="contact.php">Contact our support team</a> for assistance.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.querySelector('form')?.addEventListener('submit', function(e) {
    const reason = document.getElementById('cancellation_reason').value.trim();
    
    if (reason.length < 10) {
        e.preventDefault();
        alert('Please provide a more detailed reason for cancellation (at least 10 characters).');
        return;
    }
    
    if (!confirm('Are you absolutely sure you want to cancel this registration?\n\nThis action cannot be undone and your spot will be given to someone else.')) {
        e.preventDefault();
        return;
    }
});

// Auto-save reason in case of page refresh
window.addEventListener('beforeunload', function() {
    const reason = document.getElementById('cancellation_reason')?.value;
    if (reason && reason.length > 0) {
        localStorage.setItem('cancellation_reason', reason);
    }
});

// Load saved reason on page load
window.addEventListener('load', function() {
    const savedReason = localStorage.getItem('cancellation_reason');
    const reasonField = document.getElementById('cancellation_reason');
    
    if (savedReason && reasonField && !reasonField.value) {
        reasonField.value = savedReason;
    }
    
    // Clear saved reason when leaving the page
    window.addEventListener('unload', function() {
        localStorage.removeItem('cancellation_reason');
    });
});
</script>

<?php include '../templates/footer.php'; ?>