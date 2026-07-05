/* Create layers on the map to prevent wrong order of layers */
map.createPane('trains');
map.getPane('trains').style.zIndex = 700;
map.createPane('stations');
map.getPane('stations').style.zIndex = 600;
map.createPane('disruptions');
map.getPane('disruptions').style.zIndex = 500;
map.createPane('railways');
map.getPane('railways').style.zIndex = 400;