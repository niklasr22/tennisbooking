<?php
class CodeController extends Controller {
    public static const CODE_STATE_VALID = "valid";
    public static const CODE_STATE_INVALID = "invalid";
    public static const CODE_STATE_UNPAYED = "unpayed";
    public static const CODE_STATE_INEXISTENT = "inexistent";

    public function handleRequest() {
        $segments = $this->getUriSegments();
        if (count($segments) == 2 && preg_match("/^[0-9A-Z]+$/", $segments[0])) {
            switch ($segments[1]) {
                case "redeem":
                    //$this->redeemCode($segments[0]);
                    break;
            }
        }
        Api::notFound();
    }

    private function redeemCode(string $code) {
        Database::connect();
        $order = Orders::getOrderByCode($code);
        $state = CODE_STATE_INEXISTENT;
        if ($order !== false) {
            if ($order->getState() == Orders::ORDER_STATE_PAYED) {
                Orders::updateOrderStateByPaymentId($order->getPaymentId(), Orders::ORDER_STATE_REDEEMED);
                $state = CodeController::CODE_STATE_VALID;
            } else if ($order->getState() == Orders::ORDER_STATE_REDEEMED) {
                $state = CodeController::CODE_STATE_INVALID;
            } else if ($order->getState() == Orders::ORDER_STATE_OPEN) {
                $state = CodeController::CODE_STATE_UNPAYED;
            }
        }
        static::output(json_encode(array("code_state" => $state)));
    }

}