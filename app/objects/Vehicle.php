<?php
    class Vehicle {
        public string $trainNumber;
        public ?string $rideId;
        public float $lat;
        public float $lon;
        public ?float $speed;
        public ?float $heading;
        public string $trainType;
        public ?string $source;

        public function __construct(string $trainNumber, ?string $rideId, float $lat, float $lon, ?float $speed, ?float $heading, string $trainType, ?string $source) {
            $this->trainNumber = $trainNumber;
            $this->rideId = $rideId;
            $this->lat = $lat;
            $this->lon = $lon;
            $this->speed = $speed;
            $this->heading = $heading;
            $this->trainType = $trainType;
            $this->source = $source;
        }
    }

    /* Get all vehicles from the api, must be to the api due to it constantly changing */
    function getVehicles(): array {
        require_once(__DIR__ . "/../utils/Api.php");
        $api = new Api();
        $url = "https://gateway.apiportal.ns.nl/virtual-train-api/vehicle";
        $res = json_decode($api->getResponse($url), true)["payload"];
        
        $vehicles = [];
        foreach($res["treinen"] as $train) {
            array_push($vehicles, new Vehicle(
                $train["treinNummer"], $train["ritId"], $train["lat"], $train["lng"],
                $train["snelheid"], $train["richting"], $train["type"], $train["bron"]
            ));
        }
        return $vehicles;
    }
    if (isset($_GET["getVehicles"]) && $_GET["getVehicles"]==true) echo json_encode(getVehicles());
?>