/* Create layers on the map to prevent wrong order of layers */
map.createPane('trains');
map.getPane('trains').style.zIndex = 600;
map.createPane('stations');
map.getPane('stations').style.zIndex = 500;
map.createPane('railways');
map.getPane('railways').style.zIndex = 400;