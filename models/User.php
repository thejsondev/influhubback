<?php
namespace App\Models;

class User {
    private $db;
    private $table = 'users';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password, full_name, bio, role) 
                  VALUES 
                  (:username, :email, :password, :full_name, :bio, :role)";
        
        $stmt = $this->db->prepare($query);
        
        // Clean and bind data
        $username = htmlspecialchars(strip_tags($data['username']));
        $email = htmlspecialchars(strip_tags($data['email']));
        $password = $data['password']; // Already hashed in controller
        $fullName = isset($data['full_name']) ? htmlspecialchars(strip_tags($data['full_name'])) : null;
        $bio = isset($data['bio']) ? htmlspecialchars(strip_tags($data['bio'])) : null;
        $role = isset($data['role']) ? htmlspecialchars(strip_tags($data['role'])) : 'user';
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':role', $role);
        
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
        if (isset($data['username'])) {
            $sets[] = "username = :username";
            $params[':username'] = htmlspecialchars(strip_tags($data['username']));
        }
        
        if (isset($data['email'])) {
            $sets[] = "email = :email";
            $params[':email'] = htmlspecialchars(strip_tags($data['email']));
        }
        
        if (isset($data['password'])) {
            $sets[] = "password = :password";
            $params[':password'] = $data['password']; // Already hashed in controller
        }
        
        if (isset($data['full_name'])) {
            $sets[] = "full_name = :full_name";
            $params[':full_name'] = htmlspecialchars(strip_tags($data['full_name']));
        }
        
        if (isset($data['bio'])) {
            $sets[] = "bio = :bio";
            $params[':bio'] = htmlspecialchars(strip_tags($data['bio']));
        }
        
        if (isset($data['avatar'])) {
            $sets[] = "avatar = :avatar";
            $params[':avatar'] = htmlspecialchars(strip_tags($data['avatar']));
        }
        
        if (isset($data['role'])) {
            $sets[] = "role = :role";
            $params[':role'] = htmlspecialchars(strip_tags($data['role']));
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
    
    public function authenticate($email, $password) {
        $user = $this->getByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
}
