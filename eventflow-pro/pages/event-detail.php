<?php
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: events.php');
    exit;
}

$page_title = "Event Details";
$event_class = new Event();
$auth = new Auth();
$registration_class = new Registration();

$event_id = intval($_GET['id']);
$event = $event_class->getById($event_id);

if (!$event) {
    header('Location: events.php');
    exit;
}

$page_title = $event->title . " - Event Details";
$available_spots = $event_class->getAvailableSpots($event_id);
$is_registered = $auth->isLoggedIn() ? $event_class->isUserRegistered($event_id, $_SESSION['user_id']) : false;
$is_organizer = $auth->isLoggedIn() && ($event->user_id == $_SESSION['user_id'] || $auth->hasRole('super_admin'));

// Safe date formatting functions
function formatDateSafe($dateString) {
    if (empty($dateString) || $dateString === '0000-00-00' || $dateString === '0000-00-00 00:00:00') {
        return 'Not specified';
    }
    return date('l, F j, Y', strtotime($dateString));
}

function formatTimeSafe($timeString) {
    if (empty($timeString) || $timeString === '00:00:00') {
        return 'Not specified';
    }
    return date('g:i A', strtotime($timeString));
}

function isRegistrationDeadlinePassed($deadline) {
    if (empty($deadline) || $deadline === '0000-00-00' || $deadline === '0000-00-00 00:00:00') {
        return false;
    }
    return strtotime($deadline) < time();
}

include '../templates/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Event Header -->
        <div class="card mb-4">
            <?php if ($event->image && file_exists(UPLOAD_PATH . $event->image)): ?>
            <img src="<?php echo UPLOAD_URL . $event->image; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event->title); ?>" style="max-height: 400px; object-fit: cover;">
            <?php elseif ($event->image): ?>
            <img src="<?php echo UPLOAD_URL . $event->image; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event->title); ?>" style="max-height: 400px; object-fit: cover;" onerror="this.style.display='none'">
            <?php endif; ?>
            
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <?php if (!empty($event->category_name)): ?>
                        <span class="badge rounded-pill mb-2" style="background-color: <?php echo !empty($event->category_color) ? $event->category_color : '#6c757d'; ?>">
                            <?php echo htmlspecialchars($event->category_name); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($event->is_featured): ?>
                        <span class="badge bg-warning text-dark ms-1">
                            <i class="fas fa-star me-1"></i>Featured
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($event->price > 0): ?>
                        <span class="badge bg-primary ms-1">
                            <i class="fas fa-tag me-1"></i>$<?php echo number_format($event->price, 2); ?>
                        </span>
                        <?php else: ?>
                        <span class="badge bg-success ms-1">Free</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_organizer): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Manage
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="edit-event.php?id=<?php echo $event->id; ?>">
                                <i class="fas fa-edit me-2"></i>Edit Event
                            </a></li>
                            <li><a class="dropdown-item" href="event-registrations.php?id=<?php echo $event->id; ?>">
                                <i class="fas fa-users me-2"></i>View Registrations
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="delete-event.php?id=<?php echo $event->id; ?>" onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')">
                                <i class="fas fa-trash me-2"></i>Delete Event
                            </a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                
                <h1 class="card-title h2"><?php echo htmlspecialchars($event->title); ?></h1>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary rounded-circle p-2 me-3">
                                <i class="fas fa-calendar text-white"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Date</small>
                                <strong><?php echo formatDateSafe($event->date); ?></strong>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success rounded-circle p-2 me-3">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Time</small>
                                <strong>
                                    <?php echo formatTimeSafe($event->start_time); ?> - <?php echo formatTimeSafe($event->end_time); ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning rounded-circle p-2 me-3">
                                <i class="fas fa-map-marker-alt text-white"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Location</small>
                                <strong><?php echo !empty($event->location) ? htmlspecialchars($event->location) : 'Location not specified'; ?></strong>
                                <?php if (!empty($event->venue_name)): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($event->venue_name); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info rounded-circle p-2 me-3">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Capacity</small>
                                <strong>
                                    <?php echo $available_spots; ?> spots available of <?php echo $event->capacity; ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Event Description -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>About This Event
                </h5>
            </div>
            <div class="card-body">
                <div class="event-description">
                    <?php echo !empty($event->description) ? nl2br(htmlspecialchars($event->description)) : '<p class="text-muted">No description provided.</p>'; ?>
                </div>
            </div>
        </div>
        
        <!-- Organizer Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>Organizer
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <?php
                    $organizer_avatar = '../assets/images/avatar.png';
                    if (!empty($event->organizer_avatar) && file_exists(UPLOAD_PATH . $event->organizer_avatar)) {
                        $organizer_avatar = UPLOAD_URL . $event->organizer_avatar;
                    }
                    ?>
                    <img src="<?php echo $organizer_avatar; ?>" 
                         alt="<?php echo htmlspecialchars($event->organizer_name); ?>" 
                         class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                    <div>
                        <h6 class="mb-1"><?php echo !empty($event->organizer_name) ? htmlspecialchars($event->organizer_name) : 'Unknown Organizer'; ?></h6>
                        <p class="text-muted mb-1">Event Organizer</p>
                        <small class="text-muted">
                            <i class="fas fa-envelope me-1"></i>
                            <?php echo !empty($event->organizer_email) ? htmlspecialchars($event->organizer_email) : 'No email available'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Registration Panel -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-ticket-alt me-2"></i>Registration
                </h5>
            </div>
            <div class="card-body">
                <?php if ($event->status !== 'published'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This event is not currently accepting registrations.
                </div>
                <?php elseif (isRegistrationDeadlinePassed($event->registration_deadline)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-clock me-2"></i>
                    Registration deadline has passed.
                </div>
                <?php elseif ($available_spots <= 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-users me-2"></i>
                    This event is fully booked. Join the waitlist!
                </div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <?php if ($event->price > 0): ?>
                    <h3 class="text-primary">$<?php echo number_format($event->price, 2); ?></h3>
                    <p class="text-muted">Per ticket</p>
                    <?php else: ?>
                    <h3 class="text-success">Free</h3>
                    <p class="text-muted">No cost to attend</p>
                    <?php endif; ?>
                </div>
                
                <div class="d-grid gap-2">
                    <?php if (!$auth->isLoggedIn()): ?>
                    <a href="login.php?redirect=event-detail.php?id=<?php echo $event->id; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Register
                    </a>
                    <small class="text-muted text-center">Don't have an account? <a href="register.php">Sign up</a></small>
                    
                    <?php elseif ($is_registered): ?>
                    <button class="btn btn-success btn-lg" disabled>
                        <i class="fas fa-check me-2"></i>Already Registered
                    </button>
                    <a href="my-registrations.php" class="btn btn-outline-primary">
                        View My Registration
                    </a>
                    
                    <?php elseif ($available_spots > 0 && $event->status === 'published' && !isRegistrationDeadlinePassed($event->registration_deadline)): ?>
                    <a href="register-event.php?event_id=<?php echo $event->id; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-ticket-alt me-2"></i>Register Now
                    </a>
                    <small class="text-muted text-center">
                        <?php echo $available_spots; ?> spots remaining
                    </small>
                    
                    <?php elseif ($available_spots <= 0): ?>
                    <button class="btn btn-secondary btn-lg" disabled>
                        <i class="fas fa-clock me-2"></i>Join Waitlist
                    </button>
                    <small class="text-muted text-center">
                        You'll be notified if spots become available
                    </small>
                    <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>
                        <i class="fas fa-ban me-2"></i>Registration Closed
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if ($auth->isLoggedIn() && !$is_registered && $available_spots > 0 && $event->status === 'published' && !isRegistrationDeadlinePassed($event->registration_deadline)): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Your registration is secure and you can cancel anytime
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Event Stats -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Event Stats
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="text-primary mb-1"><?php echo $event->capacity - $available_spots; ?></h4>
                            <small class="text-muted">Registered</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1"><?php echo $available_spots; ?></h4>
                        <small class="text-muted">Available</small>
                    </div>
                </div>
                <hr>
                <div class="small text-muted">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Total Capacity:</span>
                        <span><?php echo $event->capacity; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Registration Rate:</span>
                        <span>
                            <?php 
                            $registration_rate = $event->capacity > 0 ? round((($event->capacity - $available_spots) / $event->capacity) * 100) : 0;
                            echo $registration_rate; ?>%
                        </span>
                    </div>
                    <?php if (!empty($event->registration_deadline) && $event->registration_deadline !== '0000-00-00' && $event->registration_deadline !== '0000-00-00 00:00:00'): ?>
                    <div class="d-flex justify-content-between">
                        <span>Registration Deadline:</span>
                        <span><?php echo date('M j, Y', strtotime($event->registration_deadline)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Share Event -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-share-alt me-2"></i>Share This Event
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-around">
                    <?php
                    $current_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                    $event_title = urlencode($event->title);
                    ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $current_url; ?>&text=<?php echo $event_title; ?>" target="_blank" class="btn btn-outline-info btn-sm">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-danger btn-sm" onclick="alert('Copy the event URL to share on Instagram')">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo $event_title . ' ' . $current_url; ?>" target="_blank" class="btn btn-outline-success btn-sm">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="#" class="btn btn-outline-dark btn-sm" onclick="copyToClipboard('<?php echo "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>')">
                        <i class="fas fa-link"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Event link copied to clipboard!');
    }, function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Event link copied to clipboard!');
    });
}
</script>

<?php include '../templates/footer.php'; ?>