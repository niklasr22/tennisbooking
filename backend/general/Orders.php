<?php
/*enum OrderState {
    case ORDER_STATE_OPEN;
    case ORDER_STATE_PAYED;
    case ORDER_STATE_REDEEMED;
}*/

class Order {
    private string $paymentId;
    private string $orderId;
    private string $code;
    private string $state;
    private array $items;

    public function __construct(string $orderId, string $paymentId, string $code, string $state, string $items) {
        $this->orderId = $orderId;
        $this->paymentId = $paymentId;
        $this->code = $code;
        $this->state = $state;
        $this->items = json_decode($items, true);
    }

    public function getPaymentId(): string {
        return $this->paymentId;
    }

    public function getOrderId(): string {
        return $this->orderId;
    }

    public function getCode(): string {
        return $this->code;
    }

    public function getState(): string {
        return $this->state;
    }

    public function getItems(): array {
        return $this->items;
    }

    public function getAsArray(bool $withCode=true): array {
        $vars = get_object_vars($this);
        if (!$withCode)
            unset($vars["code"]);
        return $vars;
    }

}

class Orders {
    public const ORDER_STATE_OPEN = "open";
    public const ORDER_STATE_PAYED = "payed";
    public const ORDER_STATE_REDEEMED = "redeemed";

    public static function createOrder(array $plans, string $paymentId): string {
        Database::preparedStatement(
            "INSERT INTO orders (order_state, order_plans, order_code, order_paypal_id) VALUES (:orderState, :plans, :code, :paymentId)",
            array(
                //"orderState" => array(Orders::getStateString(OrderState::ORDER_STATE_OPEN), PDO::PARAM_STR),
                "orderState" => array(Orders::ORDER_STATE_OPEN, PDO::PARAM_STR),
                "plans" => array(json_encode($plans), PDO::PARAM_STR),
                "code" => array(Orders::generateCode(), PDO::PARAM_STR),
                "paymentId" => array($paymentId, PDO::PARAM_STR)
            )
        );
        return Database::lastInsertId();
    }

    //public static function updateOrderStateByPaymentId(string $paymentId, OrderState $orderState): void {
    public static function updateOrderStateByPaymentId(string $paymentId, string $orderState): void {
        Database::preparedStatement(
            "UPDATE orders SET order_state = :orderState WHERE order_paypal_id = :paymentId",
            array(
                //"orderState" => array(Orders::getStateString($orderState), PDO::PARAM_STR),
                "orderState" => array($orderState, PDO::PARAM_STR),
                "paymentId" => array($paymentId, PDO::PARAM_STR)
            )
        );
    }

    public static function getOrderByPaymentId(string $paymentId): Order|false {
        Database::preparedStatement(
            "SELECT * FROM orders WHERE order_paypal_id = :paymentId",
            array(
                "paymentId" => array($paymentId, PDO::PARAM_STR)
            )
        );
        $result = Database::fetchAll();
        if (count($result) == 1) {
            $order = $result[0];
            return new Order($order["order_id"], $order["order_paypal_id"], $order["order_code"], $order["order_state"], $order["order_plans"]);
        }
        return false;
    }

    private static function generateCode(int $length = 5): string {
        $alphabet = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
        $alphabetSize = count($alphabet) - 1;
        $code = "";
        while (true) {
            for ($i = 0; $i < $length; $i++)
                $code .= $alphabet[rand(0, $alphabetSize)];
            Database::preparedStatement(
                "SELECT count(*) as codeOccurrences FROM orders WHERE order_code = :code",
                array(
                    "code" => array($code, PDO::PARAM_STR),
                )
            );
            $codeOccurrences = intval(Database::fetchColumn());
            if ($codeOccurrences > 0)
                $code = "";
            else
                break;
        }
        return $code;
    }

    /*private static function getStateString(OrderState $orderState): string {
        switch ($orderState) {
            case OrderState::ORDER_STATE_OPEN:
                return "open";
            case OrderState::ORDER_STATE_PAYED:
                return "payed";
            case OrderState::ORDER_STATE_REDEEMED: 
                return "redeemed";
            default:
                return "error";
        }
    }*/
}