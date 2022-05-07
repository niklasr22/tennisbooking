<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

include_once "./general/config.php";
require_once "./general/Database.php";
require_once "./general/Controller/Controller.php";
require_once "./general/Orders.php";
require_once "./general/Plans.php";
require_once "./general/Api.php";

$api = new Api();
$api->handle();