<?php
// Admin sidebar component
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 admin-sidebar sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h5 class="text-white">
                <i class="fas fa-cog me-2"></i>Admin Tools
            </h5>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $current_page == 'events.php' ? 'active' : ''; ?>" href="events.php">
                    <i class="fas fa-calendar me-2"></i>Events
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users me-2"></i>Users
                </a>
            </li>
            
        </ul>
        
        <div class="mt-4">
            <a href="../pages/dashboard.php" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-external-link-alt me-2"></i>View Frontend
            </a>
        </div>
    </div>
</div>