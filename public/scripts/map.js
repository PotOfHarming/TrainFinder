const map = L.map('map').setView([52.3, 4.9], 8);

L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    subdomains: 'abcd',
    maxZoom: 20,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

/* Create layers on the map to prevent wrong order of layers */
map.createPane('trains');
map.getPane('trains').style.zIndex = 600;
map.createPane('stations');
map.getPane('stations').style.zIndex = 500;
map.createPane('railways');
map.getPane('railways').style.zIndex = 400;