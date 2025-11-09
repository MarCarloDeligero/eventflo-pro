<?php
class Event {
    private $db;
    public $id, $title, $description, $short_description, $date, $start_time, $end_time;
    public $location, $venue_name, $capacity, $price, $image, $category_id, $user_id;
    public $registration_deadline, $status, $is_featured, $created_at, $updated_at;
    public $category_name, $category_color, $organizer_name, $organizer_email, $organizer_avatar, $registered_count;

    public function __construct() {
        $this->db = new Database();
    }

    // Get all events with filters
    public function getAll($filters = []) {
        $query = 'SELECT e.*, c.name as category_name, c.color as category_color, 
                         u.name as organizer_name, u.email as organizer_email, u.avatar as organizer_avatar,
                         (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered_count
                  FROM events e 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE 1=1';
        
        // Apply filters
        if (isset($filters['status'])) {
            $query .= ' AND e.status = :status';
        }
        
        if (!empty($filters['category_id'])) {
            $query .= ' AND e.category_id = :category_id';
        }
        
        if (!empty($filters['search'])) {
            $query .= ' AND (e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)';
        }
        
        if (!empty($filters['date_from'])) {
            $query .= ' AND e.date >= :date_from';
        }
        
        if (!empty($filters['date_to'])) {
            $query .= ' AND e.date <= :date_to';
        }
        
        if (!empty($filters['user_id'])) {
            $query .= ' AND e.user_id = :user_id';
        }
        
        $query .= ' ORDER BY e.created_at DESC';
        
        $this->db->query($query);
        
        // Bind parameters
        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }
        
        if (!empty($filters['category_id'])) {
            $this->db->bind(':category_id', $filters['category_id']);
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $this->db->bind(':search', $searchTerm);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->bind(':date_from', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->bind(':date_to', $filters['date_to']);
        }
        
        if (!empty($filters['user_id'])) {
            $this->db->bind(':user_id', $filters['user_id']);
        }
        
        return $this->db->resultSet();
    }

    // Get featured events
    public function getFeatured($limit = 10) {
        $query = 'SELECT e.*, c.name as category_name, c.color as category_color, 
                         u.name as organizer_name, u.email as organizer_email, u.avatar as organizer_avatar,
                         (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered_count
                  FROM events e 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.is_featured = 1 AND e.status = "published" AND e.date >= CURDATE()
                  ORDER BY e.created_at DESC 
                  LIMIT :limit';
        
        $this->db->query($query);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    // Get upcoming events
    public function getUpcoming($limit = 10) {
        $query = 'SELECT e.*, c.name as category_name, c.color as category_color, 
                         u.name as organizer_name, u.email as organizer_email, u.avatar as organizer_avatar,
                         (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered_count
                  FROM events e 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.status = "published" AND e.date >= CURDATE()
                  ORDER BY e.date ASC, e.start_time ASC 
                  LIMIT :limit';
        
        $this->db->query($query);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    // Get events by organizer
    public function getEventsByOrganizer($user_id) {
        $query = 'SELECT e.*, c.name as category_name, c.color as category_color, 
                         u.name as organizer_name, u.email as organizer_email, u.avatar as organizer_avatar,
                         (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered_count
                  FROM events e 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.user_id = :user_id 
                  ORDER BY e.created_at DESC';
        
        $this->db->query($query);
        $this->db->bind(':user_id', $user_id);
        return $this->db->resultSet();
    }

    // Get event by ID
    public function getById($id) {
        $this->db->query('SELECT e.*, c.name as category_name, c.color as category_color, 
                                 u.name as organizer_name, u.email as organizer_email, u.avatar as organizer_avatar,
                                 (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered_count
                          FROM events e 
                          LEFT JOIN categories c ON e.category_id = c.id 
                          LEFT JOIN users u ON e.user_id = u.id 
                          WHERE e.id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Create new event
    public function createEvent($data) {
        $query = 'INSERT INTO events (title, description, short_description, date, start_time, end_time, 
                                     location, venue_name, capacity, price, image, category_id, user_id,
                                     registration_deadline, status, is_featured, created_at, updated_at) 
                  VALUES (:title, :description, :short_description, :date, :start_time, :end_time, 
                          :location, :venue_name, :capacity, :price, :image, :category_id, :user_id,
                          :registration_deadline, :status, :is_featured, NOW(), NOW())';
        
        $this->db->query($query);
        
        // Bind all parameters explicitly
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':short_description', $data['short_description']);
        $this->db->bind(':date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':location', $data['location']);
        $this->db->bind(':venue_name', $data['venue_name']);
        $this->db->bind(':capacity', $data['capacity']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':registration_deadline', $data['registration_deadline']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':is_featured', $data['is_featured']);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    // Update event
    public function update($id, $data) {
        $query = 'UPDATE events SET 
                 title = :title, description = :description, short_description = :short_description,
                 date = :date, start_time = :start_time, end_time = :end_time, 
                 location = :location, venue_name = :venue_name, capacity = :capacity,
                 price = :price, image = :image, category_id = :category_id,
                 registration_deadline = :registration_deadline, status = :status,
                 is_featured = :is_featured, updated_at = NOW() 
                 WHERE id = :id';

        $this->db->query($query);
        
        // Bind parameters
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':short_description', $data['short_description']);
        $this->db->bind(':date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':location', $data['location']);
        $this->db->bind(':venue_name', $data['venue_name']);
        $this->db->bind(':capacity', $data['capacity']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':registration_deadline', $data['registration_deadline']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':is_featured', $data['is_featured'] ?? 0);
        $this->db->bind(':id', $id);

        return $this->db->execute();
    }

    // Delete event
    public function delete($id) {
        $this->db->query('DELETE FROM events WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Get available spots
    public function getAvailableSpots($event_id) {
        $this->db->query('SELECT capacity FROM events WHERE id = :event_id');
        $this->db->bind(':event_id', $event_id);
        $event = $this->db->single();
        
        $this->db->query('SELECT COUNT(*) as count FROM registrations WHERE event_id = :event_id AND status IN ("registered", "attended")');
        $this->db->bind(':event_id', $event_id);
        $registrations = $this->db->single();
        
        return max(0, $event->capacity - $registrations->count);
    }

    // Check if user is registered
    public function isUserRegistered($event_id, $user_id) {
        $this->db->query('SELECT id FROM registrations WHERE event_id = :event_id AND user_id = :user_id AND status IN ("registered", "attended")');
        $this->db->bind(':event_id', $event_id);
        $this->db->bind(':user_id', $user_id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // Get registration count
    public function getRegistrationCount($event_id = null) {
        if ($event_id === null && isset($this->id)) {
            $event_id = $this->id;
        }
        
        $this->db->query('SELECT COUNT(*) as count FROM registrations WHERE event_id = :event_id AND status IN ("registered", "attended")');
        $this->db->bind(':event_id', $event_id);
        $result = $this->db->single();
        return $result->count;
    }
}
?>