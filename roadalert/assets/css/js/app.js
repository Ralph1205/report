document.addEventListener("DOMContentLoaded", function () {
  const mapElement = document.getElementById("reportMap");

  if (!mapElement) {
    return;
  }

  // Starting location: Cabanatuan City
  const defaultLatitude = 15.4859;
  const defaultLongitude = 120.9665;

  const map = L.map("reportMap").setView(
    [defaultLatitude, defaultLongitude],
    13,
  );

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  let marker = null;

  map.on("click", function (event) {
    const latitude = event.latlng.lat.toFixed(7);

    const longitude = event.latlng.lng.toFixed(7);

    document.getElementById("latitude").value = latitude;

    document.getElementById("longitude").value = longitude;

    if (marker !== null) {
      map.removeLayer(marker);
    }

    marker = L.marker([latitude, longitude]).addTo(map);

    marker.bindPopup("Selected Road Hazard Location").openPopup();
  });
});
