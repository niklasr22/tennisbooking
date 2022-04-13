<?php
class OrderController extends Controller {

    public function handleRequest() {
        $segments = Controller::getUriSegments();
        if (count($segments) == 1) {
            switch ($segments[0]) {
                case "create":
                    $this->createOrder();
                    break;
            }
        } else if (count($segments) == 2 && preg_match("/^[0-9A-Z]+$/", $segments[0])) {
            switch ($segments[1]) {
                case "capture":
                    $this->approvePayment();
                    break;
            }
        }
        static::notFound();
    }
    
    private function createOrder() {
        if (static::getRequestMethod() == "POST") {
            $data = json_decode(file_get_contents('php://input'), true);

            $requestedItems = $data["items"];

            $plans = array();
            foreach (Plans::getPlans() as $plan) {
                $plans[strval($plan["id"])] = $plan;
            }

            $items = array();
            foreach ($requestedItems as $item) {
                if ($item["quantity"] < 1)
                    continue;
                $plan = $plans[$item["id"]];
                array_push(
                    $items,
                    array(
                        "name" => $plan["name"],
                        "price" => $plan["price"],
                        "quantity" => $item["quantity"]
                    )
                );
            }

            usort($items, [Plans::class, "comparePlans"]);
            $payedItems = array_values(array_filter(array_slice($items, 0, 2), fn($item) => $item["price"] > 0));
            
            Database::connect();
            $response = Paypal::createOrder($payedItems);
            if (isset($response["id"])) {
                $internalOrderId = Orders::createOrder(array("items" => $items, "payedItems" => $payedItems), $response["id"]);
                $response["internalOrderId"] = $internalOrderId;
                static::output(json_encode($response));
            } else {
                static::output(json_encode(array("error" => "paypal_error")));
            }
        } else {
            static::unprocessable();
        }
    }

    private function approvePayment() {
        if (static::getRequestMethod() == "POST") {
            $paymentId = static::getUriSegments()[0];
            Database::connect();
            $paypalResponse = Paypal::capturePayment($paymentId);
            if (isset($paypalResponse["id"]) && isset($paypalResponse["status"]) && $paypalResponse["status"] == "COMPLETED") {
                //Orders::updateOrderStateByPaymentId($paymentId, OrderState::ORDER_STATE_PAYED);
                Orders::updateOrderStateByPaymentId($paymentId, Orders::ORDER_STATE_PAYED);
                $order = Orders::getOrderByPaymentId($paymentId);
                $response = array(
                    "state" => "success",
                    "order" => $order,
                    "paypal" => $paypalResponse
                );
                static::output(json_encode($response));
            } else {
                static::output(json_encode(array(
                    "state" => "error", 
                    "error" => "paypal_error",
                    "paypal" => $paypalResponse
                )));
            }
        } else {
            static::unprocessable();
        }
    }
}