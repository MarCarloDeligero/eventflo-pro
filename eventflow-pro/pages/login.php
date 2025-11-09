<?php
require_once '../includes/config.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = "Login to Your Account";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            throw new Exception('Please enter both email and password');
        }

        $user = $auth->login($email, $password);
        
        // Redirect to intended page or dashboard
        $redirect = $_GET['redirect'] ?? 'dashboard.php';
        header('Location: ' . $redirect);
        exit;
        
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
                    <i class="fas fa-sign-in-alt me-2"></i>Welcome Back
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
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
                                   required placeholder="Enter your password">
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="forgot-password.php" class="text-decoration-none">Forgot your password?</a>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">Don't have an account? 
                        <a href="register.php" class="text-decoration-none">Create one here</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Demo Accounts -->
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title">Demo Accounts</h6>
                <div class="row small text-muted">
                    <div class="col-6">
                        <strong>Admin:</strong><br>
                        admin@eventflow.com<br>
                        password: 123456
                    </div>
                    <div class="col-6">
                        <strong>Organizer:</strong><br>
                        organizer@eventflow.com<br>
                        password: 123456
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>