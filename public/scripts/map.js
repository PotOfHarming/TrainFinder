/* Create layers on the map to prevent wrong order of layers */
map.createPane('trains');
map.getPane('trains').style.zIndex = 415;
map.createPane('stations');
map.getPane('stations').style.zIndex = 410;
map.createPane('disruptions');
map.getPane('disruptions').style.zIndex = 405;
map.createPane('railways');
map.getPane('railways').style.zIndex = 400;