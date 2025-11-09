<?php
require_once '../includes/config.php';

$page_title = "Browse Events";
$event = new Event();
$auth = new Auth();

// Get filters from URL
$filters = [
    'category_id' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'filter' => $_GET['filter'] ?? 'all'
];

// Initialize events array
$events = [];

// Apply filters based on user type and filter type
if ($filters['filter'] === 'featured') {
    $events = $event->getFeatured(20);
} elseif ($filters['filter'] === 'upcoming') {
    $events = $event->getUpcoming(20);
} elseif ($filters['filter'] === 'my_events' && $auth->isLoggedIn()) {
    // Show events created by current user
    $events = $event->getEventsByOrganizer($_SESSION['user_id']);
} else {
    // For "all" filter - apply search, category, and date filters
    
    // Build SQL query with filters
    $sql = "SELECT e.*, c.name as category_name, c.color as category_color 
            FROM events e 
            LEFT JOIN categories c ON e.category_id = c.id 
            WHERE 1=1";
    
    $params = [];
    
    // Apply status filter for non-admin users
    if (!$auth->isLoggedIn() || !$auth->hasRole('super_admin')) {
        $sql .= " AND e.status = :status";
        $params[':status'] = 'published';
    }
    
    // Apply search filter - use unique parameter names
    if (!empty($filters['search'])) {
        $sql .= " AND (e.title LIKE :search_title OR e.description LIKE :search_desc OR e.short_description LIKE :search_short_desc)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[':search_title'] = $searchTerm;
        $params[':search_desc'] = $searchTerm;
        $params[':search_short_desc'] = $searchTerm;
    }
    
    // Apply category filter
    if (!empty($filters['category_id'])) {
        $sql .= " AND e.category_id = :category_id";
        $params[':category_id'] = $filters['category_id'];
    }
    
    // Apply date range filters
    if (!empty($filters['date_from'])) {
        $sql .= " AND e.date >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= " AND e.date <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    
    $sql .= " ORDER BY e.created_at DESC LIMIT 20";
    
    // Debug: Uncomment to see the actual SQL and parameters
    // echo "SQL: " . $sql . "<br>";
    // echo "Params: "; print_r($params); echo "<br>";
    
    // Execute the query
    try {
        $db = new Database();
        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $events = $db->resultSet();
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage();
        $events = [];
    }
}

// Get categories for filter dropdown
$db = new Database();
$db->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name');
$categories = $db->resultSet();

include '../templates/header.php';
?>

<div class="row">
    <div class="col-lg-3">
        <!-- Filters Sidebar -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2"></i>Filters
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <!-- Keep current filter in hidden field -->
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filters['filter']); ?>">
                    
                    <!-- Search -->
                    <div class="mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($filters['search']); ?>" 
                               placeholder="Search events...">
                    </div>
                    
                    <!-- Category -->
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>" 
                                    <?php echo $filters['category_id'] == $category->id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Date Range -->
                    <div class="mb-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>
                    
                    <!-- Quick Filters -->
                    <div class="mb-3">
                        <label class="form-label">Quick Filters</label>
                        <div class="d-grid gap-2">
                            <a href="?filter=all" class="btn btn-outline-primary btn-sm text-start <?php echo $filters['filter'] === 'all' ? 'active' : ''; ?>">
                                <i class="fas fa-calendar me-1"></i> All Events
                            </a>
                            <a href="?filter=upcoming" class="btn btn-outline-primary btn-sm text-start <?php echo $filters['filter'] === 'upcoming' ? 'active' : ''; ?>">
                                <i class="fas fa-clock me-1"></i> Upcoming
                            </a>
                            <a href="?filter=featured" class="btn btn-outline-primary btn-sm text-start <?php echo $filters['filter'] === 'featured' ? 'active' : ''; ?>">
                                <i class="fas fa-star me-1"></i> Featured
                            </a>
                            <?php if ($auth->isLoggedIn() && ($auth->hasRole('organizer') || $auth->hasRole('super_admin'))): ?>
                            <a href="?filter=my_events" class="btn btn-outline-primary btn-sm text-start <?php echo $filters['filter'] === 'my_events' ? 'active' : ''; ?>">
                                <i class="fas fa-user me-1"></i> My Events
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                        <a href="events.php" class="btn btn-outline-secondary">Clear All</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Categories -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tags me-2"></i>Categories
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($categories as $category): ?>
                    <a href="?category=<?php echo $category->id; ?>&filter=<?php echo $filters['filter']; ?>" 
                       class="badge rounded-pill text-decoration-none" 
                       style="background-color: <?php echo $category->color; ?>; color: white;">
                        <?php echo htmlspecialchars($category->name); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <!-- Events Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1">
                    <?php
                    switch($filters['filter']) {
                        case 'featured': echo 'Featured Events'; break;
                        case 'upcoming': echo 'Upcoming Events'; break;
                        case 'my_events': echo 'My Events'; break;
                        default: echo 'All Events';
                    }
                    ?>
                </h2>
                <p class="text-muted mb-0">
                    <?php echo count($events); ?> events found
                    <?php if (!empty($filters['search'])): ?>
                        for "<?php echo htmlspecialchars($filters['search']); ?>"
                    <?php endif; ?>
                    <?php if (!empty($filters['category_id'])): ?>
                        in <?php 
                            $category_name = '';
                            foreach ($categories as $cat) {
                                if ($cat->id == $filters['category_id']) {
                                    $category_name = $cat->name;
                                    break;
                                }
                            }
                            echo htmlspecialchars($category_name);
                        ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($auth->isLoggedIn() && ($auth->hasRole('organizer') || $auth->hasRole('super_admin'))): ?>
            <a href="create-event.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Event
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Active Filters -->
        <?php 
        $activeFilters = [];
        if (!empty($filters['search'])) $activeFilters[] = 'Search: ' . htmlspecialchars($filters['search']);
        if (!empty($filters['category_id'])) {
            foreach ($categories as $cat) {
                if ($cat->id == $filters['category_id']) {
                    $activeFilters[] = 'Category: ' . htmlspecialchars($cat->name);
                    break;
                }
            }
        }
        if (!empty($filters['date_from'])) $activeFilters[] = 'From: ' . htmlspecialchars($filters['date_from']);
        if (!empty($filters['date_to'])) $activeFilters[] = 'To: ' . htmlspecialchars($filters['date_to']);
        ?>
        
        <?php if (!empty($activeFilters)): ?>
        <div class="alert alert-info d-flex align-items-center justify-content-between mb-4">
            <div>
                <strong>Active Filters:</strong>
                <?php foreach ($activeFilters as $filter): ?>
                    <span class="badge bg-primary me-2"><?php echo $filter; ?></span>
                <?php endforeach; ?>
            </div>
            <a href="events.php" class="btn btn-sm btn-outline-danger">Clear All</a>
        </div>
        <?php endif; ?>
        
        <!-- Events Grid -->
        <div class="row g-4">
            <?php if (empty($events)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No events found</h4>
                <p class="text-muted">Try adjusting your filters or create the first event!</p>
                <?php if ($auth->isLoggedIn() && ($auth->hasRole('organizer') || $auth->hasRole('super_admin'))): ?>
                <a href="create-event.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>Create First Event
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($events as $event_item): 
                    $available_spots = $event->getAvailableSpots($event_item->id);
                    $is_registered = $auth->isLoggedIn() ? $event->isUserRegistered($event_item->id, $_SESSION['user_id']) : false;
                    $can_edit = $auth->isLoggedIn() && ($auth->hasRole('super_admin') || $event_item->user_id == $_SESSION['user_id']);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 event-card shadow-sm">
                        <?php if ($event_item->image): ?>
                        <img src="<?php echo UPLOAD_URL . $event_item->image; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event_item->title); ?>" style="height: 160px; object-fit: cover;">
                        <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                            <i class="fas fa-calendar-alt fa-2x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Status Badge -->
                        <?php if ($event_item->status !== 'published'): ?>
                        <div class="position-absolute top-0 start-0 m-2">
                            <span class="badge bg-<?php echo $event_item->status === 'draft' ? 'secondary' : 'danger'; ?>">
                                <?php echo ucfirst($event_item->status); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <!-- Category & Featured Badge -->
                            <div class="mb-2">
                                <span class="badge rounded-pill" style="background-color: <?php echo $event_item->category_color; ?>; color: white;">
                                    <?php echo htmlspecialchars($event_item->category_name); ?>
                                </span>
                                <?php if ($event_item->is_featured): ?>
                                <span class="badge bg-warning text-dark ms-1">
                                    <i class="fas fa-star me-1"></i>Featured
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Event Title -->
                            <h6 class="card-title"><?php echo htmlspecialchars($event_item->title); ?></h6>
                            
                            <!-- Short Description -->
                            <?php if (!empty($event_item->short_description)): ?>
                            <p class="card-text small text-muted mb-2">
                                <?php echo htmlspecialchars(substr($event_item->short_description, 0, 100)); ?>
                                <?php echo strlen($event_item->short_description) > 100 ? '...' : ''; ?>
                            </p>
                            <?php endif; ?>
                            
                            <!-- Event Meta -->
                            <div class="event-meta small text-muted mb-3 flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo date('M j, Y', strtotime($event_item->date)); ?>
                                </div>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo date('g:i A', strtotime($event_item->start_time)); ?>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($event_item->venue_name ?: $event_item->location); ?>
                                </div>
                            </div>
                            
                            <!-- Price & Capacity -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
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
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <a href="event-detail.php?id=<?php echo $event_item->id; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                
                                <?php if ($can_edit): ?>
                                <div class="btn-group" role="group">
                                    <a href="edit-event.php?id=<?php echo $event_item->id; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Event">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($auth->hasRole('super_admin') || $event_item->user_id == $_SESSION['user_id']): ?>
                                    <a href="delete-event.php?id=<?php echo $event_item->id; ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this event?')"
                                       title="Delete Event">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($auth->isLoggedIn() && !$is_registered && $available_spots > 0 && $event_item->status === 'published'): ?>
                                <a href="register-event.php?event_id=<?php echo $event_item->id; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-ticket-alt me-1"></i>Register Now
                                </a>
                                <?php elseif ($auth->isLoggedIn() && $is_registered): ?>
                                <button class="btn btn-success btn-sm" disabled>
                                    <i class="fas fa-check me-1"></i>Registered
                                </button>
                                <?php elseif ($available_spots <= 0 && $event_item->status === 'published'): ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-clock me-1"></i>Waitlist
                                </button>
                                <?php elseif ($event_item->status !== 'published'): ?>
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <?php echo ucfirst($event_item->status); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Load More Button (if needed) -->
        <?php if (count($events) >= 20): ?>
        <div class="text-center mt-4">
            <button class="btn btn-outline-primary" id="loadMore">
                <i class="fas fa-refresh me-2"></i>Load More Events
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple load more functionality
document.getElementById('loadMore')?.addEventListener('click', function() {
    const currentUrl = new URL(window.location.href);
    const currentPage = parseInt(currentUrl.searchParams.get('page')) || 1;
    currentUrl.searchParams.set('page', currentPage + 1);
    window.location.href = currentUrl.toString();
});

// Add active class to quick filter buttons
document.querySelectorAll('.btn-outline-primary').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-outline-primary').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});

// Auto-submit form when category or date filters change
document.getElementById('category')?.addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('date_from')?.addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('date_to')?.addEventListener('change', function() {
    this.form.submit();
});
</script>

<?php include '../templates/footer.php'; ?>