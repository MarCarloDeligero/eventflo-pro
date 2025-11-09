<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user can create events
if (!$auth->hasRole('organizer') && !$auth->hasRole('super_admin')) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Create New Event";
$event = new Event();
$error = '';
$success = '';
$event_id = null;

// Get categories for dropdown
$db = new Database();
$db->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name');
$categories = $db->resultSet();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description']),
            'short_description' => trim($_POST['short_description']),
            'date' => $_POST['date'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'location' => trim($_POST['location']),
            'venue_name' => trim($_POST['venue_name']),
            'capacity' => intval($_POST['capacity']),
            'price' => floatval($_POST['price']),
            'category_id' => intval($_POST['category_id']),
            'user_id' => $_SESSION['user_id'],
            'registration_deadline' => $_POST['registration_deadline'] ?: null,
            'status' => 'published', // Add default status
            'is_featured' => 0, // Add default featured status
            'image' => null // Initialize image as null
        ];

        // Validation
        if (empty($data['title']) || empty($data['description']) || empty($data['date']) || 
            empty($data['start_time']) || empty($data['end_time']) || empty($data['location']) || 
            empty($data['capacity'])) {
            throw new Exception('Please fill in all required fields');
        }

        if ($data['capacity'] < 1) {
            throw new Exception('Capacity must be at least 1');
        }

        if (strtotime($data['date'] . ' ' . $data['start_time']) < time()) {
            throw new Exception('Event date and time must be in the future');
        }

        if (strtotime($data['date'] . ' ' . $data['end_time']) <= strtotime($data['date'] . ' ' . $data['start_time'])) {
            throw new Exception('End time must be after start time');
        }

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed');
            }
            
            if ($file_size > $max_size) {
                throw new Exception('Image size must be less than 5MB');
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $upload_path = UPLOAD_PATH . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $data['image'] = $filename;
            } else {
                throw new Exception('Failed to upload image');
            }
        }

        // Create event using direct SQL to avoid parameter binding issues
        $event_id = $event->createEvent($data);
        
        if ($event_id) {
            $success = 'Event created successfully!';
            // Clear form data
            $_POST = [];
        } else {
            throw new Exception('Failed to create event');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        // If there was an image uploaded but event creation failed, delete the image
        if (isset($filename) && file_exists(UPLOAD_PATH . $filename)) {
            unlink(UPLOAD_PATH . $filename);
        }
    }
}

include '../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Create New Event
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
                    <?php if ($event_id): ?>
                    <a href="event-detail.php?id=<?php echo $event_id; ?>" class="alert-link ms-2">View Event</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Event Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                               required placeholder="Enter event title">
                                    </div>

                                    <div class="mb-3">
                                        <label for="short_description" class="form-label">Short Description</label>
                                        <textarea class="form-control" id="short_description" name="short_description" 
                                                  rows="2" placeholder="Brief description (appears in event listings)"><?php echo isset($_POST['short_description']) ? htmlspecialchars($_POST['short_description']) : ''; ?></textarea>
                                        <div class="form-text">Max 500 characters. This appears in event cards.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Full Description *</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="6" required placeholder="Detailed description of your event"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category->id; ?>" 
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category->id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category->name); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Date & Time -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Date & Time</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="date" class="form-label">Event Date *</label>
                                                <input type="date" class="form-control" id="date" name="date" 
                                                       value="<?php echo isset($_POST['date']) ? $_POST['date'] : ''; ?>" 
                                                       min="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="registration_deadline" class="form-label">Registration Deadline</label>
                                                <input type="datetime-local" class="form-control" id="registration_deadline" 
                                                       name="registration_deadline" 
                                                       value="<?php echo isset($_POST['registration_deadline']) ? $_POST['registration_deadline'] : ''; ?>">
                                                <div class="form-text">Leave empty to accept registrations until event starts</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="start_time" class="form-label">Start Time *</label>
                                                <input type="time" class="form-control" id="start_time" name="start_time" 
                                                       value="<?php echo isset($_POST['start_time']) ? $_POST['start_time'] : '09:00'; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="end_time" class="form-label">End Time *</label>
                                                <input type="time" class="form-control" id="end_time" name="end_time" 
                                                       value="<?php echo isset($_POST['end_time']) ? $_POST['end_time'] : '17:00'; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Event Image -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Event Image</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Upload Image</label>
                                        <input type="file" class="form-control" id="image" name="image" 
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">
                                            Recommended: 1200x600px, max 5MB<br>
                                            Formats: JPG, PNG, GIF, WebP
                                        </div>
                                    </div>
                                    
                                    <div id="image-preview" class="text-center mt-3" style="display: none;">
                                        <img id="preview" class="img-fluid rounded" style="max-height: 200px;">
                                        <p class="small text-muted mt-2">Image Preview</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Location</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="venue_name" class="form-label">Venue Name</label>
                                        <input type="text" class="form-control" id="venue_name" name="venue_name" 
                                               value="<?php echo isset($_POST['venue_name']) ? htmlspecialchars($_POST['venue_name']) : ''; ?>" 
                                               placeholder="e.g., Convention Center">
                                    </div>

                                    <div class="mb-3">
                                        <label for="location" class="form-label">Address *</label>
                                        <textarea class="form-control" id="location" name="location" 
                                                  rows="3" required placeholder="Full address of the event"><?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Capacity & Pricing -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Capacity & Pricing</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="capacity" class="form-label">Capacity *</label>
                                        <input type="number" class="form-control" id="capacity" name="capacity" 
                                               value="<?php echo isset($_POST['capacity']) ? $_POST['capacity'] : '50'; ?>" 
                                               min="1" max="10000" required>
                                        <div class="form-text">Maximum number of attendees</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price ($)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   value="<?php echo isset($_POST['price']) ? $_POST['price'] : '0'; ?>" 
                                                   min="0" step="0.01" placeholder="0.00">
                                        </div>
                                        <div class="form-text">Enter 0 for free events</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus-circle me-2"></i>Create Event
                                        </button>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview
document.getElementById('image').addEventListener('change', function(e) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('image-preview');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const date = document.getElementById('date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (date && startTime && endTime) {
        const eventDateTime = new Date(date + 'T' + startTime);
        const endDateTime = new Date(date + 'T' + endTime);
        
        if (eventDateTime < new Date()) {
            e.preventDefault();
            alert('Event date and time must be in the future.');
            return;
        }
        
        if (endDateTime <= eventDateTime) {
            e.preventDefault();
            alert('End time must be after start time.');
            return;
        }
    }
});
</script>

<?php include '../templates/footer.php'; ?>