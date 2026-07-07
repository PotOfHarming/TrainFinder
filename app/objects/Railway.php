<?php
    class Railway {
        public string $start_name;
        public ?string $start_name_long;
        public string $end_name;
        public ?string $end_name_long;
        public array $coordinates;

        public function __construct(
                string $start_name, ?string $start_name_long, 
                string $end_name, ?string $end_name_long, 
                array $coordinates
        ) {
            $this->start_name = $start_name;
            $this->start_name_long = $start_name_long;
            $this->end_name = $end_name;
            $this->end_name_long = $end_name_long;
            $this->coordinates = $coordinates;
        }

        // Check if the railway already exists in the database
        public function railwayExists(): bool {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();
            $check_stmt = $db->getConnection()->prepare("
                SELECT COUNT(*) FROM railways WHERE start_name = ?
                AND start_name_long = ? AND end_name = ?
                AND end_name_long = ? AND coordinates = ?
            ");

            $check_stmt->execute([
                $this->start_name, $this->start_name_long, 
                $this->end_name, $this->end_name_long,
                json_encode($this->coordinates)
            ]);
            if ($check_stmt->fetchColumn() != 0) return true;
            return false;
        }

        /* Save railway to the database to shorten load times and prevent fetching from the api on every page load */
        public function saveRailway(bool $force = false): void {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();
            if (!$force) {
                if ($this->railwayExists()) return;
            }

            $save_stmt = $db->getConnection()->prepare("
                INSERT INTO railways (
                    start_name, start_name_long, end_name, end_name_long, coordinates
                ) VALUES (?, ?, ?, ?, ?)
            ");

            $save_stmt->execute([
                $this->start_name, $this->start_name_long, 
                $this->end_name, $this->end_name_long,
                json_encode($this->coordinates)
            ]);
        }

        /* Delete railway from the database */
        public function deleteRailway(): void {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();

            $del_stmt = $db->getConnection()->prepare("
                DELETE FROM railways WHERE start_name = ?
                AND start_name_long = ? AND end_name = ?
                AND end_name_long = ? AND coordinates = ?
            ");

            $del_stmt->execute([
                $this->start_name, $this->start_name_long, 
                $this->end_name, $this->end_name_long,
                json_encode($this->coordinates)
            ]);
        }

        /* Create railway line for the map */
        private string $color = "#808080";
        private int $radius = 3;
        public function createPolyline(string $map = "map", ?string $pane = null): string {
            return "L.polyline(" . json_encode($this->coordinates) . 
                    ", {color: '{$this->color}', radius: {$this->radius}" . 
                    ($pane == null ? "" : (", pane: " . json_encode($pane) . "")) . 
                    "}).bindPopup(" .
                    json_encode("Railway between {$this->start_name_long} ({$this->start_name}) and {$this->end_name_long} ({$this->end_name})") . 
                    ").addTo({$map});";
        }
    }

    /* Fetch railways from the api */
    function fetchRailways(): array {
        require_once(__DIR__ . "/../utils/Api.php");
        $api = new Api();
        $url = "https://gateway.apiportal.ns.nl/Spoorkaart-API/api/v1/spoorkaart";
        $res = json_decode($api->getResponse($url), true)["payload"];

        $railways_list = [];
        foreach ($res["features"] as $railway) {
            $ns_rw_coords = $railway["geometry"]["coordinates"];
            $rw_coords = [];
            foreach ($ns_rw_coords as $coord) array_push($rw_coords, [$coord[1], $coord[0]]);
            require_once(__DIR__ . "/Station.php");
            $railway = new Railway(
                $railway["properties"]["from"],
                searchStation($railway["properties"]["from"])["name"],
                $railway["properties"]["to"],
                searchStation($railway["properties"]["to"])["name"],
                $rw_coords
            );
            $railway->saveRailway();
            array_push($railways_list, $railway);
        }
        return $railways_list;
    }

    /* Get railways from database if available else fetch from api */
    function getRailways(): array {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();
        
        $select_stmt = $db->getConnection()->prepare("SELECT * FROM railways");
        $select_stmt->execute();
        $railways = $select_stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($railways == [] || $railways == null) {
            return [
                "source" => "API",
                "railways"=>fetchRailways()
            ];
        } else {
            $rw_list = [];
            foreach ($railways as $rw) {
                array_push(
                    $rw_list, new Railway(
                        $rw["start_name"], $rw["start_name_long"],
                        $rw["end_name"], $rw["end_name_long"],
                        json_decode($rw["coordinates"], true)
                    )
                );
            }

            return [
                "source" => "Database",
                "railways" => $rw_list
            ];
        }
    }
?>