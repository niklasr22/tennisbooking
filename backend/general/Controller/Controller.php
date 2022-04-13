<?php
abstract class Controller {

    public function __call($name, $arguments) {
        static::notFound();
    }

    protected static function notFound() {
        static::output("", array("HTTP/1.1 404 Not Found"));
    }

    protected static function unprocessable() {
        static::output("", header("HTTP/1.1 422 Unprocessable Entity"));
    }

    protected static function getUriSegments() {
        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $uri = explode("/", $uri);
        return array_slice($uri, 3);
    }

    protected static function getRequestMethod() {
        return strtoupper($_SERVER["REQUEST_METHOD"]);
    }

    protected static function output($body, $httpHeaders = array(), $cors=true) {
        if ($cors) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Credentials: true");
        }
        if (!empty($body))
            header("Content-Type: application/json; charset=utf-8");
        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }
        echo $body;
        exit;
    }

    abstract public function handleRequest();
}