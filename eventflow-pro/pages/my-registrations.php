<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = "My Registrations";
$user = new User();
$registration = new Registration();

$current_user = $auth->getCurrentUser();
$registrations = $user->getRegisteredEvents($current_user->id);

include '../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="fas fa-ticket-alt me-2"></i>My Event Registrations
                </h4>
                <span class="badge bg-primary"><?php echo count($registrations); ?> events</span>
            </div>
            <div class="card-body">
                <?php if (empty($registrations)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Registrations Yet</h4>
                    <p class="text-muted">You haven't registered for any events yet.</p>
                    <a href="events.php" class="btn btn-primary">
                        <i class="fas fa-calendar me-2"></i>Browse Events
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date & Time</th>
                                <th>Ticket Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($reg->title); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($reg->organizer_name); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($reg->date)); ?><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($reg->start_time)); ?></small>
                                </td>
                                <td>
                                    <code><?php echo $reg->ticket_number; ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $reg->status === 'registered' ? 'primary' : 
                                             ($reg->status === 'attended' ? 'success' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($reg->status); ?>
                                    </span>
                                    <?php if ($reg->guests_count > 0): ?>
                                    <br>
                                    <small class="text-muted">+<?php echo $reg->guests_count; ?> guests</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="registration-confirmation.php?registration_id=<?php echo $reg->id; ?>" 
                                           class="btn btn-outline-primary" title="View Confirmation">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="event-detail.php?id=<?php echo $reg->event_id; ?>" 
                                           class="btn btn-outline-info" title="View Event">
                                            <i class="fas fa-calendar"></i>
                                        </a>
                                        <?php if ($reg->status === 'registered'): ?>
                                        <a href="cancel-registration.php?registration_id=<?php echo $reg->id; ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Cancel Registration"
                                           onclick="return confirm('Are you sure you want to cancel this registration?')">
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

<?php include '../templates/footer.php'; ?>