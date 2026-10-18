<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$sql = "
    SELECT
        hazard_reports.id,
        hazard_reports.hazard_type,
        hazard_reports.location,
        hazard_reports.latitude,
        hazard_reports.longitude,
        hazard_reports.description,
        hazard_reports.photo,
        hazard_reports.status,
        hazard_reports.created_at,
        users.full_name
    FROM hazard_reports
    INNER JOIN users
        ON hazard_reports.user_id = users.id
    WHERE hazard_reports.latitude IS NOT NULL
    AND hazard_reports.longitude IS NOT NULL
    ORDER BY hazard_reports.created_at DESC
";

$result = $conn->query($sql);

$reports = [];

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>RoadAlert Map</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

</head>

<body>

<header class="navbar">

    <div class="logo">
        Road<span>Alert</span>
    </div>

    <div class="nav-links">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="report.php">
            Report Hazard
        </a>

        <a href="map.php">
            Road Map
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</header>


<div class="container">

    <div class="card">

        <h1>
            RoadAlert Map
        </h1>

        <p>
            View reported road hazards on the map.
        </p>

    </div>


    <div class="card">

        <div
            id="hazardMap"
            style="
                width: 100%;
                height: 600px;
                border-radius: 15px;
            "
        ></div>

    </div>

</div>


<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>


<script>

const reports =
    <?= json_encode($reports) ?>;


const map = L.map("hazardMap").setView(
    [15.487, 120.967],
    12
);


L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        maxZoom: 19,
        attribution:
            '&copy; OpenStreetMap contributors'
    }
).addTo(map);


reports.forEach(function(report) {

    const marker = L.marker([
        parseFloat(report.latitude),
        parseFloat(report.longitude)
    ]).addTo(map);


    let popup = `
        <strong>
            ${report.hazard_type}
        </strong>

        <br><br>

        <strong>Location:</strong>
        ${report.location}

        <br>

        <strong>Status:</strong>
        ${report.status}

        <br>

        <strong>Reported by:</strong>
        ${report.full_name}

        <br><br>

        ${report.description}
    `;


    if (report.photo) {

        popup += `
            <br><br>

            <img
                src="assets/uploads/${report.photo}"
                style="
                    width: 200px;
                    max-width: 100%;
                    border-radius: 8px;
                "
            >
        `;
    }


    marker.bindPopup(popup);

});

</script>

</body>

</html>