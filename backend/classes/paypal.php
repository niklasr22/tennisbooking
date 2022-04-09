<?php

class Paypal {
    private const CLIENT_ID = "AWM3mAoQzWAPdQvnIXsNcLFfdRoGII8RZlP1N_ypQQYDLS3gOa-pVVVPNDDSHRiFRXVofub7w0a3fhCr";
    private const APP_SECRET = "EKp8QhVvI32sYYvuaMzufqy_oA3mgkZw_kgEYtmEtVsidkZ7GZJrQDXAo0uee3zNXcc0shHAluX595ta";
    private const API_BASE = "https://api-m.sandbox.paypal.com";

    public static function generateAccessToken(): string {
        $auth = base64_encode(static::CLIENT_ID . ":" . static::APP_SECRET);

        $ch = curl_init(static::API_BASE . "/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic " . $auth));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response);

        return $data->access_token;
    }

    public static function createOrder($price): string {
        $accessToken = static::generateAccessToken();

        $payload = array(
            "intent" => "CAPTURE", 
            "purchase_units" => array(
                array(
                    "amount" => array(
                        "currency_code" => "EUR",
                        "value" => $price
                    )
                )
            )
        );

        $ch = curl_init(static::API_BASE . "/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $accessToken));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}