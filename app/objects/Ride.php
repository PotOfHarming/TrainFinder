<?php
    require_once(__DIR__ . "/RideImage.php");
    class Ride {
        public string $rideNumber;
        public string $trainType;
        public ?string $operator;
        public ?RideImage $rideImage;

        public function __construct(string $rideNumber, string $trainType, ?string $operator = null, ?RideImage $rideImage = null) {
            $this->rideNumber = $rideNumber;
            $this->trainType = $trainType;
            $this->operator = $operator;
            $this->rideImage = $rideImage;
        }

        /* Save ride to database */
        public function saveRide() {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();

            if (!rideExists($this->rideNumber)) {
                $save_stmt = $db->getConnection()->prepare("
                    INSERT INTO `rides`(
                        `ride_number`, `train_type`, `operator`
                    ) VALUES (
                        ?, ?, ?
                    )
                ");

                $save_stmt->execute([$this->rideNumber, $this->trainType, $this->operator]);
            } else {
                $upd_stmt = $db->getConnection()->prepare("
                    UPDATE `rides` SET 
                        `ride_number` = ?,
                        `train_type` = ?,
                        `operator` = ?
                    WHERE 
                        ride_number = ?
                ");

                $upd_stmt->execute([$this->rideNumber, $this->trainType, $this->operator, $this->rideNumber]);
            }
        }
    }

    /* Check if ride exists in the database */
    function rideExists(string $rideNumber): bool {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();

        $stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM rides WHERE ride_number = ?");
        $stmt->execute([$rideNumber]);

        return (bool) $stmt->fetchColumn();
    }

    /* Get all materials from the api and potentially save them to the database */
    function getAllRides(?bool $save = false, ?bool $includeImages = false, ?bool $preferDatabase = false): array {
        $rides = [];

        require_once(__DIR__ . "/Vehicle.php");
        $vehicles_list = getVehicles();
        $trains = $preferDatabase ? [] : "";
        foreach ($vehicles_list as $vehicle) {
            if (!$preferDatabase) $trains = $trains . $vehicle->trainNumber . ",";
            else array_push($trains, $vehicle->trainNumber);
        }
        
        if ($preferDatabase) {
            if ($includeImages) {
                require_once(__DIR__ . "/../utils/Database.php");
                $db = new Database();
                $rides_stmt = $db->getConnection()->prepare("
                    SELECT 
                        r.ride_number, r.train_type, r.operator,
                        i.train_img, i.img_width, i.img_height
                    FROM rides r 
                    LEFT JOIN ride_images i
                        ON r.train_type = i.train_type
                ");
                $rides_stmt->execute([]);
                $rides_list = $rides_stmt->fetchAll(PDO::FETCH_ASSOC);
                $rides = [];
                foreach ($rides_list as $ride) {
                    $rides[$ride["ride_number"]] = $ride;
                }
                return $rides;
            }

            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();
            $rides_stmt = $db->getConnection()->prepare("SELECT * FROM rides");
            $rides_stmt->execute([]);
            $rides_list = $rides_stmt->fetchAll(PDO::FETCH_ASSOC);
            $rides = [];
            foreach ($rides_list as $ride) {
                array_push($rides, new Ride(
                    $ride["ride_number"], $ride["train_type"], $ride["operator"]
                ));
            }
            return ["rides" => $rides];
        } else {
            require_once(__DIR__ . "/../utils/Api.php");
            $api = new Api();
            $url = "https://gateway.apiportal.ns.nl/virtual-train-api/v1/trein?ids=" . $trains;
            $res = json_decode($api->getResponse($url), true);
            foreach ($res as $train) {
                if (!isset($train["ritnummer"]) || !isset($train["type"]) || !isset($train["vervoerder"])) continue;
                $ride = new Ride(
                    $train["ritnummer"], 
                    isset($train["vervoerder"]) ? ($train["type"] . "_" . $train["vervoerder"]) : $train["type"], 
                    isset($train["vervoerder"]) ? $train["vervoerder"] : null
                );
                if ($save) $ride->saveRide();
                array_push($rides, $ride);
            }
            if (!$includeImages) return ["rides" => $rides];
            else {
                require_once(__DIR__ . "/RideImage.php");
                return [
                    "rides" => $rides,
                    "images" => getAllRideImages($save, $preferDatabase)
                ];
            } 
        }
    }

    /* Get ride based on the ride id */
    function getRide(string $id, ?bool $includeImage = false): ?Ride {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();
        if ($includeImage) {
            $stmt = $db->getConnection()->prepare("
                SELECT 
                    r.ride_number, r.train_type, r.operator,
                    i.train_img, i.img_width, i.img_height
                FROM rides r 
                LEFT JOIN ride_images i
                    ON r.train_type = i.train_type
                WHERE r.ride_number = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return new Ride(
                $row["ride_number"], $row["train_type"], $row["operator"],
                rideImage: new RideImage(
                    $row["train_type"], $row["train_img"],
                    $row["img_width"], $row["img_height"]
                )
            );
        } else {
            $stmt = $db->getConnection()->prepare("
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return new Ride($row["ride_number"], $row["train_type"], $train["operator"]);
        }
        return null;
    }

    

    if (isset($_GET["isFetch"]) && $_GET["isFetch"]==true) echo json_encode(getAllRides(
        false,
        isset($_GET["preferDatabase"]) ? $_GET["preferDatabase"]==true : false, 
        isset($_GET["includeImages"]) ? $_GET["includeImages"]==true : false
    ));

    if (isset($_GET["getRide"])) echo json_encode(getRide($_GET["getRide"], isset($_GET["includeImage"]) ? $_GET["includeImage"]==true : false));
?>