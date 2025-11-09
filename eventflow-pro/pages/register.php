<?php
require_once '../includes/config.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Create Account";
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'],
            'confirm_password' => $_POST['confirm_password']
        ];

        // Validation
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception('All fields are required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }

        if (strlen($data['password']) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }

        if ($data['password'] !== $data['confirm_password']) {
            throw new Exception('Passwords do not match');
        }

        // Register user
        $result = $auth->register($data);
        
        $success = 'Registration successful! Please check your email to verify your account.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title text-center mb-0">
                    <i class="fas fa-user-plus me-2"></i>Create Your Account
                </h4>
            </div>
            <div class="card-body p-4">
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

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required placeholder="Enter your full name">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required placeholder="Enter your email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required placeholder="Enter your password" minlength="6">
                        </div>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required placeholder="Confirm your password">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? 
                        <a href="login.php" class="text-decoration-none">Sign in here</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="row mt-4 text-center">
            <div class="col-md-4">
                <div class="text-primary">
                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                    <h6>Discover Events</h6>
                    <small class="text-muted">Find amazing events near you</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-success">
                    <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                    <h6>Easy Registration</h6>
                    <small class="text-muted">Register with one click</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-warning">
                    <i class="fas fa-bell fa-2x mb-2"></i>
                    <h6>Get Notified</h6>
                    <small class="text-muted">Never miss an event</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>