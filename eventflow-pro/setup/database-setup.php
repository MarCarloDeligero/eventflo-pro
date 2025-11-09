<?php
/**
 * Database Setup Script for EventFlow Pro
 * Run this once to set up your database with sample data
 */

require_once '../includes/config.php';

// Check if already installed
try {
    $db = new Database();
    $db->query('SELECT 1 FROM users LIMIT 1');
    $db->execute();
    
    echo "<div class='alert alert-warning'>Database already seems to be set up. If you want to reset, please drop the database first.</div>";
    exit;
} catch (Exception $e) {
    // Database doesn't exist yet, continue with setup
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventFlow Pro - Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="card-title mb-0 text-center">
                            <i class="fas fa-database me-2"></i>EventFlow Pro - Database Setup
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            setupDatabase();
                        } else {
                            showSetupForm();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function showSetupForm() {
    ?>
    <p class="text-muted mb-4">This setup will create the necessary database tables and add sample data for EventFlow Pro.</p>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Before you start:</strong> Make sure your database credentials in <code>includes/config.php</code> are correct.
    </div>
    
    <form method="POST">
        <div class="mb-3">
            <label for="admin_email" class="form-label">Admin Email</label>
            <input type="email" class="form-control" id="admin_email" name="admin_email" 
                   value="admin@eventflow.com" required>
        </div>
        
        <div class="mb-3">
            <label for="admin_password" class="form-label">Admin Password</label>
            <input type="password" class="form-control" id="admin_password" name="admin_password" 
                   value="123456" required minlength="6">
            <div class="form-text">Minimum 6 characters</div>
        </div>
        
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="sample_data" name="sample_data" checked>
            <label class="form-check-label" for="sample_data">
                Include sample events and users
            </label>
        </div>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-play me-2"></i>Run Setup
            </button>
        </div>
    </form>
    <?php
}

function setupDatabase() {
    try {
        $db = new Database();
        
        echo "<div class='alert alert-info'><i class='fas fa-cog fa-spin me-2'></i>Starting database setup...</div>";
        
        // Run table creation
        createTables($db);
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>Database tables created successfully</div>";
        
        // Insert default data
        insertDefaultData($db, $_POST['admin_email'], $_POST['admin_password']);
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>Default data inserted</div>";
        
        if (isset($_POST['sample_data'])) {
            insertSampleData($db);
            echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>Sample data inserted</div>";
        }
        
        echo "<div class='alert alert-success'>";
        echo "<h5><i class='fas fa-check-circle me-2'></i>Setup Complete!</h5>";
        echo "<p class='mb-2'>EventFlow Pro has been successfully installed.</p>";
        echo "<p class='mb-0'><strong>Admin Login:</strong> " . htmlspecialchars($_POST['admin_email']) . " / " . htmlspecialchars($_POST['admin_password']) . "</p>";
        echo "</div>";
        
        echo "<div class='text-center mt-4'>";
        echo "<a href='../index.php' class='btn btn-primary me-3'>Go to Website</a>";
        echo "<a href='../admin/' class='btn btn-outline-primary'>Go to Admin Panel</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h5><i class='fas fa-exclamation-triangle me-2'></i>Setup Failed</h5>";
        echo "<p class='mb-0'>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

function createTables($db) {
    // SQL to create all tables (same as previous SQL but in PHP)
    $tables_sql = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'organizer', 'attendee') DEFAULT 'attendee',
            avatar VARCHAR(255) NULL,
            bio TEXT NULL,
            phone VARCHAR(20) NULL,
            email_verified BOOLEAN DEFAULT FALSE,
            verification_token VARCHAR(100) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            description TEXT NULL,
            color VARCHAR(7) DEFAULT '#007bff',
            icon VARCHAR(50) DEFAULT 'fa-calendar',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            short_description VARCHAR(500) NULL,
            date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            location VARCHAR(255) NOT NULL,
            venue_name VARCHAR(100) NULL,
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            capacity INT NOT NULL,
            price DECIMAL(10, 2) DEFAULT 0.00,
            image VARCHAR(255) NULL,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
            is_featured BOOLEAN DEFAULT FALSE,
            is_recurring BOOLEAN DEFAULT FALSE,
            recurrence_pattern ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
            max_registrations INT NULL,
            registration_deadline DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
        )",
        
        "CREATE TABLE IF NOT EXISTS registrations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            status ENUM('registered', 'attended', 'cancelled', 'no_show') DEFAULT 'registered',
            ticket_number VARCHAR(20) UNIQUE NOT NULL,
            guests_count INT DEFAULT 0,
            special_requirements TEXT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            check_in_time TIMESTAMP NULL,
            cancellation_reason TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            UNIQUE KEY unique_registration (user_id, event_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS waitlist (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            position INT NOT NULL,
            status ENUM('waiting', 'promoted', 'cancelled') DEFAULT 'waiting',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            promoted_at TIMESTAMP NULL,
            notified_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS payments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            payment_method ENUM('stripe', 'paypal', 'bank_transfer', 'cash') NULL,
            transaction_id VARCHAR(255) NULL,
            payer_email VARCHAR(255) NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error', 'system') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            action_url VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            key_name VARCHAR(100) UNIQUE NOT NULL,
            value TEXT NOT NULL,
            description TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    ];
    
    foreach ($tables_sql as $sql) {
        $db->query($sql);
        $db->execute();
    }
}

function insertDefaultData($db, $admin_email, $admin_password) {
    // Insert admin user
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $db->query('INSERT INTO users (name, email, password, role, email_verified) VALUES (:name, :email, :password, :role, 1)');
    $db->bind(':name', 'System Administrator');
    $db->bind(':email', $admin_email);
    $db->bind(':password', $hashed_password);
    $db->bind(':role', 'super_admin');
    $db->execute();
    
    // Insert categories
    $categories = [
        ['Conference', '#007bff', 'fa-users'],
        ['Workshop', '#28a745', 'fa-tools'],
        ['Networking', '#6f42c1', 'fa-handshake'],
        ['Seminar', '#fd7e14', 'fa-chalkboard-teacher'],
        ['Social', '#e83e8c', 'fa-glass-cheers'],
        ['Training', '#20c997', 'fa-graduation-cap'],
        ['Meetup', '#17a2b8', 'fa-meetup']
    ];
    
    foreach ($categories as $category) {
        $db->query('INSERT INTO categories (name, color, icon) VALUES (:name, :color, :icon)');
        $db->bind(':name', $category[0]);
        $db->bind(':color', $category[1]);
        $db->bind(':icon', $category[2]);
        $db->execute();
    }
    
    // Insert settings
    $settings = [
        ['site_name', 'EventFlow Pro', 'Website name'],
        ['site_email', 'noreply@eventflow.com', 'System email address'],
        ['registration_open', '1', 'Whether event registration is open globally'],
        ['max_events_per_user', '10', 'Maximum events a user can create'],
        ['auto_approve_events', '0', 'Whether events are auto-approved']
    ];
    
    foreach ($settings as $setting) {
        $db->query('INSERT INTO settings (key_name, value, description) VALUES (:key, :value, :desc)');
        $db->bind(':key', $setting[0]);
        $db->bind(':value', $setting[1]);
        $db->bind(':desc', $setting[2]);
        $db->execute();
    }
}

function insertSampleData($db) {
    // Insert sample organizer
    $db->query('INSERT INTO users (name, email, password, role, email_verified) VALUES (:name, :email, :password, :role, 1)');
    $db->bind(':name', 'Event Organizer');
    $db->bind(':email', 'organizer@eventflow.com');
    $db->bind(':password', password_hash('123456', PASSWORD_DEFAULT));
    $db->bind(':role', 'organizer');
    $db->execute();
    $organizer_id = $db->lastInsertId();
    
    // Insert sample attendees
    $attendees = [
        ['John Doe', 'john@example.com'],
        ['Jane Smith', 'jane@example.com'],
        ['Mike Johnson', 'mike@example.com'],
        ['Sarah Wilson', 'sarah@example.com']
    ];
    
    foreach ($attendees as $attendee) {
        $db->query('INSERT INTO users (name, email, password, email_verified) VALUES (:name, :email, :password, 1)');
        $db->bind(':name', $attendee[0]);
        $db->bind(':email', $attendee[1]);
        $db->bind(':password', password_hash('123456', PASSWORD_DEFAULT));
        $db->execute();
    }
    
    // Insert sample events
    $sample_events = [
        [
            'Tech Conference 2024',
            'Join us for the biggest tech conference of the year! Featuring keynote speakers, workshops, and networking opportunities with industry leaders.',
            'Annual technology conference showcasing the latest innovations',
            date('Y-m-d', strtotime('+30 days')),
            '09:00:00',
            '17:00:00',
            'Convention Center, 123 Tech Street, San Francisco, CA',
            'SF Convention Center',
            100,
            199.00,
            1, // category_id for Conference
            $organizer_id
        ],
        [
            'Web Development Workshop',
            'Hands-on workshop covering modern web development technologies including React, Node.js, and MongoDB.',
            'Learn full-stack web development in this intensive workshop',
            date('Y-m-d', strtotime('+15 days')),
            '10:00:00',
            '16:00:00',
            'Tech Hub, 456 Developer Ave, San Francisco, CA',
            'Tech Hub SF',
            25,
            99.00,
            2, // category_id for Workshop
            $organizer_id
        ],
        [
            'Startup Networking Mixer',
            'Connect with entrepreneurs, investors, and tech professionals in a relaxed networking environment.',
            'Networking event for startup enthusiasts',
            date('Y-m-d', strtotime('+7 days')),
            '18:00:00',
            '21:00:00',
            'Innovation Lounge, 789 Startup Blvd, San Francisco, CA',
            'Innovation Lounge',
            50,
            0.00,
            3, // category_id for Networking
            $organizer_id
        ]
    ];
    
    foreach ($sample_events as $event) {
        $db->query('INSERT INTO events (title, description, short_description, date, start_time, end_time, location, venue_name, capacity, price, category_id, user_id, status, is_featured) 
                   VALUES (:title, :desc, :short_desc, :date, :start_time, :end_time, :location, :venue, :capacity, :price, :category_id, :user_id, "published", :featured)');
        
        $db->bind(':title', $event[0]);
        $db->bind(':desc', $event[1]);
        $db->bind(':short_desc', $event[2]);
        $db->bind(':date', $event[3]);
        $db->bind(':start_time', $event[4]);
        $db->bind(':end_time', $event[5]);
        $db->bind(':location', $event[6]);
        $db->bind(':venue', $event[7]);
        $db->bind(':capacity', $event[8]);
        $db->bind(':price', $event[9]);
        $db->bind(':category_id', $event[10]);
        $db->bind(':user_id', $event[11]);
        $db->bind(':featured', $event === $sample_events[0] ? 1 : 0); // First event is featured
        
        $db->execute();
    }
}
?>