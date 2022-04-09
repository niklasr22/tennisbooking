<?php
error_reporting(E_ALL);
require '../../../classes/paypal.php';

echo(Paypal::createOrder(1));