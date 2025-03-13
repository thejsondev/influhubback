<?php
namespace App\Models;

class Review {
    private $db;
    private $table = 'reviews';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getByComponent($componentId) {
        $query = "SELECT r.*, 
                 u.username as reviewer_name,
                 u.avatar as reviewer_avatar
                 FROM " . $this->table . " r
                 JOIN users u ON r.user_id = u.id
                 WHERE r.component_id = :component_id
                 ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':component_id', $componentId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getByUser($userId) {
        $query = "SELECT r.*, 
                 c.title as component_title,
                 c.slug as component_slug
                 FROM " . $this->table . " r
                 JOIN components c ON r.component_id = c.id
                 WHERE r.user_id = :user_id
                 ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $query = "SELECT r.*, 
                 u.username as reviewer_name,
                 u.avatar as reviewer_avatar,
                 c.title as component_title,
                 c.slug as component_slug
                 FROM " . $this->table . " r
                 JOIN users u ON r.user_id = u.id
                 JOIN components c ON r.component_id = c.id
                 WHERE r.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function create($data) {
        // Check if user already reviewed this component
        $query = "SELECT id FROM " . $this->table . " 
                 WHERE user_id = :user_id AND component_id = :component_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':component_id', $data['component_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // User already reviewed this component, update instead
            $review = $stmt->fetch();
            return $this->update($review['id'], $data);
        }
        
        // Create new review
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, component_id, rating, title, comment) 
                  VALUES 
                  (:user_id, :component_id, :rating, :title, :comment)";
        
        $stmt = $this->db->prepare($query);
        
        // Clean and bind data
        $userId = $data['user_id'];
        $componentId = $data['component_id'];
        $rating = $data['rating'];
        $title = isset($data['title']) ? htmlspecialchars(strip_tags($data['title'])) : null;
        $comment = htmlspecialchars(strip_tags($data['comment']));
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':component_id', $componentId);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':comment', $comment);
        
        if ($stmt->execute()) {
            // Update component average rating
            $this->updateComponentRating($componentId);
            
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
        if (isset($data['rating'])) {
            $sets[] = "rating = :rating";
            $params[':rating'] = $data['rating'];
        }
        
        if (isset($data['title'])) {
            $sets[] = "title = :title";
            $params[':title'] = htmlspecialchars(strip_tags($data['title']));
        }
        
        if (isset($data['comment'])) {
            $sets[] = "comment = :comment";
            $params[':comment'] = htmlspecialchars(strip_tags($data['comment']));
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
        
        $result = $stmt->execute();
        
        if ($result) {
            // Get component ID for this review
            $query = "SELECT component_id FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $review = $stmt->fetch();
            
            // Update component average rating
            $this->updateComponentRating($review['component_id']);
        }
        
        return $result;
    }
    
    public function delete($id) {
        // Get component ID for this review
        $query = "SELECT component_id FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $review = $stmt->fetch();
        
        // Delete the review
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();
        
        if ($result && $review) {
            // Update component average rating
            $this->updateComponentRating($review['component_id']);
        }
        
        return $result;
    }
    
    private function updateComponentRating($componentId) {
        // Calculate average rating
        $query = "SELECT AVG(rating) as avg_rating FROM " . $this->table . " WHERE component_id = :component_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':component_id', $componentId);
        $stmt->execute();
        $result = $stmt->fetch();
        
        // Update component with new average rating
        if ($result) {
            $avgRating = $result['avg_rating'] ? $result['avg_rating'] : 0;
            
            // This would be added to the components table in a real implementation
            // For now, we'll just return the calculated average
            return $avgRating;
        }
        
        return 0;
    }
}
