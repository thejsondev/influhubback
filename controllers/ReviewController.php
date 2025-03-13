<?php
// Review Controller
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Review;
use App\Models\Purchase;

class ReviewController {
    private $review;
    private $purchase;
    
    public function __construct($db) {
        $this->review = new Review($db);
        $this->purchase = new Purchase($db);
    }
    
    public function getComponentReviews(Request $request, Response $response, array $args): Response {
        // Get component ID from URL
        $componentId = $args['id'];
        
        // Get reviews for the component
        $reviews = $this->review->getByComponent($componentId);
        
        $response->getBody()->write(json_encode([
            'error' => false,
            'reviews' => $reviews
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function createReview(Request $request, Response $response): Response {
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['component_id']) || !isset($data['rating']) || !isset($data['comment'])) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Missing required fields'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Validate rating (1-5)
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Rating must be between 1 and 5'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Check if user has purchased the component
        $purchases = $this->purchase->getByUser($user->user_id);
        $hasPurchased = false;
        
        foreach ($purchases as $purchase) {
            if ($purchase['component_id'] == $data['component_id']) {
                $hasPurchased = true;
                break;
            }
        }
        
        if (!$hasPurchased && $user->role != 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'You must purchase this component before leaving a review'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Add user ID to data
        $data['user_id'] = $user->user_id;
        
        // Create review
        if ($reviewId = $this->review->create($data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Review submitted successfully',
                'review_id' => $reviewId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to submit review'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function updateReview(Request $request, Response $response, array $args): Response {
        // Get review ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get review data
        $review = $this->review->getById($id);
        
        // Check if review exists
        if (!$review) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Review not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Check if user is the reviewer or an admin
        if ($review['user_id'] != $user->user_id && $user->role != 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to update this review'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate rating if provided
        if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Rating must be between 1 and 5'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Update review
        if ($this->review->update($id, $data)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Review updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to update review'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function deleteReview(Request $request, Response $response, array $args): Response {
        // Get review ID from URL
        $id = $args['id'];
        
        // Get authenticated user from request attribute
        $user = $request->getAttribute('user');
        
        // Get review data
        $review = $this->review->getById($id);
        
        // Check if review exists
        if (!$review) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Review not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Check if user is the reviewer or an admin
        if ($review['user_id'] != $user->user_id && $user->role != 'admin') {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Unauthorized to delete this review'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Delete review
        if ($this->review->delete($id)) {
            $response->getBody()->write(json_encode([
                'error' => false,
                'message' => 'Review deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Failed to delete review'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
