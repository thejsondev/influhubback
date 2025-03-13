<?php
// JWT Authentication middleware
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = new Response();
        
        // Get authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        
        // Check if token exists
        if (!$authHeader) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Authorization header not found'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Extract token
        $jwt = trim(str_replace('Bearer ', '', $authHeader));
        
        try {
            // Decode token
            $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
            
            // Add user data to request
            $request = $request->withAttribute('user', $decoded);
            
            // Continue with request
            return $handler->handle($request);
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Invalid or expired token'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
