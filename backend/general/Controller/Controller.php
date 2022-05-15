<?php
abstract class Controller {
    private Api $api;
    private array $uri;

    public function __construct(Api $api) {
        $this->api = $api;
        $this->uri = array_slice($this->api->getUri(), 3);
    }

    public function __call($name, $arguments) {
        Api::notFound();
    }

    protected function getUriSegments() {
        return $this->uri;
    }

    protected function getRequestMethod() {
        return $this->api->getRequestMethod();
    }

    protected static function output($body, $httpHeaders = array(), $cors = true) {
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