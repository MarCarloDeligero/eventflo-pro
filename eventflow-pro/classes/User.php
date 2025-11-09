<?php
class User {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth();
    }

    // Get user by ID
    public function getById($id) {
        $this->db->query('SELECT id, name, email, role, avatar, bio, phone, created_at 
                         FROM users WHERE id = :id AND is_active = 1');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

   // Update user profile
public function updateProfile($user_id, $data) {
    $sql = 'UPDATE users SET name = :name, bio = :bio, phone = :phone';
    $params = [
        ':name' => $data['name'],
        ':bio' => $data['bio'] ?? '',
        ':phone' => $data['phone'] ?? '',
        ':user_id' => $user_id
    ];

    // Handle avatar upload/removal
    if (isset($data['avatar'])) {
        $sql .= ', avatar = :avatar';
        $params[':avatar'] = $data['avatar'];
    } elseif (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
        $sql .= ', avatar = NULL';
    }

    $sql .= ' WHERE id = :user_id';

    $this->db->query($sql);
    foreach ($params as $key => $value) {
        $this->db->bind($key, $value);
    }

    return $this->db->execute();
}

    // Get user's registered events
    public function getRegisteredEvents($user_id) {
        $this->db->query('SELECT r.*, e.title, e.date, e.start_time, e.location, e.image, 
                         u.name as organizer_name, c.name as category_name
                         FROM registrations r
                         JOIN events e ON r.event_id = e.id
                         JOIN users u ON e.user_id = u.id
                         LEFT JOIN categories c ON e.category_id = c.id
                         WHERE r.user_id = :user_id
                         ORDER BY r.registration_date DESC');
        $this->db->bind(':user_id', $user_id);
        return $this->db->resultSet();
    }

    // Get user's created events count
    public function getEventsCount($user_id) {
        $this->db->query('SELECT COUNT(*) as count FROM events WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        return $this->db->single()->count;
    }

    // Get all users (for admin)
    public function getAll($page = 1, $per_page = 10) {
        $offset = ($page - 1) * $per_page;
        
        $this->db->query('SELECT id, name, email, role, avatar, is_active, created_at 
                         FROM users 
                         ORDER BY created_at DESC 
                         LIMIT :offset, :per_page');
        $this->db->bind(':offset', $offset);
        $this->db->bind(':per_page', $per_page);
        
        return $this->db->resultSet();
    }

    // Get users count
    public function getCount() {
        $this->db->query('SELECT COUNT(*) as count FROM users');
        return $this->db->single()->count;
    }

    // Update user role (admin only)
    public function updateRole($user_id, $role) {
        $this->db->query('UPDATE users SET role = :role WHERE id = :user_id');
        $this->db->bind(':role', $role);
        $this->db->bind(':user_id', $user_id);
        return $this->db->execute();
    }

    // Deactivate user
    public function deactivate($user_id) {
        $this->db->query('UPDATE users SET is_active = 0 WHERE id = :user_id');
        $this->db->bind(':user_id', $user_id);
        return $this->db->execute();
    }

    // Activate user
    public function activate($user_id) {
        $this->db->query('UPDATE users SET is_active = 1 WHERE id = :user_id');
        $this->db->bind(':user_id', $user_id);
        return $this->db->execute();
    }
}
?>