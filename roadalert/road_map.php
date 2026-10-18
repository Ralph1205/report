<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| RESIDENT ONLY
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "resident"
) {
    header("Location: admin/dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET ROAD HAZARD REPORTS
|--------------------------------------------------------------------------
*/

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

    WHERE
        hazard_reports.latitude IS NOT NULL
        AND hazard_reports.longitude IS NOT NULL

    ORDER BY hazard_reports.created_at DESC
";

$result = $conn->query($sql);

$reports = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $reports[] = $row;

    }

}

/*
|--------------------------------------------------------------------------
| CONVERT REPORTS TO JSON
|--------------------------------------------------------------------------
*/

$reports_json = json_encode(
    $reports,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Road Map - RoadAlert
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <!-- Leaflet CSS -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | MAP PAGE
        |--------------------------------------------------------------------------
        */

        .map-page {
            width: 90%;
            max-width: 1400px;
            margin: 40px auto;
        }

        .map-header {
            margin-bottom: 20px;
        }

        .map-header h1 {
            margin-bottom: 8px;
        }

        .map-header p {
            color: #687780;
        }

        /*
        |--------------------------------------------------------------------------
        | MAP
        |--------------------------------------------------------------------------
        */

        #userRoadMap {

            width: 100%;

            height: 600px;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);

        }

        /*
        |--------------------------------------------------------------------------
        | LEGEND
        |--------------------------------------------------------------------------
        */

        .map-legend {

            background: black;

            padding: 18px;

            border-radius: 12px;

            margin-top: 20px;

            box-shadow:
                0 4px 15px rgba(0, 0, 0, 0.06);

        }

        .map-legend h3 {

            margin-bottom: 12px;

        }

        .legend-item {

            display: inline-flex;

            align-items: center;

            margin-right: 25px;

            margin-bottom: 8px;

        }

        .legend-dot {

            width: 14px;

            height: 14px;

            border-radius: 50%;

            margin-right: 8px;

        }

        .legend-pending {

            background: #f59e0b;

        }

        .legend-verified {

            background: #16a34a;

        }

        .legend-resolved {

            background: #6b7280;

        }

        /*
        |--------------------------------------------------------------------------
        | POPUP
        |--------------------------------------------------------------------------
        */

        .report-popup {

            min-width: 230px;

        }

        .report-popup h3 {

            margin-bottom: 8px;

        }

        .report-popup p {

            margin-bottom: 6px;

        }

        .report-photo {

            width: 100%;

            max-width: 220px;

            margin-top: 10px;

            border-radius: 8px;

        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 800px) {

            .map-page {

                width: 94%;

                margin: 25px auto;

            }

            #userRoadMap {

                height: 450px;

            }

            .legend-item {

                display: flex;

            }

        }

    </style>

</head>

<body>

<!-- =========================================================
     NAVIGATION
========================================================= -->

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

        <a href="road_map.php">
            Road Map
        </a>

        <a href="history.php">
            History
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</header>


<!-- =========================================================
     MAP CONTENT
========================================================= -->

<div class="map-page">

    <div class="map-header">

        <h1>
            🗺️ Road Hazard Map
        </h1>

        <p>
            View reported road hazards and check
            the current status of affected roads.
        </p>

    </div>


    <!-- MAP -->

    <div id="userRoadMap"></div>


    <!-- LEGEND -->

    <div class="map-legend">

        <h3>
            Hazard Status
        </h3>

        <div class="legend-item">

            <span
                class="legend-dot legend-pending">
            </span>

            Pending

        </div>


        <div class="legend-item">

            <span
                class="legend-dot legend-verified">
            </span>

            Verified

        </div>


        <div class="legend-item">

            <span
                class="legend-dot legend-resolved">
            </span>

            Resolved

        </div>

    </div>

</div>


<!-- Leaflet JS -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>


<script>

/*
|--------------------------------------------------------------------------
| ROAD REPORT DATA FROM PHP
|--------------------------------------------------------------------------
*/

const reports = <?= $reports_json ?: "[]" ?>;


/*
|--------------------------------------------------------------------------
| CREATE MAP
|--------------------------------------------------------------------------
*/

// Cabanatuan City

const defaultLatitude = 15.4859;

const defaultLongitude = 120.9665;

const map = L.map("userRoadMap").setView(
    [
        defaultLatitude,
        defaultLongitude
    ],
    13
);


/*
|--------------------------------------------------------------------------
| OPENSTREETMAP
|--------------------------------------------------------------------------
*/

L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        attribution:
            "&copy; OpenStreetMap contributors"
    }
).addTo(map);


/*
|--------------------------------------------------------------------------
| STORE MARKERS
|--------------------------------------------------------------------------
*/

const markers = [];


/*
|--------------------------------------------------------------------------
| CREATE MARKERS
|--------------------------------------------------------------------------
*/

reports.forEach(function(report) {

    const latitude =
        parseFloat(report.latitude);

    const longitude =
        parseFloat(report.longitude);


    if (
        isNaN(latitude) ||
        isNaN(longitude)
    ) {

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | MARKER COLOR
    |--------------------------------------------------------------------------
    */

    let markerColor = "#f59e0b";


    if (report.status === "Verified") {

        markerColor = "#16a34a";

    }

    else if (report.status === "Resolved") {

        markerColor = "#6b7280";

    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOM MARKER
    |--------------------------------------------------------------------------
    */

    const markerIcon = L.divIcon({

        className: "",

        html: `
            <div style="
                width: 28px;
                height: 28px;
                background: ${markerColor};
                border: 3px solid white;
                border-radius: 50%;
                box-shadow: 0 2px 8px rgba(0,0,0,.35);
            "></div>
        `,

        iconSize: [28, 28],

        iconAnchor: [14, 14],

        popupAnchor: [0, -14]

    });


    /*
    |--------------------------------------------------------------------------
    | CREATE MARKER
    |--------------------------------------------------------------------------
    */

    const marker = L.marker(
        [
            latitude,
            longitude
        ],
        {
            icon: markerIcon
        }
    ).addTo(map);


    /*
    |--------------------------------------------------------------------------
    | PHOTO
    |--------------------------------------------------------------------------
    */

    let photoHTML = "";


    if (
        report.photo &&
        report.photo.trim() !== ""
    ) {

        photoHTML = `
            <img
                src="assets/uploads/${encodeURIComponent(report.photo)}"
                class="report-photo"
                alt="Road hazard photo"
            >
        `;

    }


    /*
    |--------------------------------------------------------------------------
    | POPUP
    |--------------------------------------------------------------------------
    */

    const popupHTML = `

        <div class="report-popup">

            <h3>
                🚧 ${escapeHTML(report.hazard_type)}
            </h3>

            <p>
                <strong>Location:</strong><br>
                ${escapeHTML(report.location)}
            </p>

            <p>
                <strong>Status:</strong>
                ${escapeHTML(report.status)}
            </p>

            <p>
                <strong>Description:</strong><br>
                ${escapeHTML(
                    report.description || "No description provided."
                )}
            </p>

            <p>
                <strong>Reported by:</strong>
                ${escapeHTML(report.full_name)}
            </p>

            <p>
                <strong>Date:</strong>
                ${escapeHTML(report.created_at)}
            </p>

            ${photoHTML}

        </div>

    `;


    marker.bindPopup(popupHTML);


    markers.push(marker);

});


/*
|--------------------------------------------------------------------------
| FIT MAP TO REPORTS
|--------------------------------------------------------------------------
*/

if (markers.length > 0) {

    const group =
        L.featureGroup(markers);

    map.fitBounds(
        group.getBounds(),
        {
            padding: [30, 30]
        }
    );

}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHTML(value) {

    const div =
        document.createElement("div");

    div.textContent =
        value ?? "";

    return div.innerHTML;

}

</script>

</body>

</html>