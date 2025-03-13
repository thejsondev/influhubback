<?php
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Create Container
$container = new Container();

// Set container to create App with AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Define app routes
require __DIR__ . '/../routes/api.php';

// Run app
$app->run();
