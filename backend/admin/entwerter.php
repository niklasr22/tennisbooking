<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once "../general/config.php";
require_once "../general/Database.php";
require_once "../general/Controller/Controller.php";
require_once "../general/Orders.php";
require_once "../general/Plans.php";


if(isset($_POST["code"])) {
    Database::connect();
    $order = Orders::getOrderByCode($_POST["code"]);
    if ($order !== false) {
        if ($order->getState() == Orders::ORDER_STATE_PAYED) {
            echo "<p style=\"color: green;\">Code gültig!</p>";
            Orders::updateOrderStateByPaymentId($order->getPaymentId(), Orders::ORDER_STATE_REDEEMED);
        } else if ($order->getState() == Orders::ORDER_STATE_REDEEMED) {
            echo "<p style=\"color: red;\">Code schon benutzt!</p>";
        } else if ($order->getState() == Orders::ORDER_STATE_OPEN) {
            echo "<p style=\"color: red;\">Code nicht bezahlt!</p>";
        }
    } else {
        echo "<p style=\"color: red;\">Code nicht gefunden!</p>";
    }
}

?>
<h1>Code prüfen und entwerten</h1>
<form method="post">
    <input type="text" name="code" placeholder="Code" maxlength="5" minlength="5">
    <input type="submit" value="Prüfen">
</form>
