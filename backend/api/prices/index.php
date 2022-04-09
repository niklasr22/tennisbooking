<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json; charset=utf-8');
?>
{
    "plans": [
        {
            "id": 0,
            "name": "Gast",
            "price": 6.00
        },
        {
            "id": 1,
            "name": "Tennisclub-Mitglied",
            "price": 0.00
        },
        {
            "id": 2,
            "name": "TSV-Hauptvereinsmitglied",
            "price": 4.00
        }
    ]
}