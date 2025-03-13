<?php
// User Controller
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use Firebase\JWT\JWT;

class UserController {
    private $user;
    
    public function __construct($db) {
        $this->user = new User($db);
    }
    
    public function register(Request $request, Response $response): Response {
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Missing required fields'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Check if email already exists
        if ($this->user->getByEmail($data['email'])) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Email already in use'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        if ($userId = $this->user->create($data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'User registered successfully',
                'user_id' => $userId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to register user'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function login(Request $request, Response $response): Response {
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['email']) || !isset($data['password'])) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Missing required fields'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Authenticate user
        $user = $this->user->authenticate($data['email'], $data['password']);
        
        if ($user) {
            // Generate JWT token
            $payload = [
                'iss' => 'component_marketplace',
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24), // 24 hours
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            
            $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
            
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Invalid email or password'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }
    
    public function getUser(Request $request, Response $response, array $args): Response {
        // Get user ID from URL
        $id = $args['id'];
        
        // Get user data
        $user = $this->user->getById($id);
        
        if ($user) {
            // Remove sensitive data
            unset($user['password']);
            
            $response->getBody()->write(json_encode([
                'error' => false,
                'user' => $user
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'User not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
    
    public function updateUser(Request $request, Response $response, array $args): Response {
        // Get user ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $authUser = $request->getAttribute('user');
        
        // Check if user is updating their own profile or is an admin
        if ($authUser->user_id != $id && $authUser->role != 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to update this user'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Get request data
        $data = $request->getParsedBody();
        
        // If password is being updated, hash it
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Update user
        if ($this->user->update($id, $data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'User updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to update user'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
