<?php
// API Routes for Component Marketplace

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// API group
$app->group('/api', function (RouteCollectorProxy $group) {
    
    // Users endpoints
    $group->group('/users', function (RouteCollectorProxy $group) {
        $group->post('/register', 'App\Controllers\UserController:register');
        $group->post('/login', 'App\Controllers\UserController:login');
        $group->get('/{id}', 'App\Controllers\UserController:getUser');
        $group->put('/{id}', 'App\Controllers\UserController:updateUser');
    });
    
    // Components endpoints
    $group->group('/components', function (RouteCollectorProxy $group) {
        $group->get('', 'App\Controllers\ComponentController:getAllComponents');
        $group->get('/{id}', 'App\Controllers\ComponentController:getComponent');
        $group->post('', 'App\Controllers\ComponentController:createComponent');
        $group->put('/{id}', 'App\Controllers\ComponentController:updateComponent');
        $group->delete('/{id}', 'App\Controllers\ComponentController:deleteComponent');
    });
    
    // Categories endpoints
    $group->group('/categories', function (RouteCollectorProxy $group) {
        $group->get('', 'App\Controllers\CategoryController:getAllCategories');
        $group->get('/{id}', 'App\Controllers\CategoryController:getCategory');
        $group->post('', 'App\Controllers\CategoryController:createCategory');
        $group->put('/{id}', 'App\Controllers\CategoryController:updateCategory');
        $group->delete('/{id}', 'App\Controllers\CategoryController:deleteCategory');
    });
    
    // Purchases endpoints
    $group->group('/purchases', function (RouteCollectorProxy $group) {
        $group->get('', 'App\Controllers\PurchaseController:getAllPurchases');
        $group->get('/{id}', 'App\Controllers\PurchaseController:getPurchase');
        $group->post('', 'App\Controllers\PurchaseController:createPurchase');
    });
    
    // Reviews endpoints
    $group->group('/reviews', function (RouteCollectorProxy $group) {
        $group->get('/component/{id}', 'App\Controllers\ReviewController:getComponentReviews');
        $group->post('', 'App\Controllers\ReviewController:createReview');
        $group->put('/{id}', 'App\Controllers\ReviewController:updateReview');
        $group->delete('/{id}', 'App\Controllers\ReviewController:deleteReview');
    });
});
