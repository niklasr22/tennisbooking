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

        //check if database exists
        Database::checkDatabase();

        Database::$connected = true;
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
        $qry_create_db = "CREATE DATABASE IF NOT EXISTS ".DB_NAME." CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
        Database::query($qry_create_db);

        Database::query("SET NAMES utf8");
        
        //select database
        Database::$dbh->exec("USE ".DB_NAME);
        
        Database::query("CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`ips` ( `ip_id` INT NOT NULL AUTO_INCREMENT , `ip_key` TEXT NOT NULL, `ip` TEXT NOT NULL, `` TEXT NOT NULL, PRIMARY KEY (`ip_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        Database::query("CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`users` ( `user_id` INT NOT NULL AUTO_INCREMENT, `user_username` TEXT NOT NULL, `user_password` TEXT NOT NULL, `user_key` TEXT, `user_is_authorized` BOOLEAN NOT NULL DEFAULT 0, `user_is_admin` BOOLEAN NOT NULL DEFAULT 0, PRIMARY KEY (`user_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        Database::query("CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`users_ips` ( `user_ip_id` INT NOT NULL AUTO_INCREMENT, `user_id` INT NOT NULL, `ip_id` INT NOT NULL, PRIMARY KEY (`user_ip_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        Database::query("CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`log` ( `user_ip_id` INT NOT NULL AUTO_INCREMENT, `user_id` INT NOT NULL, `ip_id` INT NOT NULL, PRIMARY KEY (`user_ip_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");

    }

    public static function preparedStatement($query, $keyToValueArr){ // $keyToValueArr = array(":key" => array(value, type));
        Database::$stmt = Database::$dbh->prepare($query);

        Database::bindValueArray($keyToValueArr);

        Database::$stmt->execute();
    }

}