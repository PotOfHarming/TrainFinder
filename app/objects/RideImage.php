<?php
    class RideImage {
        public string $type;
        public string $url;
        public int $width;
        public int $height;

        public function __construct(string $type, string $url, int $width, int $height) {
            $this->type = $type;
            $this->url = $url;
            $this->width = $width;
            $this->height = $height;
        }

        /* Save ride image to the database */
        public function saveToDB(?bool $update = false) {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();

            if (getRideImage($this->type)!=null) {
                if (!$update) return;
                $upd_stmt = $db->getConnection()->prepare("
                        UPDATE `ride_images` SET 
                            `train_type` = ?, `train_img` = ?, 
                            `img_width` = ?, `img_height` = ?
                        WHERE
                            `train_type` = ?
                ");

                $upd_stmt->execute([$this->type, $this->url, $this->width, $this->height, $this->type]);
            } else {
                $ins_stmt = $db->getConnection()->prepare("
                        INSERT INTO `ride_images`(
                            `train_type`, `train_img`, `img_width`, `img_height`
                        ) VALUES (
                            ?, ?, ?, ?
                        )
                ");

                $ins_stmt->execute([$this->type, $this->url, $this->width, $this->height]);
            }
        }
    }

    /* Get ride image from the database */
    function getRideImage($train_type): ?RideImage {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();

        $stmt = $db->getConnection()->prepare("SELECT * FROM ride_images WHERE train_type = ?");
        $stmt->execute([$train_type]);
        $rideImg = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rideImg == null) return null;
        return new RideImage(
            $rideImg["train_type"], $rideImg["train_img"],
            $rideImg["img_width"], $rideImg["img_height"]
        );
    }

    /* Get amount of ride images */
    function getRideImagesCount(): int {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();
        $stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM ride_images");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /* Get all trains from the api, get the images and potentially save them to the database */
    function getAllRideImages(?bool $save = false, ?bool $preferDatabase = false): array {
        if ($preferDatabase && getRideImagesCount()>0) {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();

            $stmt = $db->getConnection()->prepare("SELECT * FROM ride_images");
            $stmt->execute();
            $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rideImages = [];
            foreach ($imgs as $img) {
                array_push($rideImages, new RideImage(
                    $img["train_type"], $img["train_img"],
                    $img["img_width"], $img["img_height"]
                ));
            }
            return $rideImages;
        } else {
            $rides = [];

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
            $types = [];
            foreach ($res as $train) {
                if (!isset($train["type"])) continue;
                if (in_array(isset($train["vervoerder"]) ? ($train["type"] . "_" . $train["vervoerder"]) : $train["type"], $types)) continue;
                $rideImg = new RideImage(
                    isset($train["vervoerder"]) ? ($train["type"] . "_" . $train["vervoerder"]) : $train["type"],
                    $train["materieeldelen"][0]["afbeelding"],
                    $train["materieeldelen"][0]["breedte"],
                    $train["materieeldelen"][0]["hoogte"]
                );
                if ($save) $rideImg->saveToDB();
                array_push($rides, $rideImg);
                array_push($types, isset($train["vervoerder"]) ? ($train["type"] . "_" . $train["vervoerder"]) : $train["type"]);
            }
            return $rides;
        }
    }

    if (isset($_GET["getAllRideImages"]) && $_GET["getAllRideImages"]==true) echo json_encode(getAllRides(false, true));
    if (isset($_GET["updateRideImages"]) && $_GET["updateRideImages"]==true) echo json_encode(getAllRides(true, false));
    if (isset($_GET["getRideImage"])) echo json_encode(getRideImage($_GET["getRideImage"]));
?>