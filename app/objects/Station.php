<?php
    $stations = [];

    class Station {
        public string $station_name;
        public ?string $station_type;
        public ?string $code;
        public ?int $cdcode;
        public ?string $uiccode;
        public ?string $uiccdcode;
        public ?bool $has_facilities;
        public ?bool $has_travelassistence;
        public ?string $country;
        public float $lat;
        public float $lon;
        public ?int $tracks;

        public function __construct(
            string $station_name, ?string $station_type, ?string $code = null, ?int $cdcode = null, ?string $uiccode = null,
            ?string $uiccdcode = null, ?bool $has_facilities = null, ?bool $has_travelassistence = null,
            ?string $country = null, float $lat, float $lon, ?int $tracks
        ) {
            $this->station_name = $station_name;
            $this->station_type = $station_type;
            $this->code = $code;
            $this->cdcode = $cdcode;
            $this->uiccode = $uiccode;
            $this->uiccdcode = $uiccdcode;
            $this->has_facilities = $has_facilities;
            $this->has_travelassistence = $has_travelassistence;
            $this->country = $country;
            $this->lat = $lat;
            $this->lon = $lon;
            $this->tracks = $tracks;
        }

        // Check if the station already exists in the database
        public function stationExists(): bool {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();
            $check_stmt = $db->getConnection()->prepare("
                SELECT COUNT(*) FROM stations WHERE station_name = ?
                AND station_type = ? AND code = ? AND cdcode = ? 
                AND uiccode = ? AND uiccdcode = ? AND has_facilities = ?
                AND has_travelassistence = ? AND country = ?
                AND lat = ? AND lon = ? AND tracks = ?
            ");

            $check_stmt->execute([
                $this->station_name, $this->station_type, $this->code, 
                $this->cdcode, $this->uiccdcode, $this->has_facilities,
                $this->has_travelassistence, $this->country,
                $this->lat, $this->lon, $this->tracks
            ]);
            if ($check_stmt->fetchColumn() != 0) return true;
            return false;
        }

        /* Save station to database to prevent calling the api every load */
        public function saveStation(bool $force = false): void {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();
            if (!$force) {
                if ($this->stationExists()) return;
            }

            $save_stmt = $db->getConnection()->prepare("
                INSERT INTO railways (
                    station_name, station_type, code, cdcode, uiccode, uiccdcode, has_facilities, has_travelassitence, country, lat, lon, tracks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $save_stmt->execute([
                $this->station_name, $this->code, $this->cdcode,
                $this->uiccdcode, $this->has_facilities,
                $this->has_travelassistence, $this->country,
                $this->lat, $this->lon, $this->tracks
            ]);

        }

        /* Delete station from the database */
        public function deleteStation(): void {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();

            $del_stmt = $db->getConnection()->prepare("
                DELETE FROM stations WHERE station_name = ?
                AND station_type = ? AND code = ? AND cdcode = ? 
                AND uiccode = ? AND uiccdcode = ? AND has_facilities = ?
                AND has_travelassistence = ? AND country = ?
                AND lat = ? AND lon = ? AND tracks = ?
            ");

            $del_stmt->execute([
                $this->station_name, $this->station_type, $this->code, 
                $this->cdcode, $this->uiccdcode, $this->has_facilities,
                $this->has_travelassistence, $this->country, $this->lat, 
                $this->lon, $this->tracks
            ]);
        }

        /* create style of the marker on the map */
        private function getMarkerStyle(): array {
            $scale = 25;
            $style = [
                "color" => "#ffffff",
                "weight" => 1
            ];
            switch ($this->station_type) {
                default:
                    break;
                case "STOPTREIN_STATION":
                    $style["color"] = "#A5D6A7";
                    $style["weight"] = 2;
                    break;
                case "KNOOPPUNT_STOPTREIN_STATION":
                    $style["color"] = "#66BB6A";
                    $style["weight"] = 4;
                    break;
                case "SNELTREIN_STATION":
                    $style["color"] = "#42A5F5";
                    $style["weight"] = 5;
                    break;
                case "KNOOPPUNT_SNELTREIN_STATION":
                    $style["color"] = "#1E88E5";
                    $style["weight"] = 7;
                    break;
                case "INTERCITY_STATION":
                    $style["color"] = "#26C6DA";
                    $style["weight"] = 8;
                    break;
                case "KNOOPPUNT_INTERCITY_STATION":
                    $style["color"] = "#00897B";
                    $style["weight"] = 10;
                    break;
                case "MEGA_STATION":
                    $style["color"] = "#0D47A1";
                    $style["weight"] = 11;
                    break;
                case "FACULTATIEF_STATION":
                    $style["color"] = "#B0BEC5";
                    $style["weight"] = 1;
                    break;
            }
            $style["weight"] *= $scale;
            return $style;
        }

        /* Create marker code to put the marker in javascript for loading it onto the map */
        public function createMarker(string $map = "map", ?string $pane = null): string {
            $style = $this->getMarkerStyle();
            return "const station_{$this->code} = L.circle([{$this->lat}, {$this->lon}]"  . 
                    ", {color: '{$style["color"]}', radius: {$style["weight"]}, fillOpacity: 1" . 
                    ($pane == null ? "" : (", pane: " . json_encode($pane) . "")) . 
                    "}).bindPopup(" .
                    json_encode("Station {$this->station_name} (Code: {$this->code})") . 
                    ").addTo({$map});\n";
        }
    }

    /* Fetch stations from the api */
    function fetchStations(): array {
        global $stations;
        require_once(__DIR__ . "/../utils/Api.php");
        $api = new Api();
        $url = "https://gateway.apiportal.ns.nl/nsapp-stations/v2?includeNonPlannableStations=false";
        $res = json_decode($api->getResponse($url), true)["payload"];

        $stations_list = [];
        foreach ($res as $station) {
            array_push($stations_list, new Station(
                station_name: $station["namen"]["lang"], station_type: $station["stationType"] ?? null, code: $station["code"], 
                cdcode: $station["cdCode"]  ?? null, uiccode: $station["UICCode"], uiccdcode: $station["UICCdCode"], 
                has_facilities: $station["heeftFaciliteiten"], has_travelassistence: $station["heeftReisassistentie"], 
                country: $station["land"], lat: $station["lat"], lon: $station["lng"], 
                tracks: sizeof($station["sporen"]) > 0 ? sizeof($station["sporen"]) : null
            ));
            $stations[$station["code"]] = [
                "name"=> $station["namen"]["lang"], 
                "type" => $station["stationType"], 
                "facilities" => $station["heeftFaciliteiten"],
                "travelAssistence" => $station["heeftReisassistentie"]
            ];
        }
        return $stations_list;
    }

    /* Get stations from database unless not available then get them from the api */
    function getStations(): array {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();
        
        $select_stmt = $db->getConnection()->prepare("SELECT * FROM stations");
        $select_stmt->execute();
        $stations_list = $select_stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($stations_list == [] || $stations_list == null) {
            return [
                "source" => "API",
                "stations"=>fetchStations()
            ];
        } else {
            $st_list = [];
            foreach ($stations_list as $station) {
                array_push(
                    $st_list, new Station(
                        $station["namen"]["lang"], $station["stationType"], $station["code"], $station["cdCode"],
                        $station["UICCode"], $station["UICCdCode"], $station["heeftFaciliteiten"],
                        $station["heeftReisassistentie"], $station["land"], $station["lat"], $station["lng"],
                        sizeof($station["sporen"]) > 0 ? sizeof($station["sporen"]) : null
                    )
                );
            }

            return [
                "source" => "Database",
                "stations" => $st_list
            ];
        }
    }

    /* function to search for a station using a code (for example TB returns the name, type, has facilities and has travelassistence of tilburg) */
    function searchStation(string $station_code): array {
        $station_code = strtoupper($station_code);
        global $stations;
        if (isset($stations[$station_code])) {
            return $stations[$station_code];
        }
        return [
            "name"=> "Unknown", 
            "type" => "Unknown", 
            "facilities" => "Unknown",
            "travelAssistence" => "Unknown"
        ];
    }
?>