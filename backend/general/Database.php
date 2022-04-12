<?php

class Database {
    private static $dbh;
    private static $stmt;
    public static $connected = false;

    public static function connect(): bool {
        try {
            Database::$dbh = new PDO('mysql:host='.DB_HOST, DB_USER, DB_PASSWORD);
        } catch (PDOException $e) {  
            return false;
        }
        Database::checkDatabase();
        Database::$connected = true;
        return true;
    }

    private static function prepare($query){
        Database::$stmt = Database::$dbh->prepare($query);
    }

    private static function bindParam($keys, $value, $type){
        Database::$stmt->bindParam($keys, $value, $type);
    }

    private static function bindValueArray($params){
        foreach($params as $key => $value) {
            Database::$stmt->bindParam($key, $value[0], $value[1]);
        }
    }

    private static function execute(){
        Database::$stmt->execute();
    }

    private static function executeParam($array){
        Database::$stmt->execute($array);
    }

    public static function error(){
        if(Database::$dbh->errorInfo())
            return Database::$dbh->errorInfo();
    }

    public static function printErrorInfo(){
        if(Database::$dbh->errorInfo())
            echo var_dump(Database::$dbh->errorInfo());
    }

    public static function close(){
        Database::$dbh = null;
    }

    public static function query($query){
        Database::$stmt = Database::$dbh->query($query);
    }

    private static function storeResult(){
        Database::$stmt->store_result();
    }

    private static function getResult(){
        return Database::$stmt;
    }

    public static function fetchAssoc(){
        return Database::$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function fetchAll(){
        return Database::$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function fetchColumn(){
        return Database::$stmt->fetchColumn();
    }

    public static function rowCount(){
        return Database::$stmt->rowCount();
    }

    public static function lastInsertId(){
        return Database::$dbh->lastInsertId();
    }

    private static function checkDatabase(){
        Database::query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
        Database::query("SET NAMES utf8");
        Database::$dbh->exec("USE " . DB_NAME);
        Database::query("CREATE TABLE IF NOT EXISTS `" . DB_NAME . "`.`orders` ( `order_id` INT NOT NULL AUTO_INCREMENT, `order_plans` TEXT NOT NULL, `order_state` TEXT NOT NULL, `order_code` TEXT NOT NULL, `order_paypal_id` TEXT NOT NULL, PRIMARY KEY (`order_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        Database::query("CREATE TABLE IF NOT EXISTS `" . DB_NAME . "`.`plans` ( `plan_id` INT NOT NULL AUTO_INCREMENT, `plan_name` TEXT NOT NULL, `plan_price` FLOAT NOT NULL, PRIMARY KEY (`plan_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
    }

    public static function preparedStatement($query, $keyToValueArr){ // $keyToValueArr = array(":key" => array(value, type));
        Database::$stmt = Database::$dbh->prepare($query);
        Database::bindValueArray($keyToValueArr);
        Database::$stmt->execute();
    }

}