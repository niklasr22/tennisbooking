<?php
class Api {
    private array $requestHeaders;
    private string $requestMethod;
    private array $uri;

    public function __construct() {
        $this->requestHeaders = array_map("strtolower", array_keys(getallheaders()));
        $this->requestMethod = strtoupper($_SERVER["REQUEST_METHOD"]);
        $this->uri = $this->parseUrl();
    }

    public function handle() {
        if ($this->isPreflightRequest()) {
            $this->preflightRequestResponse();
        } else if (count($this->uri) >= 2) {
            $controller = $this->selectController();
            $controller->handleRequest();
        } else {
            Api::notFound();
        }
    }

    public function getUri(): array {
        return $this->uri;
    }

    public function getRequestHeaders() {
        return $this->requestHeaders;
    }

    public function getRequestMethod() {
        return $this->requestMethod;
    }

    private function selectController(): Controller {
        switch ($this->uri[2]) {
            case "plans":
                require_once "./Controller/PlanController.php";
                return new PlanController($this);
            case "orders":
                require_once "./Paypal.php";
                require_once "./Controller/OrderController.php";
                return new OrderController($this);
            case "code":
                require_once "./Controller/CodeController.php";
                return new CodeController($this);
            case "auth":
                require_once "./Controller/AuthController.php";
                return new AuthController($this);
            default:
                Api::notFound();
        }
    }

    private function preflightRequestResponse() {
        http_response_code(200);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
        header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers");
        exit;
    }

    private function isPreflightRequest(): bool {
        return $this->requestMethod === "OPTIONS" 
                && in_array("origin", $this->requestHeaders) 
                && in_array("access-control-request-method", $this->requestHeaders) 
                && in_array("access-control-request-headers", $this->requestHeaders);
    }

    private function parseUrl() {
        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        return explode("/", $uri);
    }

    private static function exitWithHeader($header) {
        header($header);
        exit;
    }

    public static function notFound() {
        Api::exitWithHeader("HTTP/1.1 404 Not Found");
    }

    public static function unprocessable() {
        Api::exitWithHeader("HTTP/1.1 422 Unprocessable Entity");
    }

}