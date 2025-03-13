<?php
// Component Controller
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Component;

class ComponentController {
    private $component;
    
    public function __construct($db) {
        $this->component = new Component($db);
    }
    
    public function getAllComponents(Request $request, Response $response): Response {
        // Get query parameters
        $params = $request->getQueryParams();
        
        // Set defaults
        $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $filters = [];
        
        // Add filters if provided
        if (isset($params['category'])) {
            $filters['category_id'] = $params['category'];
        }
        
        if (isset($params['language'])) {
            $filters['language_id'] = $params['language'];
        }
        
        if (isset($params['framework'])) {
            $filters['framework_id'] = $params['framework'];
        }
        
        if (isset($params['price_min'])) {
            $filters['price_min'] = $params['price_min'];
        }
        
        if (isset($params['price_max'])) {
            $filters['price_max'] = $params['price_max'];
        }
        
        // Get components
        $components = $this->component->getAll($limit, $offset, $filters);
        
        $response->getBody()->write(json_encode([
            'error' => false,
            'components' => $components
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function getComponent(Request $request, Response $response, array $args): Response {
        // Get component ID from URL
        $id = $args['id'];
        
        // Get component data
        $component = $this->component->getById($id);
        
        if ($component) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'component' => $component
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Component not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
    
    public function createComponent(Request $request, Response $response): Response {
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['title']) || !isset($data['description']) || !isset($data['price']) || !isset($data['category_id'])) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Missing required fields'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Add user ID to data
        $data['user_id'] = $user->user_id;
        
        // Create slug from title
        $data['slug'] = $this->createSlug($data['title']);
        
        // Create component
        if ($componentId = $this->component->create($data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Component created successfully',
                'component_id' => $componentId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to create component'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function updateComponent(Request $request, Response $response, array $args): Response {
        // Get component ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get component data
        $component = $this->component->getById($id);
        
        // Check if component exists
        if (!$component) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Component not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Check if user is the owner or an admin
        if ($component['user_id'] != $user->user_id && $user->role != 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to update this component'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Get request data
        $data = $request->getParsedBody();
        
        // If title is being updated, update slug as well
        if (isset($data['title'])) {
            $data['slug'] = $this->createSlug($data['title']);
        }
        
        // Update component
        if ($this->component->update($id, $data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Component updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to update component'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function deleteComponent(Request $request, Response $response, array $args): Response {
        // Get component ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get component data
        $component = $this->component->getById($id);
        
        // Check if component exists
        if (!$component) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Component not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Check if user is the owner or an admin
        if ($component['user_id'] != $user->user_id && $user->role != 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to delete this component'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Delete component
        if ($this->component->delete($id)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Component deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to delete component'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    private function createSlug($title) {
        // Convert to lowercase
        $slug = strtolower($title);
        
        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading and trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }
}
