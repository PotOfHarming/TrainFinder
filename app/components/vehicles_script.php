<?php
    include_once(__DIR__ . "/../config/Config.php");
    $refresh_rate = getConfig(__DIR__ . "/../config/map.json")["refresh_rate"];
?>
<script>
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
            <a href="./info.php?vehicle=${vehicle.trainNumber}">More info</a>
        `)
        .addTo(map);
        map_vehicles[vehicle["trainNumber"]] = {
            marker: marker,
            lastLocs: [[vehicle["lat"], vehicle["lon"]]],
            last_seen: 0
        };
    }
</script>