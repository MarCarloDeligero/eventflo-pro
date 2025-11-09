<?php
class Category {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Get all categories
    public function getAll() {
        $this->db->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name');
        return $this->db->resultSet();
    }

    // Get category by ID
    public function getById($id) {
        $this->db->query('SELECT * FROM categories WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Create category
    public function create($data) {
        $this->db->query('INSERT INTO categories (name, description, color, icon) 
                         VALUES (:name, :description, :color, :icon)');
        
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description'] ?? '');
        $this->db->bind(':color', $data['color'] ?? '#007bff');
        $this->db->bind(':icon', $data['icon'] ?? 'fa-calendar');

        return $this->db->execute();
    }

    // Update category
    public function update($id, $data) {
        $this->db->query('UPDATE categories SET name = :name, description = :description, 
                         color = :color, icon = :icon, is_active = :is_active 
                         WHERE id = :id');
        
        $this->db->bind(':id', $id);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description'] ?? '');
        $this->db->bind(':color', $data['color'] ?? '#007bff');
        $this->db->bind(':icon', $data['icon'] ?? 'fa-calendar');
        $this->db->bind(':is_active', $data['is_active'] ?? 1);

        return $this->db->execute();
    }

    // Delete category (soft delete)
    public function delete($id) {
        $this->db->query('UPDATE categories SET is_active = 0 WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Get category statistics
    public function getStats() {
        $this->db->query('SELECT c.id, c.name, COUNT(e.id) as event_count 
                         FROM categories c 
                         LEFT JOIN events e ON c.id = e.category_id AND e.status = "published" 
                         WHERE c.is_active = 1 
                         GROUP BY c.id, c.name 
                         ORDER BY event_count DESC');
        return $this->db->resultSet();
    }
}
?>