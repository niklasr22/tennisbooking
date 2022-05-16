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


    public function handleAuthorization() {
        if (!$this->checkAuthorization())
            static::forbidden();
    }

    private function selectController(): Controller {
        switch ($this->uri[2]) {
            case "plans":
                require_once __DIR__."/Controller/PlanController.php";
                return new PlanController($this);
            case "orders":
                require_once __DIR__."/Paypal.php";
                require_once __DIR__."/Controller/OrderController.php";
                return new OrderController($this);
            case "code":
                require_once __DIR__."/Controller/CodeController.php";
                return new CodeController($this);
            case "auth":
                require_once __DIR__."/Accounts.php";
                require_once __DIR__."/Controller/AuthController.php";
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

    private function checkAuthorization(): bool {
        $authorization = $this->parseAuthorization();
        if (!$authorization)
            return false;
        require_once __DIR__."/Accounts.php";
        return Accounts::validateTokenLogin($authorization);
    }

    private function parseAuthorization(): bool|string {
        if (isset($this->requestHeaders["Authorization"])) {
            $authorization = explode(" ", $this->requestHeaders["Authorization"]);
            if ($authorization[0] != "Basic")
                return false;
            else
                return $authorization[1];
        } else {
            return false;
        }
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

    public static function forbidden() {
        Api::exitWithHeader("HTTP/1.1 403 Forbidden");
    }

}