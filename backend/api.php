<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

include_once "./general/config.php";
require_once "./general/Database.php";
require_once "./general/Controller/Controller.php";
require_once "./general/Orders.php";
require_once "./general/Plans.php";

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
        case "orders":
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
    $headers = array_map("strtolower", array_keys($requestHeaders));
    if (strtoupper($_SERVER["REQUEST_METHOD"]) == "OPTIONS" && in_array("origin", $headers) && in_array("access-control-request-method", $headers) && in_array("access-control-request-headers", $headers)) {
        http_response_code(200);
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