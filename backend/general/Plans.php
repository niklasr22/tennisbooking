<?php
class Plans {
    public static function getPlans() {
        $plans = '[{"id": 0, "name": "Gast", "price": 6.00}, {"id": 1, "name": "Tennisclub-Mitglied", "price": 0.00}, {"id": 2, "name": "TSV-Hauptvereinsmitglied", "price": 4.00}]';
        return json_decode($plans, true);
    }

    public static function comparePlans(mixed $a, mixed $b) {
        return $a["price"] < $b["price"] ? -1 : ($a["price"] == $b["price"] ? 0 : 1);
    }
}