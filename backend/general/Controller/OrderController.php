<?php
class OrderController extends Controller {

    public function handleRequest() {
        $segments = Controller::getUriSegments();
        if (count($segments) == 1) {
            switch ($segments[0]) {
                case "create":
                    $this->createOrder();
                    break;
                case "capture":
                    break;
            }
        }
        static::notFound();
    }
    
    private function createOrder() {
        if (static::getRequestMethod() == "POST") {
            $data = json_decode(file_get_contents('php://input'));

            $items = array(
                array("name" => "Gast (TSV-Mitglied)", "price" => 4.0, "quantity" => 2)
            );

            static::output(Paypal::createOrder($items));
        } else {
            static::unprocessable();
        }
    }

}