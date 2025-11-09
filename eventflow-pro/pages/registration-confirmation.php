<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['registration_id'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Registration Confirmation";
$registration_class = new Registration();
$event_class = new Event();

$registration_id = intval($_GET['registration_id']);
$registration = $registration_class->getById($registration_id);

// Check if registration exists and belongs to current user
if (!$registration || $registration->user_id != $_SESSION['user_id']) {
    header('Location: dashboard.php');
    exit;
}

// Get event details
$event = $event_class->getById($registration->event_id);

include '../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="card-title mb-0 text-center">
                    <i class="fas fa-check-circle me-2"></i>Registration Confirmed!
                </h4>
            </div>
            <div class="card-body">
                <!-- Success Header -->
                <div class="text-center mb-4">
                    <div class="success-icon mb-3">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h3 class="text-success">You're Registered!</h3>
                    <p class="lead">Your registration for <strong><?php echo htmlspecialchars($event->title); ?></strong> has been confirmed.</p>
                </div>

                <!-- Registration Details -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-ticket-alt me-2"></i>Registration Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Ticket Number:</strong>
                                    <div class="ticket-number display-6 text-primary fw-bold">
                                        <?php echo $registration->ticket_number; ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Status:</strong>
                                        <span class="badge bg-<?php echo $registration->status === 'registered' ? 'success' : 'primary'; ?>">
                                            <?php echo ucfirst($registration->status); ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Guests:</strong>
                                        <?php echo $registration->guests_count; ?>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <strong>Registration Date:</strong><br>
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($registration->registration_date)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar me-2"></i>Event Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($event->title); ?></h6>
                                
                                <div class="event-meta small">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-calendar text-primary me-2"></i>
                                        <?php echo date('l, F j, Y', strtotime($event->date)); ?>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock text-success me-2"></i>
                                        <?php echo date('g:i A', strtotime($event->start_time)); ?> - 
                                        <?php echo date('g:i A', strtotime($event->end_time)); ?>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-warning me-2"></i>
                                        <?php echo htmlspecialchars($event->location); ?>
                                        <?php if ($event->venue_name): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($event->venue_name); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($event->price > 0): ?>
                                <div class="mt-3">
                                    <strong>Amount Paid:</strong>
                                    <div class="h5 text-success">$<?php echo number_format($event->price * (1 + $registration->guests_count), 2); ?></div>
                                    <small class="text-muted">
                                        ($<?php echo number_format($event->price, 2); ?> per ticket × <?php echo 1 + $registration->guests_count; ?> tickets)
                                    </small>
                                </div>
                                <?php else: ?>
                                <div class="mt-3">
                                    <strong>Cost:</strong>
                                    <span class="badge bg-success">Free</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-check me-2"></i>What's Next?
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="next-step">
                                    <i class="fas fa-calendar-plus fa-2x text-primary mb-2"></i>
                                    <h6>Add to Calendar</h6>
                                    <p class="small text-muted">Save the event to your calendar</p>
                                    <button class="btn btn-outline-primary btn-sm" onclick="addToCalendar()">
                                        <i class="fas fa-download me-1"></i>Download .ics
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="next-step">
                                    <i class="fas fa-share-alt fa-2x text-info mb-2"></i>
                                    <h6>Share with Friends</h6>
                                    <p class="small text-muted">Let others know about this event</p>
                                    <div class="btn-group">
                                        <button class="btn btn-outline-info btn-sm" onclick="shareEvent()">
                                            <i class="fas fa-share me-1"></i>Share
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="next-step">
                                    <i class="fas fa-qrcode fa-2x text-success mb-2"></i>
                                    <h6>Your Ticket</h6>
                                    <p class="small text-muted">Save this for event check-in</p>
                                    <button class="btn btn-outline-success btn-sm" onclick="generateTicket()">
                                        <i class="fas fa-ticket-alt me-1"></i>View Ticket
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Information -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                    <ul class="mb-0">
                        <li>Please save your ticket number <strong><?php echo $registration->ticket_number; ?></strong> for check-in</li>
                        <li>Arrive 15 minutes before the event starts</li>
                        <li>Bring a valid ID for verification</li>
                        <?php if ($registration->guests_count > 0): ?>
                        <li>Your <?php echo $registration->guests_count; ?> guest(s) are also registered</li>
                        <?php endif; ?>
                        <?php if ($event->registration_deadline): ?>
                        <li>Registration deadline: <?php echo date('F j, Y \a\t g:i A', strtotime($event->registration_deadline)); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="text-center mt-4">
                    <div class="btn-group" role="group">
                        <a href="event-detail.php?id=<?php echo $event->id; ?>" class="btn btn-primary">
                            <i class="fas fa-calendar me-2"></i>View Event Page
                        </a>
                        <a href="my-registrations.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>My Registrations
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </div>
                    
                    <div class="mt-3">
                        <button class="btn btn-outline-success" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Confirmation
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Confirmation Notice -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <i class="fas fa-envelope fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">
                    A confirmation email has been sent to <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .navbar, .btn, .card-header, .next-step .btn, .alert, .mt-4 .card {
        display: none !important;
    }
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    .ticket-number {
        font-size: 24px !important;
    }
}
</style>

<script>
// Add to Calendar function
function addToCalendar() {
    const eventDetails = {
        title: "<?php echo addslashes($event->title); ?>",
        description: "<?php echo addslashes($event->short_description ?: $event->description); ?>",
        location: "<?php echo addslashes($event->location); ?>",
        startTime: "<?php echo $event->date . 'T' . $event->start_time; ?>",
        endTime: "<?php echo $event->date . 'T' . $event->end_time; ?>"
    };

    // Create .ics file content
    const icsContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'BEGIN:VEVENT',
        'SUMMARY:' + eventDetails.title,
        'DESCRIPTION:' + eventDetails.description,
        'LOCATION:' + eventDetails.location,
        'DTSTART:' + eventDetails.startTime.replace(/[-:]/g, ''),
        'DTEND:' + eventDetails.endTime.replace(/[-:]/g, ''),
        'UID:' + "<?php echo $registration->ticket_number; ?>",
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\n');

    // Download .ics file
    const blob = new Blob([icsContent], { type: 'text/calendar' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '<?php echo $registration->ticket_number; ?>.ics';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Share event function
function shareEvent() {
    const eventUrl = window.location.origin + '/eventflow-pro/pages/event-detail.php?id=<?php echo $event->id; ?>';
    const text = "I'm attending <?php echo addslashes($event->title); ?> on <?php echo date('F j, Y', strtotime($event->date)); ?>. Join me!";
    
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($event->title); ?>',
            text: text,
            url: eventUrl
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(text + ' ' + eventUrl).then(function() {
            alert('Event details copied to clipboard!');
        });
    }
}

// Generate ticket view
function generateTicket() {
    const ticketWindow = window.open('', 'Ticket', 'width=600,height=400');
    ticketWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket - <?php echo $registration->ticket_number; ?></title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .ticket { border: 2px solid #000; padding: 20px; max-width: 500px; margin: 0 auto; }
                .header { text-align: center; margin-bottom: 20px; }
                .ticket-number { font-size: 24px; font-weight: bold; color: #007bff; }
                .event-title { font-size: 20px; font-weight: bold; margin: 10px 0; }
                .details { margin: 15px 0; }
                .barcode { text-align: center; margin: 20px 0; font-family: 'Libre Barcode 128', cursive; font-size: 40px; }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="header">
                    <h2>Event Ticket</h2>
                    <div class="ticket-number"><?php echo $registration->ticket_number; ?></div>
                </div>
                <div class="event-title"><?php echo htmlspecialchars($event->title); ?></div>
                <div class="details">
                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event->date)); ?><br>
                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($event->start_time)); ?><br>
                    <strong>Location:</strong> <?php echo htmlspecialchars($event->location); ?><br>
                    <strong>Attendee:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?><br>
                    <strong>Guests:</strong> <?php echo $registration->guests_count; ?>
                </div>
                <div class="barcode">
                    *<?php echo $registration->ticket_number; ?>*
                </div>
                <div style="text-align: center; font-size: 12px; color: #666;">
                    Please present this ticket at the event entrance
                </div>
            </div>
        </body>
        </html>
    `);
    ticketWindow.document.close();
}

// Auto-print option (optional)
// setTimeout(() => {
//     if (confirm('Would you like to print your confirmation?')) {
//         window.print();
//     }
// }, 2000);
</script>

<?php include '../templates/footer.php'; ?>