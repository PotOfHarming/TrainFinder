<?php
    function updateVehicle(string $id): void {
        require_once(__DIR__ . "/../objects/Material.php");
        $train = getMaterialParts($id)[0];
        echo json_encode($train);
        if (!isset($train["materieeldelen"])) {
            echo "Could not find train " . $id;
            echo "<br>" . json_encode($train);
            return;
        }

        if (!isset($train["materieeldelen"][0]["materieelnummer"])) $train["materieeldelen"][0]["materieelnummer"] = $train["ritnummer"];

        echo "<br><br>";
        require_once(__DIR__ . "/../objects/MaterialImage.php");
        foreach ($train["materieeldelen"] as $mat) {
            $material = jsonToMaterial($mat);
            $material->saveMaterial(true);
            echo json_encode($material) . "<br>";

            $material_image = new MaterialImage(
                $mat["type"], $mat["afbeelding"],
                $mat["breedte"], $mat["hoogte"]
            );
            $material_image->saveMaterialImage();
            echo json_encode($material_image) . "<br>";
        }

        echo "<br>";

        require_once(__DIR__ . "/../objects/Ride.php");
        $ride = new Ride(
            $id,
            isset($train["vervoerder"]) ? ($train["type"] . "_" . $train["vervoerder"]) : $train["type"], 
            isset($train["vervoerder"]) ? $train["vervoerder"] : null, 
            null
        );
        echo json_encode($ride) . "<br>";
        $ride->saveRide();

        require_once(__DIR__ . "/../objects/RideImage.php");
        $rideImage = new RideImage(
            isset($train["vervoerder"]) ? ($train["type"] . "_" . $train["vervoerder"]) : $train["type"], 
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