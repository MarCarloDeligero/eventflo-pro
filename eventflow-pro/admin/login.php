<?php
require_once '../includes/config.php';

$auth = new Auth();
if ($auth->isLoggedIn() && $auth->hasRole('super_admin')) {
    header('Location: index.php');
    exit;
}

$page_title = "Admin Login";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            throw new Exception('Please enter both email and password');
        }

        $user = $auth->login($email, $password);
        
        // Check if user is super admin
        if (!$auth->hasRole('super_admin')) {
            $auth->logout();
            throw new Exception('Access denied. Admin privileges required.');
        }
        
        // Redirect to admin dashboard
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .admin-login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="admin-login-card">
                    <div class="admin-header">
                        <h1><i class="fas fa-crown me-2"></i>Admin Panel</h1>
                        <p class="mb-0"><?php echo SITE_NAME; ?> Administration</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Admin Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           required placeholder="Enter admin email">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required placeholder="Enter password">
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-2">
                                <a href="../pages/login.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Back to User Login
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Default Admin Credentials -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h6>Default Admin Account</h6>
                        <p class="mb-1 small text-muted">admin@eventflow.com</p>
                        <p class="mb-0 small text-muted">Password: 123456</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>