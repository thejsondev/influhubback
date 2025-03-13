<?php
// Category Controller
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Category;

class CategoryController {
    private $category;
    
    public function __construct($db) {
        $this->category = new Category($db);
    }
    
    public function getAllCategories(Request $request, Response $response): Response {
        // Get all categories
        $categories = $this->category->getAll();
        
        $response->getBody()->write(json_encode([
            'error' => false,
            'categories' => $categories
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function getCategory(Request $request, Response $response, array $args): Response {
        // Get category ID from URL
        $id = $args['id'];
        
        // Get category data
        $category = $this->category->getById($id);
        
        if ($category) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'category' => $category
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Category not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
    
    public function createCategory(Request $request, Response $response): Response {
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Check if user is an admin
        if ($user->role !== 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to create categories'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['name'])) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Missing required fields'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Create slug from name
        $data['slug'] = $this->createSlug($data['name']);
        
        // Create category
        if ($categoryId = $this->category->create($data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Category created successfully',
                'category_id' => $categoryId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to create category'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function updateCategory(Request $request, Response $response, array $args): Response {
        // Get category ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Check if user is an admin
        if ($user->role !== 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to update categories'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Get category data
        $category = $this->category->getById($id);
        
        // Check if category exists
        if (!$category) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Category not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Get request data
        $data = $request->getParsedBody();
        
        // If name is being updated, update slug as well
        if (isset($data['name'])) {
            $data['slug'] = $this->createSlug($data['name']);
        }
        
        // Update category
        if ($this->category->update($id, $data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Category updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to update category'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function deleteCategory(Request $request, Response $response, array $args): Response {
        // Get category ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Check if user is an admin
        if ($user->role !== 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to delete categories'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Get category data
        $category = $this->category->getById($id);
        
        // Check if category exists
        if (!$category) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Category not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Delete category
        if ($this->category->delete($id)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Category deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to delete category'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    private function createSlug($name) {
        // Convert to lowercase
        $slug = strtolower($name);
        
        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading and trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }
}
