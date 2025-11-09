<?php
class Auth {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
    }

    // Register new user - UPDATED: No email verification
    public function register($data) {
        // Check if email already exists
        $this->db->query('SELECT id FROM users WHERE email = :email');
        $this->db->bind(':email', $data['email']);
        
        if ($this->db->single()) {
            throw new Exception('Email already registered');
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert user - UPDATED: Auto-verify email and no verification token
        $this->db->query('INSERT INTO users (name, email, password, email_verified) 
                         VALUES (:name, :email, :password, 1)');
        
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $hashedPassword);

        if ($this->db->execute()) {
            return [
                'user_id' => $this->db->lastInsertId()
            ];
        } else {
            throw new Exception('Registration failed');
        }
    }

    // Login user - UPDATED: No email verification check
    public function login($email, $password) {
        $this->db->query('SELECT * FROM users WHERE email = :email AND is_active = 1');
        $this->db->bind(':email', $email);
        
        $user = $this->db->single();

        if ($user && password_verify($password, $user->password)) {
            // REMOVED: Email verification check
            $this->setUserSession($user);
            return $user;
        }
        
        throw new Exception('Invalid credentials');
    }

    // Set user session
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['user_avatar'] = $user->avatar;
        $_SESSION['logged_in'] = true;
    }

    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Check if user has specific role
    public function hasRole($role) {
        if (!$this->isLoggedIn()) return false;
        return $_SESSION['user_role'] === $role;
    }

    // Get current user
   // Get current user - UPDATED to include all fields
public function getCurrentUser() {
    if (!$this->isLoggedIn()) return null;
    
    if (!$this->user) {
        $this->db->query('SELECT id, name, email, role, avatar, bio, phone, email_verified, is_active, created_at 
                         FROM users WHERE id = :id AND is_active = 1');
        $this->db->bind(':id', $_SESSION['user_id']);
        $this->user = $this->db->single();
    }
    
    return $this->user;
}

    // Logout user
    public function logout() {
        session_destroy();
        session_start(); // Start fresh session for flash messages
    }

    // Verify email - KEPT for compatibility but not needed
    public function verifyEmail($token) {
        // Auto-verify any token for development
        $this->db->query('SELECT id FROM users WHERE verification_token = :token');
        $this->db->bind(':token', $token);
        
        $user = $this->db->single();
        
        if ($user) {
            $this->db->query('UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :id');
            $this->db->bind(':id', $user->id);
            return $this->db->execute();
        }
        
        return false;
    }
}
?>