<?php
    class Material {
        public string $materialPart;
        public string $materialType;
        public int $length;
        public ?bool $hasToilet;
        public ?bool $hasPower;
        public ?bool $allowsBike;
        public ?bool $isAccessible;
        public ?bool $hasWifi;

        public function __construct(
            string $materialPart, string $materialType, int $length, 
            ?bool $hasToilet, ?bool $hasPower, ?bool $allowsBike,
            ?bool $isAccessible, ?bool $hasWifi
        ) {
            $this->materialPart = $materialPart;
            $this->materialType = $materialType;
            $this->length = $length;
            $this->hasToilet = $hasToilet;
            $this->hasPower = $hasPower;
            $this->allowsBike = $allowsBike;
            $this->isAccessible = $isAccessible;
            $this->hasWifi = $hasWifi;
        }

        /* Save material to database to decrease loading time */
        public function saveMaterial(?bool $update = false) {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();

            $materialExists = getMaterial($this->materialPart) != null;
            if ($update && $materialExists) {
                $update_stmt = $db->getConnection()->prepare("
                    UPDATE materials SET 
                        `material_part = ?, `material_type` = ?,
                        `material_length` = ?, `has_toilet` = ?,
                        `has_power` = ?, `allows_bike` = ?,
                        `is_accessible` = ?, `has_wifi` = ? 
                    WHERE `material_part` = ?
                ");

                $update_stmt->execute([
                    $this->materialPart, $this->materialType, $this->length, 
                    $this->hasToilet, $this->hasPower, $this->allowsBike, 
                    $this->isAccessible, $this->hasWifi, $this->materialPart
                ]);
            } else {
                if ($materialExists) return;

                $insert_stmt = $db->getConnection()->prepare("
                    INSERT INTO `materials`(
                        `material_part`, `material_type`, `material_length`, 
                        `has_toilet`, `has_power`, `allows_bike`, 
                        `is_accessible`, `has_wifi`
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");

                $insert_stmt->execute([
                    $this->materialPart, $this->materialType,
                    $this->length, $this->hasToilet,
                    $this->hasPower, $this->allowsBike, 
                    $this->isAccessible, $this->hasWifi
                ]);
            }
        }
    }

    /* Convert json to Material class */
    function jsonToMaterial(array $data): Material {
        return new Material(
            $data["materieelnummer"], $data["type"], sizeof($data["bakken"]),
            in_array("TOILET", $data["faciliteiten"]), in_array("STROOM", $data["faciliteiten"]), 
            in_array("FIETS", $data["faciliteiten"]), in_array("TOEGANKELIJK", $data["faciliteiten"]), 
            in_array("WIFI", $data["faciliteiten"])
        );
    }

    /* Get material from the database */
    function getMaterial(string $materialPart, ?bool $save = false): ?Material {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();
        $stmt = $db->getConnection()->prepare("SELECT * FROM materials WHERE material_part = ?");
        $stmt->execute([$materialPart]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($material) {
            $mat = new Material(
                $material["material_part"], $material["material_type"],
                $material["material_length"], $material["has_toilet"], 
                $material["has_power"], $material["allows_bike"], 
                $material["is_accessible"], $material["has_wifi"]
            );
            if ($save) $mat->saveMaterial();
            return $mat;
        } else {
            return null;
        }
    }

    /* Get all materials from the api and potentially save them to the database */
    function getAllMaterials(?bool $save = false): array {
        $materials = [];

        require_once(__DIR__ . "/Vehicle.php");
        $vehicles_list = getVehicles();
        $trains = "";
        foreach ($vehicles_list as $vehicle) {
            $trains = $trains . $vehicle->trainNumber . ",";
        }
        
        require_once(__DIR__ . "/../utils/Api.php");
        $api = new Api();
        $url = "https://gateway.apiportal.ns.nl/virtual-train-api/v1/trein?ids=" . $trains;
        $res = json_decode($api->getResponse($url), true);
        foreach ($res as $m) {
            foreach ($m["materieeldelen"] as $material) {
                $mat = jsonToMaterial($material);
                array_push($materials, $mat);
                if ($save) $mat->saveMaterial();
            }
        }
        return $materials;
    }

    echo json_encode(getAllMaterials(true));
?>