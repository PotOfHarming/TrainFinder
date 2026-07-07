<?php
    function updateVehicle(string $id): void {
        require_once(__DIR__ . "/../objects/Material.php");
        $train = getMaterialParts($id)[0];
        if (!isset($train["materieeldelen"])) {
            echo "Could not find train " . $id;
            echo "<br>" . json_encode($train);
            return;
        }

        if (!isset($train["materieeldelen"][0]["materieelnummer"])) $train["materieeldelen"][0]["materieelnummer"] = $train["ritnummer"];

        foreach ($train["materieeldelen"] as $mat) {$material = jsonToMaterial($mat);
            $material->saveMaterial(true);
        }

        require_once(__DIR__ . "/../objects/Ride.php");
        $ride = new Ride(
            $id,
            isset($train["vervoerder"]) ? ($train["type"] . "_" . $train["vervoerder"]) : $train["type"], 
            $train["vervoerder"]
        );
        $ride->saveRide();

        require_once(__DIR__ . "/../objects/RideImage.php");
        $rideImage = new RideImage(
            $train["type"], 
            $train["materieeldelen"][0]["afbeelding"],
            $train["materieeldelen"][0]["breedte"], 
            $train["materieeldelen"][0]["hoogte"]
        );
        $rideImage->saveToDB(true);
    }

    if (isset($_GET["type"])) {
        if ($_GET["type"] == "vehicle" && isset($_GET["id"])) {
            updateVehicle($_GET["id"]);
        }
    }
?>

<script>
    setTimeout(() => {
        window.close();
    }, 1000);
</script>