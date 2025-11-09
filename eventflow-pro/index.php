<?php
require_once 'includes/config.php';

$page_title = "Discover Amazing Events";
$event = new Event();
$auth = new Auth();

// Get featured and upcoming events
$featured_events = $event->getFeatured(3);
$upcoming_events = $event->getUpcoming(6);

// Get categories for display
$db = new Database();
$db->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name LIMIT 6');
$categories = $db->resultSet();

include 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">Discover Your Next Great Experience</h1>
                <p class="lead mb-4">From conferences to workshops, find events that inspire, educate, and connect you with amazing people.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="/eventflow-pro/pages/events.php" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-search me-2"></i>Explore Events
                    </a>
                    <?php if ($auth->isLoggedIn() && ($auth->hasRole('organizer') || $auth->hasRole('super_admin'))): ?>
                    <a href="/eventflow-pro/pages/create-event.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-plus me-2"></i>Create Event
                    </a>
                    <?php elseif (!$auth->isLoggedIn()): ?>
                    <a href="/eventflow-pro/pages/register.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Join Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="/eventflow-pro/assets/images/hero-events.png" alt="Events" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
</section>

<!-- Featured Events -->
<section class="featured-events mb-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="h3 mb-2">Featured Events</h2>
                <p class="text-muted">Handpicked events you don't want to miss</p>
            </div>
            <div class="col-auto">
                <a href="/eventflow-pro/pages/events.php?filter=featured" class="btn btn-outline-primary">View All</a>
            </div>
        </div>
        
        <div class="row g-4">
            <?php if (empty($featured_events)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-star fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No featured events yet</h4>
                <p class="text-muted">Check back soon for amazing events!</p>
                <?php if ($auth->isLoggedIn() && ($auth->hasRole('organizer') || $auth->hasRole('super_admin'))): ?>
                <a href="/eventflow-pro/pages/create-event.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>Create Featured Event
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($featured_events as $event_item): 
                    $available_spots = $event->getAvailableSpots($event_item->id);
                ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <?php if ($event_item->image): ?>
                        <img src="<?php echo UPLOAD_URL . $event_item->image; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event_item->title); ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge rounded-pill" style="background-color: <?php echo $event_item->category_color; ?>">
                                    <?php echo htmlspecialchars($event_item->category_name); ?>
                                </span>
                                <span class="badge bg-warning text-dark ms-1">
                                    <i class="fas fa-star me-1"></i>Featured
                                </span>
                            </div>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($event_item->title); ?></h5>
                            
                            <?php if (!empty($event_item->short_description)): ?>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars($event_item->short_description); ?>
                            </p>
                            <?php else: ?>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars(substr($event_item->description, 0, 100)); ?>...
                            </p>
                            <?php endif; ?>
                            
                            <div class="event-meta mb-3">
                                <div class="d-flex align-items-center text-muted small mb-1">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo date('M j, Y', strtotime($event_item->date)); ?>
                                </div>
                                <div class="d-flex align-items-center text-muted small mb-1">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo date('g:i A', strtotime($event_item->start_time)); ?>
                                </div>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($event_item->venue_name ?: $event_item->location); ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <div>
                                    <?php if ($event_item->price > 0): ?>
                                    <span class="fw-bold text-primary">$<?php echo number_format($event_item->price, 2); ?></span>
                                    <?php else: ?>
                                    <span class="fw-bold text-success">Free</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo $available_spots; ?> spots left
                                </small>
                            </div>
                            
                            <div class="d-grid mt-3">
                                <a href="/eventflow-pro/pages/event-detail.php?id=<?php echo $event_item->id; ?>" class="btn btn-primary btn-sm">
                                    View Details & Register
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Upcoming Events -->
<section class="upcoming-events mb-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="h3 mb-2">Upcoming Events</h2>
                <p class="text-muted">Discover what's happening soon</p>
            </div>
            <div class="col-auto">
                <a href="/eventflow-pro/pages/events.php" class="btn btn-outline-primary">View All Events</a>
            </div>
        </div>
        
        <div class="row g-4">
            <?php if (empty($upcoming_events)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No upcoming events</h4>
                <p class="text-muted">Be the first to create an event!</p>
                <?php if ($auth->isLoggedIn() && ($auth->hasRole('organizer') || $auth->hasRole('super_admin'))): ?>
                <a href="/eventflow-pro/pages/create-event.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>Create Event
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($upcoming_events as $event_item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge rounded-pill" style="background-color: <?php echo $event_item->category_color; ?>">
                                    <?php echo htmlspecialchars($event_item->category_name); ?>
                                </span>
                                <small class="text-muted">
                                    <?php echo date('M j', strtotime($event_item->date)); ?>
                                </small>
                            </div>
                            
                            <h6 class="card-title"><?php echo htmlspecialchars($event_item->title); ?></h6>
                            
                            <div class="event-meta small text-muted mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo date('g:i A', strtotime($event_item->start_time)); ?>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($event_item->venue_name ?: $event_item->location); ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($event_item->price > 0): ?>
                                    <small class="fw-bold text-primary">$<?php echo number_format($event_item->price, 2); ?></small>
                                    <?php else: ?>
                                    <small class="fw-bold text-success">Free</small>
                                    <?php endif; ?>
                                </div>
                                <a href="/eventflow-pro/pages/event-detail.php?id=<?php echo $event_item->id; ?>" class="btn btn-outline-primary btn-sm">
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section bg-light py-5 mb-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="h3 mb-2">Browse by Category</h2>
                <p class="text-muted">Find events that match your interests</p>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-4 col-lg-2">
                <a href="/eventflow-pro/pages/events.php?category=<?php echo $category->id; ?>" class="text-decoration-none">
                    <div class="card text-center h-100 category-card">
                        <div class="card-body">
                            <div class="category-icon mb-3" style="color: <?php echo $category->color; ?>">
                                <i class="fas fa-<?php echo $category->icon ?: 'tag'; ?> fa-2x"></i>
                            </div>
                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($category->name); ?></h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 text-primary fw-bold"><?php echo count($upcoming_events); ?></h3>
                    <p class="text-muted">Upcoming Events</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 text-success fw-bold"><?php echo count($featured_events); ?></h3>
                    <p class="text-muted">Featured Events</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 text-warning fw-bold"><?php echo count($categories); ?></h3>
                    <p class="text-muted">Categories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 text-info fw-bold">100%</h3>
                    <p class="text-muted">Satisfaction</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>