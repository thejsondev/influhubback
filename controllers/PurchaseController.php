<?php
// Purchase Controller
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Purchase;
use App\Models\Component;

class PurchaseController {
    private $purchase;
    private $component;
    
    public function __construct($db) {
        $this->purchase = new Purchase($db);
        $this->component = new Component($db);
    }
    
    public function getAllPurchases(Request $request, Response $response): Response {
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get purchases for the authenticated user
        $purchases = $this->purchase->getByUser($user->user_id);
        
        $response->getBody()->write(json_encode([
            'error' => false,
            'purchases' => $purchases
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function getPurchase(Request $request, Response $response, array $args): Response {
        // Get purchase ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get purchase data
        $purchase = $this->purchase->getById($id);
        
        // Check if purchase exists
        if (!$purchase) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Purchase not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Check if user is the purchaser or an admin
        if ($purchase['user_id'] != $user->user_id && $user->role != 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to view this purchase'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $response->getBody()->write(json_encode([
            'error' => false,
            'purchase' => $purchase
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function createPurchase(Request $request, Response $response): Response {
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['component_id']) || !isset($data['payment_method'])) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Missing required fields'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Get component data
        $component = $this->component->getById($data['component_id']);
        
        // Check if component exists
        if (!$component) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Component not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Add user ID to data
        $data['user_id'] = $user->user_id;
        
        // Add amount from component price
        $data['amount'] = $component['price'];
        
        // Generate transaction ID
        $data['transaction_id'] = uniqid('txn_');
        
        // Set payment status to completed for now (in a real app, this would be handled by a payment gateway)
        $data['payment_status'] = 'completed';
        
        // Create purchase
        if ($purchaseId = $this->purchase->create($data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Purchase completed successfully',
                'purchase_id' => $purchaseId,
                'transaction_id' => $data['transaction_id']
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to complete purchase'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
