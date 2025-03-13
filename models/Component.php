<?php
namespace App\Models;

class Component {
    private $db;
    private $table = 'components';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAll($limit = 20, $offset = 0, $filters = []) {
        // Start building the query
        $query = "SELECT c.*, u.username as seller_name, cat.name as category_name 
                 FROM " . $this->table . " c
                 JOIN users u ON c.user_id = u.id
                 JOIN categories cat ON c.category_id = cat.id
                 WHERE 1=1";
        
        $params = [];
        
        // Add filters if provided
        if (isset($filters['category_id'])) {
            $query .= " AND c.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (isset($filters['language_id'])) {
            $query .= " AND c.id IN (SELECT component_id FROM component_supports WHERE language_id = :language_id)";
            $params[':language_id'] = $filters['language_id'];
        }
        
        if (isset($filters['framework_id'])) {
            $query .= " AND c.id IN (SELECT component_id FROM component_supports WHERE framework_id = :framework_id)";
            $params[':framework_id'] = $filters['framework_id'];
        }
        
        if (isset($filters['price_min'])) {
            $query .= " AND c.price >= :price_min";
            $params[':price_min'] = $filters['price_min'];
        }
        
        if (isset($filters['price_max'])) {
            $query .= " AND c.price <= :price_max";
            $params[':price_max'] = $filters['price_max'];
        }
        
        // Add pagination
        $query .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Prepare and execute
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            if ($param == ':limit' || $param == ':offset') {
                $stmt->bindValue($param, $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue($param, $value);
            }
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $query = "SELECT c.*, u.username as seller_name, cat.name as category_name 
                 FROM " . $this->table . " c
                 JOIN users u ON c.user_id = u.id
                 JOIN categories cat ON c.category_id = cat.id
                 WHERE c.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $component = $stmt->fetch();
        
        if ($component) {
            // Get component screenshots
            $query = "SELECT * FROM component_screenshots WHERE component_id = :component_id ORDER BY display_order";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':component_id', $id);
            $stmt->execute();
            $component['screenshots'] = $stmt->fetchAll();
            
            // Get component supported languages and frameworks
            $query = "SELECT cs.*, pl.name as language_name, f.name as framework_name 
                     FROM component_supports cs
                     LEFT JOIN programming_languages pl ON cs.language_id = pl.id
                     LEFT JOIN frameworks f ON cs.framework_id = f.id
                     WHERE cs.component_id = :component_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':component_id', $id);
            $stmt->execute();
            $component['supports'] = $stmt->fetchAll();
            
            // Get component tags
            $query = "SELECT t.* FROM tags t
                     JOIN component_tags ct ON t.id = ct.tag_id
                     WHERE ct.component_id = :component_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':component_id', $id);
            $stmt->execute();
            $component['tags'] = $stmt->fetchAll();
        }
        
        return $component;
    }
    
    public function getByUser($userId) {
        $query = "SELECT c.*, cat.name as category_name 
                 FROM " . $this->table . " c
                 JOIN categories cat ON c.category_id = cat.id
                 WHERE c.user_id = :user_id
                 ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getByCategory($categoryId) {
        $query = "SELECT c.*, u.username as seller_name 
                 FROM " . $this->table . " c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.category_id = :category_id
                 ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (title, slug, description, short_description, price, user_id, category_id, 
                   version, is_featured, is_approved, main_file_path, demo_url, documentation_url, preview_image) 
                  VALUES 
                  (:title, :slug, :description, :short_description, :price, :user_id, :category_id, 
                   :version, :is_featured, :is_approved, :main_file_path, :demo_url, :documentation_url, :preview_image)";
        
        $stmt = $this->db->prepare($query);
        
        // Clean and bind data
        $title = htmlspecialchars(strip_tags($data['title']));
        $slug = htmlspecialchars(strip_tags($data['slug']));
        $description = $data['description'];
        $shortDescription = isset($data['short_description']) ? htmlspecialchars(strip_tags($data['short_description'])) : null;
        $price = $data['price'];
        $userId = $data['user_id'];
        $categoryId = $data['category_id'];
        $version = isset($data['version']) ? htmlspecialchars(strip_tags($data['version'])) : '1.0.0';
        $isFeatured = isset($data['is_featured']) ? $data['is_featured'] : false;
        $isApproved = isset($data['is_approved']) ? $data['is_approved'] : false;
        $mainFilePath = isset($data['main_file_path']) ? htmlspecialchars(strip_tags($data['main_file_path'])) : '';
        $demoUrl = isset($data['demo_url']) ? htmlspecialchars(strip_tags($data['demo_url'])) : null;
        $documentationUrl = isset($data['documentation_url']) ? htmlspecialchars(strip_tags($data['documentation_url'])) : null;
        $previewImage = isset($data['preview_image']) ? htmlspecialchars(strip_tags($data['preview_image'])) : null;
        
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':short_description', $shortDescription);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->bindParam(':version', $version);
        $stmt->bindParam(':is_featured', $isFeatured, \PDO::PARAM_BOOL);
        $stmt->bindParam(':is_approved', $isApproved, \PDO::PARAM_BOOL);
        $stmt->bindParam(':main_file_path', $mainFilePath);
        $stmt->bindParam(':demo_url', $demoUrl);
        $stmt->bindParam(':documentation_url', $documentationUrl);
        $stmt->bindParam(':preview_image', $previewImage);
        
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
        if (isset($data['title'])) {
            $sets[] = "title = :title";
            $params[':title'] = htmlspecialchars(strip_tags($data['title']));
        }
        
        if (isset($data['slug'])) {
            $sets[] = "slug = :slug";
            $params[':slug'] = htmlspecialchars(strip_tags($data['slug']));
        }
        
        if (isset($data['description'])) {
            $sets[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['short_description'])) {
            $sets[] = "short_description = :short_description";
            $params[':short_description'] = htmlspecialchars(strip_tags($data['short_description']));
        }
        
        if (isset($data['price'])) {
            $sets[] = "price = :price";
            $params[':price'] = $data['price'];
        }
        
        if (isset($data['category_id'])) {
            $sets[] = "category_id = :category_id";
            $params[':category_id'] = $data['category_id'];
        }
        
        if (isset($data['version'])) {
            $sets[] = "version = :version";
            $params[':version'] = htmlspecialchars(strip_tags($data['version']));
        }
        
        if (isset($data['is_featured'])) {
            $sets[] = "is_featured = :is_featured";
            $params[':is_featured'] = $data['is_featured'];
        }
        
        if (isset($data['is_approved'])) {
            $sets[] = "is_approved = :is_approved";
            $params[':is_approved'] = $data['is_approved'];
        }
        
        if (isset($data['main_file_path'])) {
            $sets[] = "main_file_path = :main_file_path";
            $params[':main_file_path'] = htmlspecialchars(strip_tags($data['main_file_path']));
        }
        
        if (isset($data['demo_url'])) {
            $sets[] = "demo_url = :demo_url";
            $params[':demo_url'] = htmlspecialchars(strip_tags($data['demo_url']));
        }
        
        if (isset($data['documentation_url'])) {
            $sets[] = "documentation_url = :documentation_url";
            $params[':documentation_url'] = htmlspecialchars(strip_tags($data['documentation_url']));
        }
        
        if (isset($data['preview_image'])) {
            $sets[] = "preview_image = :preview_image";
            $params[':preview_image'] = htmlspecialchars(strip_tags($data['preview_image']));
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
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    public function search($query, $filters = []) {
        // Start building the query
        $searchQuery = "SELECT c.*, u.username as seller_name, cat.name as category_name 
                       FROM " . $this->table . " c
                       JOIN users u ON c.user_id = u.id
                       JOIN categories cat ON c.category_id = cat.id
                       WHERE (c.title LIKE :search OR c.description LIKE :search)";
        
        $params = [':search' => '%' . $query . '%'];
        
        // Add filters if provided
        if (isset($filters['category_id'])) {
            $searchQuery .= " AND c.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (isset($filters['language_id'])) {
            $searchQuery .= " AND c.id IN (SELECT component_id FROM component_supports WHERE language_id = :language_id)";
            $params[':language_id'] = $filters['language_id'];
        }
        
        if (isset($filters['framework_id'])) {
            $searchQuery .= " AND c.id IN (SELECT component_id FROM component_supports WHERE framework_id = :framework_id)";
            $params[':framework_id'] = $filters['framework_id'];
        }
        
        if (isset($filters['price_min'])) {
            $searchQuery .= " AND c.price >= :price_min";
            $params[':price_min'] = $filters['price_min'];
        }
        
        if (isset($filters['price_max'])) {
            $searchQuery .= " AND c.price <= :price_max";
            $params[':price_max'] = $filters['price_max'];
        }
        
        // Add order and limit
        $searchQuery .= " ORDER BY c.created_at DESC";
        
        if (isset($filters['limit'])) {
            $searchQuery .= " LIMIT :limit";
            $params[':limit'] = $filters['limit'];
        }
        
        if (isset($filters['offset'])) {
            $searchQuery .= " OFFSET :offset";
            $params[':offset'] = $filters['offset'];
        }
        
        // Prepare and execute
        $stmt = $this->db->prepare($searchQuery);
        
        foreach ($params as $param => $value) {
            if ($param == ':limit' || $param == ':offset') {
                $stmt->bindValue($param, $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue($param, $value);
            }
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
