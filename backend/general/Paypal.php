<?php
class Paypal {
    private const API_BASE = PAYPAL_ENDPOINT;

    public static function generateAccessToken(): string|bool {
        $auth = base64_encode(PAYPAL_CLIENT_ID . ":" . PAYPAL_APP_SECRET);

        $ch = curl_init(static::API_BASE . "/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json", 
            "Authorization: Basic " . $auth
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response);

        if (isset($data->access_token))
            return $data->access_token;
        else {
            exit($response);
            return false;
        }
    }

    public static function createOrder($items) {
        $accessToken = static::generateAccessToken();

        $price = 0.0;
        $payloadItems = array();
        foreach ($items as $item) {
            array_push($payloadItems, array(
                "name" => $item["name"],
                "unit_amount" => array(
                    "currency_code" => "EUR",
                    "value" => strval($item["price"])
                ),
                "quantity" => strval($item["quantity"])
            ));
            $price += $item["price"] * $item["quantity"];
        }

        $payload = array(
            "intent" => "CAPTURE", 
            "purchase_units" => array(
                array(
                    "amount" => array(
                        "currency_code" => "EUR",
                        "value" => strval($price),
                        "breakdown" => array(
                            "item_total" => array(
                                "currency_code" => "EUR",
                                "value" => strval($price)
                            )
                        ),
                    ),
                    "items" => $payloadItems
                )
            )
        );

        $ch = curl_init(static::API_BASE . "/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json", 
            "Authorization: Bearer " . $accessToken
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }

    public static function capturePayment(string $paymentId) {
        $accessToken = static::generateAccessToken();

        $ch = curl_init(static::API_BASE . "/v2/checkout/orders/" . $paymentId . "/capture");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $accessToken));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }

}