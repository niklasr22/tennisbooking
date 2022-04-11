<?php
class PlanController extends Controller {

    public function handleRequest() {
        $segments = Controller::getUriSegments();
        if (count($segments) == 0) {
            $this->listPlans();
        }
        static::notFound();
    }

    private function listPlans() {
        if (static::getRequestMethod() == "GET") {
            $plans = '{"plans": [{"id": 0, "name": "Gast", "price": 6.00}, {"id": 1, "name": "Tennisclub-Mitglied", "price": 0.00}, {"id": 2, "name": "TSV-Hauptvereinsmitglied", "price": 4.00}]}';
            static::output($plans);
        } else {
            static::unprocessable();
        }
    }

}