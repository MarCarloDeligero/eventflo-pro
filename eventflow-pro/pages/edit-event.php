<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user can edit events
if (!$auth->hasRole('organizer') && !$auth->hasRole('super_admin')) {
    header('Location: dashboard.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Edit Event";
$event = new Event();
$error = '';
$success = '';

$event_id = intval($_GET['id']);
$current_event = $event->getById($event_id);

// Check if event exists and user has permission to edit
if (!$current_event) {
    header('Location: dashboard.php');
    exit;
}

// Check if user owns the event or is super admin
if ($current_event->user_id != $_SESSION['user_id'] && !$auth->hasRole('super_admin')) {
    header('Location: dashboard.php');
    exit;
}

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
            'user_id' => $current_event->user_id, // Keep original organizer
            'registration_deadline' => $_POST['registration_deadline'] ?: null,
            'status' => $_POST['status'] ?? 'published',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0  // ADD THIS LINE
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
            // Allow editing past events but show warning
            if ($data['status'] === 'published') {
                $data['status'] = 'completed';
            }
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
                
                // Delete old image if it exists
                if ($current_event->image && file_exists(UPLOAD_PATH . $current_event->image)) {
                    unlink(UPLOAD_PATH . $current_event->image);
                }
            } else {
                throw new Exception('Failed to upload image');
            }
        } else {
            // Keep existing image
            $data['image'] = $current_event->image;
        }

        // Handle image removal
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            // Delete old image if it exists
            if ($current_event->image && file_exists(UPLOAD_PATH . $current_event->image)) {
                unlink(UPLOAD_PATH . $current_event->image);
            }
            $data['image'] = null;
        }

        // Update event
        if ($event->update($event_id, $data)) {
            $success = 'Event updated successfully!';
            // Refresh current event data
            $current_event = $event->getById($event_id);
        } else {
            throw new Exception('Failed to update event');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Edit Event
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
                    <a href="event-detail.php?id=<?php echo $event_id; ?>" class="alert-link ms-2">View Event</a>
                </div>
                <?php endif; ?>

                <!-- Event Summary -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><?php echo htmlspecialchars($current_event->title); ?></h5>
                                <p class="text-muted mb-2">Event ID: <?php echo $current_event->id; ?></p>
                                <p class="text-muted mb-0">
                                    Created: <?php echo date('M j, Y g:i A', strtotime($current_event->created_at)); ?>
                                    <?php if ($current_event->is_featured): ?>
                                    <span class="badge bg-warning text-dark ms-2">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group">
                                    <a href="event-detail.php?id=<?php echo $event_id; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                                               value="<?php echo htmlspecialchars($current_event->title); ?>" 
                                               required placeholder="Enter event title">
                                    </div>

                                    <div class="mb-3">
                                        <label for="short_description" class="form-label">Short Description</label>
                                        <textarea class="form-control" id="short_description" name="short_description" 
                                                  rows="2" placeholder="Brief description (appears in event listings)"><?php echo htmlspecialchars($current_event->short_description); ?></textarea>
                                        <div class="form-text">Max 500 characters. This appears in event cards.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Full Description *</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="6" required placeholder="Detailed description of your event"><?php echo htmlspecialchars($current_event->description); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category->id; ?>" 
                                                    <?php echo $current_event->category_id == $category->id ? 'selected' : ''; ?>>
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
                                                       value="<?php echo $current_event->date; ?>" 
                                                       min="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="registration_deadline" class="form-label">Registration Deadline</label>
                                                <input type="datetime-local" class="form-control" id="registration_deadline" 
                                                       name="registration_deadline" 
                                                       value="<?php echo $current_event->registration_deadline ? date('Y-m-d\TH:i', strtotime($current_event->registration_deadline)) : ''; ?>">
                                                <div class="form-text">Leave empty to accept registrations until event starts</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="start_time" class="form-label">Start Time *</label>
                                                <input type="time" class="form-control" id="start_time" name="start_time" 
                                                       value="<?php echo $current_event->start_time; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="end_time" class="form-label">End Time *</label>
                                                <input type="time" class="form-control" id="end_time" name="end_time" 
                                                       value="<?php echo $current_event->end_time; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Event Status -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Event Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" <?php echo $current_event->status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                            <option value="published" <?php echo $current_event->status === 'published' ? 'selected' : ''; ?>>Published</option>
                                            <option value="cancelled" <?php echo $current_event->status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <div class="form-text">
                                            <?php if ($current_event->status === 'published'): ?>
                                            <span class="text-success">This event is visible to the public.</span>
                                            <?php elseif ($current_event->status === 'draft'): ?>
                                            <span class="text-warning">This event is hidden from the public.</span>
                                            <?php else: ?>
                                            <span class="text-danger">This event is cancelled.</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                               value="1" <?php echo $current_event->is_featured ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold text-warning" for="is_featured">
                                            <i class="fas fa-star me-1"></i>Feature this event
                                        </label>
                                        <div class="form-text">Featured events appear on the homepage and in featured sections.</div>
                                    </div>

                                    <?php if ($current_event->is_featured): ?>
                                    <div class="alert alert-warning small mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        This event is currently featured and will appear in the featured events section.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Event Image -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Event Image</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($current_event->image): ?>
                                    <div class="text-center mb-3">
                                        <img src="<?php echo UPLOAD_URL . $current_event->image; ?>" 
                                             class="img-fluid rounded" 
                                             alt="Current event image" 
                                             style="max-height: 150px;">
                                        <p class="small text-muted mt-2">Current image</p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label"><?php echo $current_event->image ? 'Change Image' : 'Upload Image'; ?></label>
                                        <input type="file" class="form-control" id="image" name="image" 
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">
                                            Recommended: 1200x600px, max 5MB<br>
                                            Formats: JPG, PNG, GIF, WebP
                                        </div>
                                    </div>
                                    
                                    <?php if ($current_event->image): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                        <label class="form-check-label text-danger" for="remove_image">
                                            Remove current image
                                        </label>
                                    </div>
                                    <?php endif; ?>
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
                                               value="<?php echo htmlspecialchars($current_event->venue_name); ?>" 
                                               placeholder="e.g., Convention Center">
                                    </div>

                                    <div class="mb-3">
                                        <label for="location" class="form-label">Address *</label>
                                        <textarea class="form-control" id="location" name="location" 
                                                  rows="3" required placeholder="Full address of the event"><?php echo htmlspecialchars($current_event->location); ?></textarea>
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
                                               value="<?php echo $current_event->capacity; ?>" 
                                               min="1" max="10000" required>
                                        <div class="form-text">Maximum number of attendees</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price ($)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   value="<?php echo $current_event->price; ?>" 
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
                                            <i class="fas fa-save me-2"></i>Update Event
                                        </button>
                                        <a href="event-detail.php?id=<?php echo $event_id; ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <?php if ($auth->hasRole('super_admin') || $current_event->user_id == $_SESSION['user_id']): ?>
                                        <a href="delete-event.php?id=<?php echo $event_id; ?>" 
                                           class="btn btn-outline-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')">
                                            <i class="fas fa-trash me-1"></i>Delete Event
                                        </a>
                                        <?php endif; ?>
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
    const file = e.target.files[0];
    if (file) {
        let preview = document.getElementById('image-preview');
        if (!preview) {
            const previewContainer = document.createElement('div');
            previewContainer.id = 'image-preview';
            previewContainer.className = 'text-center mt-3';
            previewContainer.innerHTML = '<img id="preview" class="img-fluid rounded" style="max-height: 150px;"><p class="small text-muted mt-2">New image preview</p>';
            this.parentNode.appendChild(previewContainer);
            preview = document.getElementById('preview');
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
        }
        reader.readAsDataURL(file);
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
            if (!confirm('This event date is in the past. Are you sure you want to update it?')) {
                e.preventDefault();
                return;
            }
        }
        
        if (endDateTime <= eventDateTime) {
            e.preventDefault();
            alert('End time must be after start time.');
            return;
        }
    }

    // Check if featured event has an image
    const isFeatured = document.getElementById('is_featured').checked;
    const hasImage = document.getElementById('image').files.length > 0 || <?php echo $current_event->image ? 'true' : 'false'; ?>;
    
    if (isFeatured && !hasImage) {
        if (!confirm('Featured events look better with an image. Continue without an image?')) {
            e.preventDefault();
            return;
        }
    }
});

// Handle image removal
document.getElementById('remove_image')?.addEventListener('change', function() {
    const fileInput = document.getElementById('image');
    const preview = document.getElementById('image-preview');
    
    if (this.checked) {
        fileInput.disabled = true;
        if (preview) {
            preview.style.display = 'none';
        }
    } else {
        fileInput.disabled = false;
    }
});

// Featured event warning
document.getElementById('is_featured').addEventListener('change', function() {
    if (this.checked) {
        const hasImage = document.getElementById('image').files.length > 0 || <?php echo $current_event->image ? 'true' : 'false'; ?>;
        if (!hasImage) {
            alert('💡 Tip: Adding an image will make your featured event more attractive!');
        }
    }
});
</script>

<?php include '../templates/footer.php'; ?>