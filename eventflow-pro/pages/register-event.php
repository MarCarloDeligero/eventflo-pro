<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!isset($_GET['event_id'])) {
    header('Location: events.php');
    exit;
}

$page_title = "Register for Event";
$event_class = new Event();
$registration_class = new Registration();

$event_id = intval($_GET['event_id']);
$event = $event_class->getById($event_id);

if (!$event) {
    header('Location: events.php');
    exit;
}

// Check if user is already registered
if ($event_class->isUserRegistered($event_id, $_SESSION['user_id'])) {
    header('Location: event-detail.php?id=' . $event_id);
    exit;
}

// Check available spots
$available_spots = $event_class->getAvailableSpots($event_id);
if ($available_spots <= 0) {
    header('Location: event-detail.php?id=' . $event_id);
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $guests_count = intval($_POST['guests_count'] ?? 0);
        
        // Check if enough spots available including guests
        if ($guests_count + 1 > $available_spots) {
            throw new Exception('Not enough spots available for your registration plus guests');
        }

        // Register for event
        $result = $registration_class->register($event_id, $_SESSION['user_id'], $guests_count);
        
        if ($result['status'] === 'registered') {
            $success = 'Successfully registered for the event!';
            // Redirect to registration confirmation after 2 seconds
            header('Refresh: 2; URL=registration-confirmation.php?registration_id=' . $result['registration_id']);
        } else {
            $success = 'You have been added to the waitlist. We will notify you if a spot becomes available.';
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
                <h4 class="card-title mb-0">
                    <i class="fas fa-ticket-alt me-2"></i>Register for Event
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
                    <?php if (strpos($success, 'registered') !== false): ?>
                    <p class="mb-0 mt-2">Redirecting to confirmation page...</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Event Summary -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <?php if ($event->image): ?>
                                <img src="<?php echo UPLOAD_URL . $event->image; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($event->title); ?>" style="max-height: 80px;">
                                <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="fas fa-calendar-alt fa-2x text-white"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-10">
                                <h5 class="mb-1"><?php echo htmlspecialchars($event->title); ?></h5>
                                <div class="row text-muted small">
                                    <div class="col-md-4">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M j, Y', strtotime($event->date)); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('g:i A', strtotime($event->start_time)); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($event->location); ?>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-primary">
                                        <?php echo $available_spots; ?> spots available
                                    </span>
                                    <?php if ($event->price > 0): ?>
                                    <span class="badge bg-warning text-dark ms-1">
                                        $<?php echo number_format($event->price, 2); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-success ms-1">Free</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!$success): ?>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Attendee Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Attendee Information</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $user = $auth->getCurrentUser();
                                    ?>
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user->name); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user->email); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="guests_count" class="form-label">Number of Guests</label>
                                        <select class="form-select" id="guests_count" name="guests_count">
                                            <?php for ($i = 0; $i <= min(5, $available_spots - 1); $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> guest<?php echo $i != 1 ? 's' : ''; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="form-text">Maximum <?php echo min(5, $available_spots - 1); ?> guests allowed</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Registration Summary -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Registration Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Your Registration:</span>
                                        <span><?php echo $event->price > 0 ? '$' . number_format($event->price, 2) : 'Free'; ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Guests (<span id="guests-display">0</span>):</span>
                                        <span id="guests-cost">$0.00</span>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span id="total-cost"><?php echo $event->price > 0 ? '$' . number_format($event->price, 2) : 'Free'; ?></span>
                                    </div>
                                    
                                    <?php if ($event->price > 0): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Payment will be processed after registration confirmation.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Terms -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" target="_blank">terms and conditions</a> and 
                                            understand that I may receive event-related communications.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="event-detail.php?id=<?php echo $event_id; ?>" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Event
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Complete Registration
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Update cost display when guests change
document.getElementById('guests_count').addEventListener('change', function() {
    const guests = parseInt(this.value);
    const price = <?php echo $event->price; ?>;
    
    document.getElementById('guests-display').textContent = guests;
    
    if (price > 0) {
        const guestsCost = guests * price;
        const totalCost = price + guestsCost;
        
        document.getElementById('guests-cost').textContent = '$' + guestsCost.toFixed(2);
        document.getElementById('total-cost').textContent = '$' + totalCost.toFixed(2);
    } else {
        document.getElementById('guests-cost').textContent = 'Free';
        document.getElementById('total-cost').textContent = 'Free';
    }
});
</script>

<?php include '../templates/footer.php'; ?>