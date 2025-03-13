<?php
namespace App\Models;

class Purchase {
    private $db;
    private $table = 'purchases';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAll($userId = null) {
        $query = "SELECT p.*, 
                 u.username as buyer_name, 
                 c.title as component_title,
                 c.slug as component_slug,
                 c.preview_image as component_image,
                 s.username as seller_name
                 FROM " . $this->table . " p
                 JOIN users u ON p.user_id = u.id
                 JOIN components c ON p.component_id = c.id
                 JOIN users s ON c.user_id = s.id";
        
        if ($userId) {
            $query .= " WHERE p.user_id = :user_id";
        }
        
        $query .= " ORDER BY p.purchase_date DESC";
        
        $stmt = $this->db->prepare($query);
        
        if ($userId) {
            $stmt->bindParam(':user_id', $userId);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $query = "SELECT p.*, 
                 u.username as buyer_name, 
                 c.title as component_title,
                 c.slug as component_slug,
                 c.preview_image as component_image,
                 c.main_file_path,
                 s.username as seller_name
                 FROM " . $this->table . " p
                 JOIN users u ON p.user_id = u.id
                 JOIN components c ON p.component_id = c.id
                 JOIN users s ON c.user_id = s.id
                 WHERE p.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $purchase = $stmt->fetch();
        
        if ($purchase) {
            // Get download history
            $query = "SELECT * FROM downloads WHERE purchase_id = :purchase_id ORDER BY download_date DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':purchase_id', $id);
            $stmt->execute();
            $purchase['downloads'] = $stmt->fetchAll();
        }
        
        return $purchase;
    }
    
    public function getByUser($userId) {
        $query = "SELECT p.*, 
                 c.title as component_title,
                 c.slug as component_slug,
                 c.preview_image as component_image,
                 s.username as seller_name
                 FROM " . $this->table . " p
                 JOIN components c ON p.component_id = c.id
                 JOIN users s ON c.user_id = s.id
                 WHERE p.user_id = :user_id
                 ORDER BY p.purchase_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getByComponent($componentId) {
        $query = "SELECT p.*, 
                 u.username as buyer_name
                 FROM " . $this->table . " p
                 JOIN users u ON p.user_id = u.id
                 WHERE p.component_id = :component_id
                 ORDER BY p.purchase_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':component_id', $componentId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, component_id, transaction_id, amount, payment_method, payment_status) 
                  VALUES 
                  (:user_id, :component_id, :transaction_id, :amount, :payment_method, :payment_status)";
        
        $stmt = $this->db->prepare($query);
        
        // Clean and bind data
        $userId = $data['user_id'];
        $componentId = $data['component_id'];
        $transactionId = htmlspecialchars(strip_tags($data['transaction_id']));
        $amount = $data['amount'];
        $paymentMethod = htmlspecialchars(strip_tags($data['payment_method']));
        $paymentStatus = isset($data['payment_status']) ? htmlspecialchars(strip_tags($data['payment_status'])) : 'pending';
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':component_id', $componentId);
        $stmt->bindParam(':transaction_id', $transactionId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':payment_method', $paymentMethod);
        $stmt->bindParam(':payment_status', $paymentStatus);
        
        if ($stmt->execute()) {
            // Update component download count
            $query = "UPDATE components SET download_count = download_count + 1 WHERE id = :component_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':component_id', $componentId);
            $stmt->execute();
            
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET payment_status = :status WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    public function recordDownload($purchaseId, $ipAddress, $userAgent) {
        $query = "INSERT INTO downloads (purchase_id, ip_address, user_agent) VALUES (:purchase_id, :ip_address, :user_agent)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':purchase_id', $purchaseId);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_agent', $userAgent);
        
        return $stmt->execute();
    }
}
