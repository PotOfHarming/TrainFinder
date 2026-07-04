<?php
    require_once(__DIR__ . "/../app/utils/Database.php");
    new Database();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>TrainFinder - Map</title>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin="" />

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
        
        <link rel="stylesheet" href="./stylesheets/main.css">
        <link rel="stylesheet" href="./stylesheets/map.css">
    </head>

    <body>
        <div id="map"></div>

        
        <script>
            const map = L.map('map').setView([52.3, 4.9], 8);

            <?php
                include_once(__DIR__ . "/../app/config/Config.php");
                $map_conf = getConfig(__DIR__ . "/../app/config/map.json");
            ?>
            L.tileLayer(<?= json_encode($map_conf["url"]) ?>, {
                subdomains: <?= json_encode($map_conf["subdomains"]) ?>,
                maxZoom: <?= $map_conf["maxZoom"] ?>,
                attribution: <?= json_encode($map_conf["attribution"]) ?>
            }).addTo(map);
        </script>
        <script src="scripts/map.js"></script>
        <script>
            /* Script for drawing stations */
            <?php
                require_once(__DIR__ . "/../app/objects/Station.php");
                foreach(getStations()["stations"] as $station) {
                    echo $station->createMarker(pane: "stations");
                    echo "\n";
                }
            ?>
        </script>
        <script>
            /* Script for drawing railways */
            <?php
                require_once(__DIR__ . "/../app/objects/Railway.php");
                foreach(getRailways()["railways"] as $railway) {
                    echo $railway->createPolyline(pane: "railways");
                    echo "\n";
                }
            ?>
        </script>
    </body>
</html>