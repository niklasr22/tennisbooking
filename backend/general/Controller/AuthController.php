<?php
class AuthController extends Controller {

    public function handleRequest() {
        $segments = $this->getUriSegments();
        if (count($segments) == 1) {
            switch ($segments[0]) {
                case "login":
                    break;
            }
        }
        Api::notFound();
    }

}