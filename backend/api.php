<?php
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

include_once "./general/config.php";
require_once "./general/Database.php";
require_once "./general/Controller/Controller.php";

function invalidRequest() {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = explode("/", $uri);

if (count($uri) >= 2) {
    $controller;
    switch ($uri[2]) {
        case "plans":
            require_once "./general/Controller/PlanController.php";
            $controller = new PlanController();
            break;
        case "order":
            require_once "./general/Paypal.php";
            require_once "./general/Controller/OrderController.php";
            $controller = new OrderController();
            break;
        case "code":
            require_once "./general/Controller/CodeController.php";
            $controller = new CodeController();
            break;
        default:
            invalidRequest();
    }

    $requestHeaders = getallheaders();
    if (isset($requestHeaders["Access-Control-Request-Method"]) && isset($requestHeaders["Access-Control-Request-Headers"])) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
        header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers");
        exit();
    }

    $controller->handleRequest();
} else {
    invalidRequest();
}