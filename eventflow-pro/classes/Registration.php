<?php
class Registration {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Register for event - UPDATED with duplicate handling
    public function register($event_id, $user_id, $guests_count = 0) {
        $this->db->beginTransaction();

        try {
            // Check if already registered - IMPROVED DUPLICATE HANDLING
            $this->db->query('SELECT id, status FROM registrations 
                             WHERE event_id = :event_id AND user_id = :user_id');
            $this->db->bind(':event_id', $event_id);
            $this->db->bind(':user_id', $user_id);
            $existing_registration = $this->db->single();

            if ($existing_registration) {
                // If already registered but cancelled, allow re-registration
                if ($existing_registration->status === 'cancelled') {
                    // Update the existing cancelled registration
                    $this->db->query('UPDATE registrations SET 
                                     status = "registered", 
                                     guests_count = :guests_count,
                                     cancellation_reason = NULL,
                                     registration_date = NOW() 
                                     WHERE id = :id');
                    $this->db->bind(':guests_count', $guests_count);
                    $this->db->bind(':id', $existing_registration->id);
                    
                    if ($this->db->execute()) {
                        $this->db->commit();
                        return [
                            'status' => 'registered', 
                            'ticket_number' => $this->getTicketNumber($existing_registration->id),
                            'registration_id' => $existing_registration->id
                        ];
                    }
                } else {
                    throw new Exception('You are already registered for this event');
                }
            }

            // Check available spots
            $event = new Event();
            $available_spots = $event->getAvailableSpots($event_id);
            
            if ($available_spots <= 0) {
                // Add to waitlist
                $this->addToWaitlist($event_id, $user_id);
                $this->db->commit();
                return ['status' => 'waitlisted', 'message' => 'Added to waitlist'];
            }

            // Check if enough spots available including guests
            if ($guests_count + 1 > $available_spots) {
                throw new Exception('Not enough spots available for your registration plus guests');
            }

            // Generate ticket number
            $ticket_number = 'TKT-' . strtoupper(uniqid());

            // Create registration
            $this->db->query('INSERT INTO registrations (user_id, event_id, ticket_number, guests_count) 
                             VALUES (:user_id, :event_id, :ticket_number, :guests_count)');
            
            $this->db->bind(':user_id', $user_id);
            $this->db->bind(':event_id', $event_id);
            $this->db->bind(':ticket_number', $ticket_number);
            $this->db->bind(':guests_count', $guests_count);

            if (!$this->db->execute()) {
                throw new Exception('Registration failed');
            }

            $registration_id = $this->db->lastInsertId();
            
            $this->db->commit();
            return [
                'status' => 'registered', 
                'ticket_number' => $ticket_number,
                'registration_id' => $registration_id
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Helper method to get ticket number
    private function getTicketNumber($registration_id) {
        $this->db->query('SELECT ticket_number FROM registrations WHERE id = :id');
        $this->db->bind(':id', $registration_id);
        $result = $this->db->single();
        return $result ? $result->ticket_number : 'TKT-' . strtoupper(uniqid());
    }

    // Check if user is registered - UPDATED to exclude cancelled registrations
    public function isRegistered($event_id, $user_id) {
        $this->db->query('SELECT id FROM registrations 
                         WHERE event_id = :event_id AND user_id = :user_id AND status IN ("registered", "attended")');
        $this->db->bind(':event_id', $event_id);
        $this->db->bind(':user_id', $user_id);
        return $this->db->single() ? true : false;
    }

    // Cancel registration
    public function cancel($registration_id, $user_id, $reason = '') {
        $this->db->beginTransaction();

        try {
            // First, get registration details to verify ownership and get event_id
            $this->db->query('SELECT * FROM registrations WHERE id = :id AND user_id = :user_id');
            $this->db->bind(':id', $registration_id);
            $this->db->bind(':user_id', $user_id);
            $registration = $this->db->single();
            
            if (!$registration) {
                throw new Exception('Registration not found or access denied');
            }

            // Update registration status
            $this->db->query('UPDATE registrations SET status = "cancelled", cancellation_reason = :reason WHERE id = :id AND user_id = :user_id');
            
            $this->db->bind(':id', $registration_id);
            $this->db->bind(':user_id', $user_id);
            $this->db->bind(':reason', $reason);

            if (!$this->db->execute()) {
                throw new Exception('Cancellation failed');
            }

            // Promote first person from waitlist
            $this->promoteFromWaitlist($registration_id);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Add to waitlist
    private function addToWaitlist($event_id, $user_id) {
        // Get current waitlist position
        $this->db->query('SELECT COALESCE(MAX(position), 0) + 1 as new_position FROM waitlist WHERE event_id = :event_id');
        $this->db->bind(':event_id', $event_id);
        $position = $this->db->single()->new_position;

        $this->db->query('INSERT INTO waitlist (user_id, event_id, position) VALUES (:user_id, :event_id, :position)');
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':event_id', $event_id);
        $this->db->bind(':position', $position);

        return $this->db->execute();
    }

    // Promote from waitlist - FIXED: Added proper error handling
    private function promoteFromWaitlist($registration_id) {
        try {
            // Get event ID from registration
            $this->db->query('SELECT event_id FROM registrations WHERE id = :id');
            $this->db->bind(':id', $registration_id);
            $result = $this->db->single();
            
            if (!$result) {
                return false;
            }
            
            $event_id = $result->event_id;

            // Get first person in waitlist
            $this->db->query('SELECT * FROM waitlist WHERE event_id = :event_id AND status = "waiting" ORDER BY position ASC LIMIT 1');
            $this->db->bind(':event_id', $event_id);
            $waitlist_entry = $this->db->single();

            if ($waitlist_entry) {
                // Check if there are available spots
                $event = new Event();
                $available_spots = $event->getAvailableSpots($event_id);
                
                if ($available_spots > 0) {
                    // Register the waitlisted user using a direct method to avoid recursion
                    $ticket_number = 'TKT-' . strtoupper(uniqid());
                    
                    $this->db->query('INSERT INTO registrations (user_id, event_id, ticket_number, guests_count) 
                                     VALUES (:user_id, :event_id, :ticket_number, 0)');
                    $this->db->bind(':user_id', $waitlist_entry->user_id);
                    $this->db->bind(':event_id', $event_id);
                    $this->db->bind(':ticket_number', $ticket_number);
                    
                    if ($this->db->execute()) {
                        // Update waitlist entry
                        $this->db->query('UPDATE waitlist SET status = "promoted", promoted_at = NOW() WHERE id = :id');
                        $this->db->bind(':id', $waitlist_entry->id);
                        $this->db->execute();
                        return true;
                    }
                }
            }
            return false;
            
        } catch (Exception $e) {
            // Log error but don't break the cancellation
            error_log("Waitlist promotion error: " . $e->getMessage());
            return false;
        }
    }

    // ===== UPDATED CHECK-IN METHOD (SINGLE VERSION) =====
    public function checkIn($registration_id, $user_id = null) {
        // If user_id is provided, it's a user checking themselves in
        // If user_id is null, it's an organizer/admin checking someone in
        if ($user_id !== null) {
            $this->db->query('UPDATE registrations SET status = "attended", check_in_time = NOW() 
                             WHERE id = :id AND user_id = :user_id');
            $this->db->bind(':id', $registration_id);
            $this->db->bind(':user_id', $user_id);
        } else {
            $this->db->query('UPDATE registrations SET status = "attended", check_in_time = NOW() 
                             WHERE id = :id');
            $this->db->bind(':id', $registration_id);
        }
        return $this->db->execute();
    }

    // Get registration by ID
    public function getById($registration_id) {
        $this->db->query('SELECT r.*, e.title, e.date, e.start_time, e.location, u.name as user_name, u.email as user_email
                        FROM registrations r
                        JOIN events e ON r.event_id = e.id
                        JOIN users u ON r.user_id = u.id
                        WHERE r.id = :id');
        $this->db->bind(':id', $registration_id);
        return $this->db->single();
    }

    // Get event registrations
    public function getEventRegistrations($event_id) {
        $this->db->query('SELECT r.*, u.name, u.email, u.phone
                         FROM registrations r
                         JOIN users u ON r.user_id = u.id
                         WHERE r.event_id = :event_id AND r.status IN ("registered", "attended")
                         ORDER BY r.registration_date DESC');
        $this->db->bind(':event_id', $event_id);
        return $this->db->resultSet();
    }

    // NEW: Get user's registration for a specific event
    public function getUserRegistrationForEvent($event_id, $user_id) {
        $this->db->query('SELECT * FROM registrations 
                         WHERE event_id = :event_id AND user_id = :user_id');
        $this->db->bind(':event_id', $event_id);
        $this->db->bind(':user_id', $user_id);
        return $this->db->single();
    }

    // ===== NEW METHODS FOR EVENT-REGISTRATIONS.PHP =====

    // Get all registrations for a specific event (with user details)
    public function getRegistrationsByEvent($event_id) {
        $this->db->query('SELECT r.*, u.name as user_name, u.email as user_email, 
                         u.phone as user_phone, u.avatar as user_avatar
                  FROM registrations r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.event_id = :event_id 
                  ORDER BY r.registration_date DESC');
        
        $this->db->bind(':event_id', $event_id);
        return $this->db->resultSet();
    }

    // Get registration statistics for an event
    public function getEventRegistrationStats($event_id) {
        $this->db->query('SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "registered" THEN 1 ELSE 0 END) as registered,
                    SUM(CASE WHEN status = "attended" THEN 1 ELSE 0 END) as attended,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN check_in_time IS NOT NULL THEN 1 ELSE 0 END) as checked_in
                  FROM registrations 
                  WHERE event_id = :event_id');
        
        $this->db->bind(':event_id', $event_id);
        $result = $this->db->single();
        
        // Ensure all keys exist
        return [
            'total' => $result->total ?? 0,
            'confirmed' => ($result->registered ?? 0) + ($result->attended ?? 0), // Combine registered and attended
            'pending' => 0, // Your system doesn't have pending status
            'cancelled' => $result->cancelled ?? 0,
            'checked_in' => $result->checked_in ?? 0
        ];
    }

    // Update registration status (for organizer/admin)
    public function updateStatus($registration_id, $status) {
        $this->db->query('UPDATE registrations SET status = :status WHERE id = :id');
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $registration_id);
        return $this->db->execute();
    }

    // Delete a registration (for organizer/admin)
    public function delete($registration_id) {
        $this->db->query('DELETE FROM registrations WHERE id = :id');
        $this->db->bind(':id', $registration_id);
        return $this->db->execute();
    }

    // Get user's registrations - UPDATED WITH ALL FIELDS
    public function getUserRegistrations($user_id) {
        $this->db->query('SELECT r.*, e.title as event_title, e.date as event_date, e.start_time, 
                         e.location, e.image, e.price, c.name as category_name,
                         e.venue_name, e.end_time, e.capacity
                  FROM registrations r 
                  JOIN events e ON r.event_id = e.id 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  WHERE r.user_id = :user_id 
                  ORDER BY r.registration_date DESC');
        
        $this->db->bind(':user_id', $user_id);
        return $this->db->resultSet();
    }

    // Get registration count for an event
    public function getRegistrationCount($event_id) {
        $this->db->query('SELECT COUNT(*) as count FROM registrations WHERE event_id = :event_id AND status IN ("registered", "attended")');
        $this->db->bind(':event_id', $event_id);
        $result = $this->db->single();
        return $result->count;
    }

    // Update registration notes
    public function updateNotes($registration_id, $notes) {
        $this->db->query('UPDATE registrations SET notes = :notes WHERE id = :id');
        $this->db->bind(':notes', $notes);
        $this->db->bind(':id', $registration_id);
        return $this->db->execute();
    }

    // Get waitlisted registrations for an event
    public function getWaitlistedRegistrations($event_id) {
        $this->db->query('SELECT w.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                  FROM waitlist w 
                  JOIN users u ON w.user_id = u.id 
                  WHERE w.event_id = :event_id AND w.status = "waiting" 
                  ORDER BY w.position ASC');
        
        $this->db->bind(':event_id', $event_id);
        return $this->db->resultSet();
    }

    // Check if user is registered for event (compatibility method)
    public function isUserRegistered($event_id, $user_id) {
        return $this->isRegistered($event_id, $user_id);
    }

    // Create new registration (alternative method)
    public function create($data) {
        $ticket_number = 'TKT-' . strtoupper(uniqid());
        
        $this->db->query('INSERT INTO registrations (event_id, user_id, ticket_number, guests_count, status, registration_date) 
                  VALUES (:event_id, :user_id, :ticket_number, :guests_count, :status, NOW())');
        
        $this->db->bind(':event_id', $data['event_id']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':ticket_number', $ticket_number);
        $this->db->bind(':guests_count', $data['guests_count'] ?? 0);
        $this->db->bind(':status', $data['status'] ?? 'registered');
        
        return $this->db->execute();
    }
}
?>