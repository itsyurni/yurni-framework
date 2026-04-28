<?php
session_start();

require __DIR__ . '/../autoload.php';


// TODO: 
// Analyse All the code and understand how it works, then write tests for the code to ensure its functionality and reliability. This will involve creating test cases for each component of the application, such as controllers, models, and routes, to verify that they behave as expected under various conditions.

use yurni\Application;
use App\Controllers\HomeController;
use yurni\Http\Request;
use yurni\Http\Response;

$app = new Application();



$app->get('/', [HomeController::class, 'index']);
$app->get('/about', function (Response $response, Request $request) {

    return $response->html('This is the about page.');
});



$app->run();
