<?php
    include_once(__DIR__ . "/../config/Config.php");
    $refresh_rate = getConfig(__DIR__ . "/../config/map.json")["refresh_rate"];
    require_once(__DIR__ . "/../objects/Ride.php");
    $rides = getAllRides(false, true, true);
?>
<script>
    const rides = <?php echo json_encode($rides) ?>

    /* variable to keep track of last seen vehicles */
    const MAX_RELOAD_TIME = 300;
    setInterval(() => {
        fetch("../app/objects/Vehicle.php?getVehicles=true")
        .then(res=>res.json())
        .then(data=>{
            moveVehicles(data);
            checkDeadMarkers(data);
        })
        .catch(err => {
            console.error("An error occured while fetching the vehicles: ", err);
        })
    }, <?= $refresh_rate ?>);

    let map_vehicles = {};
    let vehicle_ids = [];
    const maxLastLocs = 3;
    /* Function to move the vehicles to a new position if they exist in the map_vehicles list */
    function moveVehicles(newVehicles) {
        for (const vehicle of newVehicles) {
            const newPos = [vehicle["lat"], vehicle["lon"]];
            const id = vehicle["trainNumber"];
            if (id==null || id==undefined) continue;
            if (map_vehicles[id]) {
                let returned = false;
                /* Check if the vehicle has been at any of the previous positions to prevent moving backwards */
                for (const loc of map_vehicles[id]["lastLocs"]) {
                    if (loc["lat"] == newPos[0] && loc["lon"] == newPos[1]) {
                        returned = true;
                        break;
                    }
                }
                if (returned) continue;
                if (newPos in map_vehicles[id]["lastLocs"]) {
                    map_vehicles[id]["marker"].setLatLng(newPos);
                    map_vehicles[id]["lastLocs"] = map_vehicles[id]["lastLocs"].slice(-(maxLastLocs));
                    map_vehicles[id]["lastLocs"].push(newPos);
                    map_vehicles[id]["last_seen"]++;
                }
            } else createVehicleMarker(vehicle);
        }
    }

    /* Function to check for markers that haven't updated in too long */
    function checkDeadMarkers(vehicles) {
        curr_vehicles = [];
        for (const vehicle of vehicles) curr_vehicles.push(vehicle["trainNumber"]);
        for (const vehicle of vehicle_ids) if (!(vehicle in curr_vehicles)) {
            map_vehicles[vehicle]["last_seen"]++;
            if (map_vehicles[vehicle]["last_seen"] > MAX_RELOAD_TIME) {
                map.removeLayer(map_vehicles[vehicle]["marker"]);
                delete map_vehicles[vehicle];
            } 
        } else {
            map_vehicles[vehicle]["last_seen"] = 0;
        }
    }

    /* Function to create a marker on the map and put the vehicle in the vehicles list to later update it */
    function createVehicleMarker(vehicle) {
        vehicle_ids.push(vehicle["trainNumber"]);
        const marker = L.marker([vehicle["lat"], vehicle["lon"]], {
            pane: "trains"
        })
        .bindPopup(`
            <b>Train ${vehicle.trainNumber}</b><br>
            Type: ${vehicle.trainType}<br>
            Speed: ${vehicle.speed} km/h<br>
            Lat: ${vehicle.lat}<br>
            Lon: ${vehicle.lon}<br>
            <button onclick='updateVehicleIcon(${vehicle.trainNumber})'>Update vehicle</button><br>
            <a href="./info.php?vehicle=${vehicle.trainNumber}">More info</a>
        `)
        .addTo(map);
        let icon = null;
        if (vehicle["trainNumber"] in rides) {
            let url = rides[vehicle["trainNumber"]]["train_img"];
            icon = L.divIcon({
                className: "trainMarker",
                html: `
                    <div class="trainImg">
                        <img src="${url}">
                    </div>
                `,
                iconSize: [60, 30],
                iconAnchor: [30, 15]
            });
        }
        if (icon != null) marker.setIcon(icon);
        else {
            updateVehicles(vehicle["trainNumber"]);
        }
        map_vehicles[vehicle["trainNumber"]] = {
            marker: marker,
            lastLocs: [[vehicle["lat"], vehicle["lon"]]],
            last_seen: 0
        };
    }

    function updateVehicles(trainNumber) {
        fetch(`../app/utils/Update.php?type=vehicle&id=${trainNumber}`)
        .then(()=>{
            fetch(`../app/objects/ride.php?getRide=${trainNumber}&includeImage=true`)
            .then(res=>res.json())
            .then(data=>{
                let url = data["rideImage"]["url"];
                map_vehicles[trainNumber].marker.setIcon(
                    L.divIcon({
                        className: "trainMarker",
                        html: `
                            <div class="trainImg">
                                <img src="${url}">
                            </div>
                        `,
                        iconSize: [60, 30],
                        iconAnchor: [30, 15]
                    }));
                }
            )  
        })
    }

    function updateVehicleIcon(trainNumber) {
        let upd_window = window.open(`../app/utils/Update.php?type=vehicle&id=${trainNumber}`, "_blank", "width=320,height=180");

        let check = setInterval(() => {
            if (upd_window.closed) {
                clearInterval(check);

                fetch(`../app/objects/ride.php?getRide=${trainNumber}&includeImage=true`)
                .then(res=>res.json())
                .then(data=>{
                    let url = data["rideImage"]["url"];
                    map_vehicles[trainNumber].marker.setIcon(
                        L.divIcon({
                            className: "trainMarker",
                            html: `
                                <div class="trainImg">
                                    <img src="${url}">
                                </div>
                            `,
                            iconSize: [60, 30],
                            iconAnchor: [30, 15]
                        }));
                    }
                )   
            }
        }, 250);
        
    }
</script>
<style>
    .trainMarker {
        width: 60px !important;
        height: 30px !important;
        background: none;
        border: none;
    }

    .trainImg {
        width: 60px;
        height: 30px;
        overflow: hidden;
    }

    .trainImg img {
        display: block;
        height: 30px;
        width: auto;
        background: none;
    }
</style>