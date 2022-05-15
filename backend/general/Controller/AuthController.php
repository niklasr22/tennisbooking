<?php
class AuthController extends Controller {

    public function handleRequest() {
        $segments = $this->getUriSegments();
        if (count($segments) == 1) {
            switch ($segments[0]) {
                case "login":
                    $this->login();
                    break;
            }
        }
        Api::notFound();
    }

    private function login() {
        if ($this->getRequestMethod() == "POST") {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data["username"]) || !isset($data["password"])) {
                Api::unprocessable();
            }

            Database::connect();
            if (Accounts::validateLogin($data["username"], $data["password"])) {
                $token = Accounts::updateLogin($data["username"]);
                static::output(json_encode(array("state" => "success", "token" => $token)));
            } else {
                static::output(json_encode(array("state" => "failed")));
            }
        } else {
            Api::unprocessable();
        }
    }

}
