<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
$setting = require __DIR__ . '/settings.php';
$app = AppFactory::create();
$app->setBasePath('/saint');

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->any('/public.php', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hola Calavera2");
    return $response;
});

$app->run();