<?php
class PlanController extends Controller {

    public function handleRequest() {
        $segments = $this->getUriSegments();
        if (count($segments) == 0) {
            $this->listPlans();
        }
        Api::notFound();
    }

    private function listPlans() {
        if ($this->getRequestMethod() == "GET") {
            $plans = '{"plans": [{"id": 0, "name": "Gast", "price": 6.00}, {"id": 1, "name": "Tennisclub-Mitglied", "price": 0.00}, {"id": 2, "name": "TSV-Hauptvereinsmitglied", "price": 4.00}]}';
            static::output($plans);
        } else {
            Api::unprocessable();
        }
    }

}