<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = "My Profile";
$user_class = new User();
$current_user = $auth->getCurrentUser();

// Set default values for missing fields
$current_user->email_verified = $current_user->email_verified ?? 0;
$current_user->bio = $current_user->bio ?? '';
$current_user->phone = $current_user->phone ?? '';
$current_user->avatar = $current_user->avatar ?? '';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => trim($_POST['name']),
            'bio' => trim($_POST['bio'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];

        // Validation
        if (empty($data['name'])) {
            throw new Exception('Name is required');
        }

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            $file_type = $_FILES['avatar']['type'];
            $file_size = $_FILES['avatar']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed');
            }
            
            if ($file_size > $max_size) {
                throw new Exception('Avatar size must be less than 2MB');
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $current_user->id . '_' . uniqid() . '.' . $extension;
            $upload_path = UPLOAD_PATH . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $data['avatar'] = $filename;
                
                // Delete old avatar if it exists and is not the default
                if (!empty($current_user->avatar) && $current_user->avatar !== 'default.png' && file_exists(UPLOAD_PATH . $current_user->avatar)) {
                    unlink(UPLOAD_PATH . $current_user->avatar);
                }
            } else {
                throw new Exception('Failed to upload avatar');
            }
        }

        // Handle avatar removal
        if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
            $data['avatar'] = null;
            // Delete old avatar if it exists
            if (!empty($current_user->avatar) && file_exists(UPLOAD_PATH . $current_user->avatar)) {
                unlink(UPLOAD_PATH . $current_user->avatar);
            }
        }

        // Update profile
        if ($user_class->updateProfile($current_user->id, $data)) {
            $success = 'Profile updated successfully!';
            // Refresh current user data
            $current_user = $auth->getCurrentUser();
            // Set default values again after refresh
            $current_user->email_verified = $current_user->email_verified ?? 0;
            $current_user->bio = $current_user->bio ?? '';
            $current_user->phone = $current_user->phone ?? '';
            $current_user->avatar = $current_user->avatar ?? '';
        } else {
            throw new Exception('Failed to update profile');
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
                    <i class="fas fa-user me-2"></i>My Profile
                </h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <!-- Profile Summary -->
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <div class="avatar-container mb-3 position-relative">
                                    <?php if (!empty($current_user->avatar)): ?>
                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($current_user->avatar); ?>" 
                                             alt="Profile Avatar" 
                                             class="rounded-circle shadow" 
                                             width="120" height="120"
                                             id="avatar-preview">
                                    <?php else: ?>
                                        <div class="rounded-circle shadow bg-secondary d-flex align-items-center justify-content-center" 
                                             style="width: 120px; height: 120px;"
                                             id="avatar-preview">
                                            <i class="fas fa-user fa-3x text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <label for="avatar" class="avatar-overlay">
                                        <i class="fas fa-camera"></i>
                                        <span>Change Photo</span>
                                    </label>
                                </div>
                                <h5><?php echo htmlspecialchars($current_user->name ?? ''); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($current_user->email ?? ''); ?></p>
                                <span class="badge bg-<?php 
                                    echo ($current_user->role ?? 'attendee') === 'super_admin' ? 'danger' : 
                                         (($current_user->role ?? 'attendee') === 'organizer' ? 'warning text-dark' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $current_user->role ?? 'attendee')); ?>
                                </span>
                                
                                <div class="mt-3 small text-muted">
                                    <div class="d-flex align-items-center justify-content-center mb-1">
                                        <i class="fas fa-calendar me-2"></i>
                                        Member since <?php echo date('M Y', strtotime($current_user->created_at ?? 'now')); ?>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-<?php echo ($current_user->email_verified ?? 0) ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                        <?php echo ($current_user->email_verified ?? 0) ? 'Email verified' : 'Email not verified'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Quick Stats</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $db = new Database();
                                
                                // Get events created count
                                $db->query('SELECT COUNT(*) as count FROM events WHERE user_id = :user_id');
                                $db->bind(':user_id', $current_user->id);
                                $events_created = $db->single()->count;
                                
                                // Get events registered count
                                $db->query('SELECT COUNT(*) as count FROM registrations WHERE user_id = :user_id AND status IN ("registered", "attended")');
                                $db->bind(':user_id', $current_user->id);
                                $events_registered = $db->single()->count;
                                ?>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Events Created:</span>
                                    <span class="badge bg-primary"><?php echo $events_created; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Events Attending:</span>
                                    <span class="badge bg-success"><?php echo $events_registered; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Account Status:</span>
                                    <span class="badge bg-<?php echo ($current_user->is_active ?? 1) ? 'success' : 'danger'; ?>">
                                        <?php echo ($current_user->is_active ?? 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Profile Edit Form -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-edit me-2"></i>Edit Profile
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Full Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($current_user->name ?? ''); ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" 
                                                       value="<?php echo htmlspecialchars($current_user->email ?? ''); ?>" 
                                                       disabled>
                                                <div class="form-text">Email cannot be changed</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" 
                                                  rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($current_user->bio ?? ''); ?></textarea>
                                        <div class="form-text">Brief description about yourself (optional)</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($current_user->phone ?? ''); ?>" 
                                               placeholder="+1 (555) 123-4567">
                                    </div>

                                    <div class="mb-3">
                                        <label for="avatar" class="form-label">Profile Picture</label>
                                        <input type="file" class="form-control d-none" id="avatar" name="avatar" 
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        
                                        <!-- Custom file upload button -->
                                        <div class="custom-file-upload">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('avatar').click()">
                                                <i class="fas fa-upload me-2"></i>Choose Image
                                            </button>
                                            <span id="file-name" class="ms-2 text-muted">No file chosen</span>
                                        </div>
                                        
                                        <div class="form-text mt-2">
                                            Recommended: Square image, max 2MB<br>
                                            Formats: JPG, PNG, GIF, WebP
                                        </div>
                                    </div>

                                    <?php if (!empty($current_user->avatar)): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar" value="1">
                                        <label class="form-check-label" for="remove_avatar">
                                            Remove current profile picture
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Account Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-cog me-2"></i>Account Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Account Role</label>
                                                <input type="text" class="form-control" 
                                                       value="<?php echo ucfirst(str_replace('_', ' ', $current_user->role ?? 'attendee')); ?>" 
                                                       disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Member Since</label>
                                                <input type="text" class="form-control" 
                                                       value="<?php echo date('F j, Y', strtotime($current_user->created_at ?? 'now')); ?>" 
                                                       disabled>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!($current_user->email_verified ?? 0)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Your email address is not verified. 
                                        <a href="verify-email.php" class="alert-link">Verify now</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Profile
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6>Delete Account</h6>
                        <p class="text-muted mb-0">
                            Permanently delete your account and all associated data. This action cannot be undone.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-danger" 
                                onclick="confirmDelete()"
                                <?php echo ($current_user->role ?? 'attendee') === 'super_admin' ? 'disabled' : ''; ?>>
                            <i class="fas fa-trash me-2"></i>Delete Account
                        </button>
                        <?php if (($current_user->role ?? 'attendee') === 'super_admin'): ?>
                        <div class="form-text text-danger mt-1">Admin accounts cannot be deleted</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Avatar file input handling
const avatarInput = document.getElementById('avatar');
const avatarPreview = document.getElementById('avatar-preview');
const fileName = document.getElementById('file-name');

// Handle file selection
avatarInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Update file name display
        fileName.textContent = file.name;
        
        // Validate file type and size
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!allowedTypes.includes(file.type)) {
            alert('Error: Only JPG, PNG, GIF, and WebP images are allowed.');
            this.value = '';
            fileName.textContent = 'No file chosen';
            return;
        }
        
        if (file.size > maxSize) {
            alert('Error: Image size must be less than 2MB.');
            this.value = '';
            fileName.textContent = 'No file chosen';
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            // If it's a div with icon, convert to img
            if (avatarPreview.tagName === 'DIV') {
                const newImg = document.createElement('img');
                newImg.src = e.target.result;
                newImg.alt = 'Profile Avatar';
                newImg.className = 'rounded-circle shadow';
                newImg.style.width = '120px';
                newImg.style.height = '120px';
                newImg.id = 'avatar-preview';
                avatarPreview.parentNode.replaceChild(newImg, avatarPreview);
            } else {
                avatarPreview.src = e.target.result;
            }
        }
        reader.readAsDataURL(file);
    } else {
        fileName.textContent = 'No file chosen';
    }
});

// Click on avatar to trigger file input
document.querySelector('.avatar-container').addEventListener('click', function(e) {
    if (e.target !== avatarInput) {
        avatarInput.click();
    }
});

// Remove avatar checkbox handler
document.getElementById('remove_avatar')?.addEventListener('change', function() {
    const fileInput = document.getElementById('avatar');
    const fileNameDisplay = document.getElementById('file-name');
    
    if (this.checked) {
        fileInput.disabled = true;
        fileNameDisplay.textContent = 'Avatar will be removed';
        
        // Replace with default avatar
        if (avatarPreview.tagName === 'IMG') {
            const defaultDiv = document.createElement('div');
            defaultDiv.className = 'rounded-circle shadow bg-secondary d-flex align-items-center justify-content-center';
            defaultDiv.style.width = '120px';
            defaultDiv.style.height = '120px';
            defaultDiv.id = 'avatar-preview';
            defaultDiv.innerHTML = '<i class="fas fa-user fa-3x text-white"></i>';
            avatarPreview.parentNode.replaceChild(defaultDiv, avatarPreview);
        }
    } else {
        fileInput.disabled = false;
        fileNameDisplay.textContent = 'No file chosen';
        
        // Restore original avatar if it exists
        const originalAvatar = '<?php echo !empty($current_user->avatar) ? UPLOAD_URL . $current_user->avatar : ''; ?>';
        if (originalAvatar) {
            const newImg = document.createElement('img');
            newImg.src = originalAvatar;
            newImg.alt = 'Profile Avatar';
            newImg.className = 'rounded-circle shadow';
            newImg.style.width = '120px';
            newImg.style.height = '120px';
            newImg.id = 'avatar-preview';
            document.querySelector('.avatar-container').replaceChild(newImg, document.getElementById('avatar-preview'));
        }
    }
});

// Account deletion confirmation
function confirmDelete() {
    if (confirm('Are you absolutely sure you want to delete your account?\n\nThis will:\n• Permanently delete your profile\n• Remove all your event registrations\n• Delete events you created\n• This action cannot be undone!')) {
        if (confirm('This is your last chance to cancel. Are you REALLY sure?')) {
            window.location.href = 'delete-account.php';
        }
    }
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const phone = document.getElementById('phone').value.trim();
    
    if (name.length < 2) {
        e.preventDefault();
        alert('Please enter a valid name (at least 2 characters)');
        return;
    }
    
    if (phone && !/^[\+]?[1-9][\d]{0,15}$/.test(phone.replace(/[\s\-\(\)]/g, ''))) {
        e.preventDefault();
        alert('Please enter a valid phone number');
        return;
    }
});
</script>

<style>
.avatar-container {
    position: relative;
    display: inline-block;
    cursor: pointer;
}

.avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
}

.avatar-overlay:hover {
    opacity: 1;
}

.avatar-overlay i {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.avatar-overlay span {
    font-size: 0.8rem;
}

.custom-file-upload {
    display: flex;
    align-items: center;
    margin-top: 10px;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

#avatar-preview {
    cursor: pointer;
    transition: opacity 0.3s ease;
    object-fit: cover;
}

.avatar-container:hover #avatar-preview {
    opacity: 0.8;
}
</style>

<?php include '../templates/footer.php'; ?>