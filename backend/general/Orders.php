<?php
class Orders {
    public const ORDER_STATE_OPEN = "open";
    public const ORDER_STATE_PAYED = "payed";
    public const ORDER_STATE_REDEEMED = "redeemed";


    public static function createOrder(array $plans, string $paymentId) {
        Database::preparedStatement(
            "INSERT INTO orders (order_state, order_plans, order_code, order_paypal_id) VALUES (:orderState, :plans, :code, :paymentId)",
            array(
                "orderState" => array(Orders::ORDER_STATE_OPEN, PDO::PARAM_STR),
                "plans" => array(json_encode($plans), PDO::PARAM_STR),
                "code" => array(Orders::generateCode(), PDO::PARAM_STR),
                "paymentId" => array($paymentId, PDO::PARAM_STR)
            )
        );
        return Database::lastInsertId();
    }

    private static function generateCode(int $length = 5) {
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
}