<?php
    require_once(__DIR__ . "/Railway.php");
    class Disruption {
        public Railway $railway; 
        public array $stations;
        public string $level;
        public string $type;

        public function __construct(Railway $railway, array $stations, string $level, string $type) {
            $this->railway = $railway;
            $this->stations = $stations;
            $this->level = $level;
            $this->type = $type;
        }

        private function getColor(): string {
            switch ($this->type) {
                case "STORING":
                    return "#d43535";
                case "WERKZAAMHEID":
                    return "#d48f35";
                default:
                    return "#ebfa1e";
            }
        }

        /* Create disrupted railway line for the map */
        private int $radius = 5;
        public function createPolyline(string $map = "map", ?string $pane = null): string {
            $popup = "Disrupted railway between {$this->railway->start_name_long} ({$this->railway->start_name}) and {$this->railway->end_name_long} ({$this->railway->end_name})" . 
                ", Affected stations: ";
            require_once(__DIR__ . "/Station.php");
            foreach ($this->stations as $station) $popup = $popup . "<br>- " . searchStation($station)["name"] . " ({$station})";
            return "L.polyline(" . json_encode($this->railway->coordinates) . 
                ", {color: '{$this->getColor()}', radius: {$this->radius}" . 
                ($pane == null ? "" : (", pane: " . json_encode($pane) . "")) . 
                "}).bindPopup(" . json_encode($popup) . ").addTo({$map});";
        }

        public function modifyStations(): array {
            $disruptions = [];
            foreach ($this->stations as $station) {
                array_push($disruptions, "station_{$station}.setStyle({color: " . json_encode($this->getColor()) . "})");
            }
            return $disruptions;
        }
    }

    /* Fetch disrupted railways from the api */
    function fetchDisruptions(): array {
        require_once(__DIR__ . "/../utils/Api.php");
        $api = new Api();
        $url = "https://gateway.apiportal.ns.nl/Spoorkaart-API/api/v1/storingen";
        $res = json_decode($api->getResponse($url), true)["payload"];
        
        $disruptions = [
        ];
        require_once(__DIR__ . "/Railway.php");
        foreach ($res["features"] as $disruption) {
            $api_coords = $disruption["geometry"]["coordinates"][0];
            $properties = $disruption["properties"];
            $coords = [];
            foreach ($api_coords as $c) array_push($coords, [$c[1], $c[0]]);
            require_once(__DIR__ . "/Station.php");
            array_push( $disruptions, new Disruption(
                new Railway(
                    $properties["stations"][0], 
                    searchStation($properties["stations"][0])["name"],
                    $properties["stations"][sizeof($properties["stations"])-1], 
                    searchStation($properties["stations"][sizeof($properties["stations"])-1])["name"],
                    $coords
                ),
                $properties["stations"], $properties["niveau"], $properties["disruptionType"]
            ));
        }
        return $disruptions;
    }

    if (isset($_GET["fetchDisruptions"])) {
        $disruptions = fetchDisruptions();
        $list = [];
        foreach ($disruptions as $disruption) {
            array_push($list, $disruption->createPolyline(pane: "disruptions"));
            foreach($disruption->modifyStations() as $station) {
                array_push($list, $station);
            }
        }
        echo json_encode($list);
    }
?>