<?php
class Accounts {
    public static function registerAccount(string $username, string $password) {
        Database::preparedStatement(
            "INSERT INTO accounts (account_username, account_password) VALUES (:username, :password)",
            array(
                "username" => array($username, PDO::PARAM_STR),
                "password" => array(password_hash($password, PASSWORD_DEFAULT))
            )
        );
    }

    public static function validateLogin(string $username, string $password): bool {
        Database::preparedStatement(
            "SELECT account_password FROM accounts WHERE account_username = :username",
            array(
                "username" => array($username, PDO::PARAM_STR)
            )
        );
        $hash = Database::fetchColumn();
        return password_verify($password, $hash);
    }

    public static function validateTokenLogin(string $authorization) {
        $authData = static::parseAuthorization($authorization);

        Database::preparedStatement(
            "SELECT account_token, account_login_timestamp FROM accounts WHERE account_username = :username",
            array(
                "username" => array($authData["username"], PDO::PARAM_STR)
            )
        );
        $storedToken = Database::fetchColumn();
        $storedTimestamp = strtotime(Database::fetchColumn());

        return $authData["token"] === $storedToken && $storedTimestamp > time() - 86400; // 86400 secs = 1 day
    }

    public static function updateLogin(string $username): string {
        $token = static::generateToken();
        Database::preparedStatement(
            "UPDATE accounts SET account_token = :token, account_login_timestamp = NOW() WHERE account_username = :username",
            array(
                "token" => array($token, PDO::PARAM_STR),
                "username" => array($username, PDO::PARAM_STR)
            )
        );
        return $token;
    }

    private static function parseAuthorization($authorization) {
        $decodedAuthorization = base64_decode($authorization);
        $splittedAuthorization = explode(":", $decodedAuthorization);
        return array(
            "username" => $splittedAuthorization[0],
            "token" => $splittedAuthorization[1]
        );
    }

    private static function generateToken() {
        return base64_encode(random_bytes(128));
    }

}