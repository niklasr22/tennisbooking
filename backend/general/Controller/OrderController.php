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

            if (!isset($data["items"]) || !isset($data["duration"])) {
                static::unprocessable();
            }

            $requestedItems = $data["items"];
            $duration = intval($data["duration"]);

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
                        "name" => $plan["name"] . " (je " . $duration . " Stunde" . ($duration > 1 ? "n" : "") . ")",
                        "price" => $plan["price"] * $duration,
                        "quantity" => intval($item["quantity"])
                    )
                );
            }

            usort($items, [Plans::class, "comparePlans"]);
            $payedItemsCandidates = array_slice($items, 0, 2);
            $payedItems = array();
            $pi_count = 0;
            $max_payed = 2;
            $i = 0;
            while($pi_count < 2 && $i < count($payedItemsCandidates)) {
                if ($payedItemsCandidates[$i]["quantity"] > $max_payed - $pi_count)
                    $payedItemsCandidates[$i]["quantity"] = $max_payed - $pi_count;
                if ($payedItemsCandidates[$i]["price"] > 0)
                    array_push($payedItems, $payedItemsCandidates[$i]);
                $pi_count += $payedItemsCandidates[$i]["quantity"];
                $i++;
            }

            if (count($payedItems) == 0) {
                static::output(json_encode(array("state" => "noOrderRequired")));
            }

            Database::connect();
            $response = Paypal::createOrder($payedItems);
            if (isset($response["id"])) {
                $internalOrderId = Orders::createOrder(array("items" => $items, "payedItems" => $payedItems), $response["id"]);
                $response["state"] = "paymentInitiated";
                $response["internalOrderId"] = $internalOrderId;
                static::output(json_encode($response));
            } else {
                static::output(json_encode(array(
                    "state" => "error", 
                    "error" => "paypal_error",
                    "paypal" => $response
                )));
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
                    "order" => $order->getAsArray(),
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