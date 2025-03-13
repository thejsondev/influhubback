<?php
namespace App\Models;

class Category {
    private $db;
    private $table = 'categories';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAll() {
        $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM components WHERE category_id = c.id) as component_count,
                 p.name as parent_name
                 FROM " . $this->table . " c
                 LEFT JOIN " . $this->table . " p ON c.parent_id = p.id
                 ORDER BY c.name ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM components WHERE category_id = c.id) as component_count,
                 p.name as parent_name
                 FROM " . $this->table . " c
                 LEFT JOIN " . $this->table . " p ON c.parent_id = p.id
                 WHERE c.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function getBySlug($slug) {
        $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM components WHERE category_id = c.id) as component_count,
                 p.name as parent_name
                 FROM " . $this->table . " c
                 LEFT JOIN " . $this->table . " p ON c.parent_id = p.id
                 WHERE c.slug = :slug";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, slug, description, parent_id) 
                  VALUES 
                  (:name, :slug, :description, :parent_id)";
        
        $stmt = $this->db->prepare($query);
        
        // Clean and bind data
        $name = htmlspecialchars(strip_tags($data['name']));
        $slug = htmlspecialchars(strip_tags($data['slug']));
        $description = isset($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : null;
        $parentId = isset($data['parent_id']) ? $data['parent_id'] : null;
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':parent_id', $parentId);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update($id, $data) {
        // Start building the query
        $query = "UPDATE " . $this->table . " SET ";
        $sets = [];
        $params = [];
        
        // Add fields to update
        if (isset($data['name'])) {
            $sets[] = "name = :name";
            $params[':name'] = htmlspecialchars(strip_tags($data['name']));
        }
        
        if (isset($data['slug'])) {
            $sets[] = "slug = :slug";
            $params[':slug'] = htmlspecialchars(strip_tags($data['slug']));
        }
        
        if (isset($data['description'])) {
            $sets[] = "description = :description";
            $params[':description'] = htmlspecialchars(strip_tags($data['description']));
        }
        
        if (array_key_exists('parent_id', $data)) {
            $sets[] = "parent_id = :parent_id";
            $params[':parent_id'] = $data['parent_id'];
        }
        
        // If no fields to update
        if (empty($sets)) {
            return false;
        }
        
        // Complete the query
        $query .= implode(', ', $sets) . " WHERE id = :id";
        $params[':id'] = $id;
        
        // Prepare and execute
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        // First update any components in this category to uncategorized
        $query = "UPDATE components SET category_id = 1 WHERE category_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Then update any child categories to have no parent
        $query = "UPDATE " . $this->table . " SET parent_id = NULL WHERE parent_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Finally delete the category
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}
