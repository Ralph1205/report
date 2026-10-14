
<?php
session_start();

require_once "../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
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

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoadAlert Admin Map</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        #adminMap {
            width: 100%;
            height: 600px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .map-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .pending-dot { background: #e5a900; }
        .verified-dot { background: #21b879; }
        .resolved-dot { background: #64748b; }
    </style>
</head>

<body>

<header class="navbar">
    <div class="logo">
        Road<span>Alert</span> Admin
    </div>

    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="reports.php">Reports</a>
        <a href="map.php">Map</a>
        <a href="../logout.php">Logout</a>
    </div>
</header>

<div class="container">

    <div class="card">
        <h1>Road Hazard Map</h1>

        <p>
            View the locations of submitted road hazard reports.
            Select a marker to view its details.
        </p>

        <div class="map-legend">
            <div class="legend-item">
                <span class="legend-dot pending-dot"></span>
                Pending
            </div>

            <div class="legend-item">
                <span class="legend-dot verified-dot"></span>
                Verified
            </div>

            <div class="legend-item">
                <span class="legend-dot resolved-dot"></span>
                Resolved
            </div>
        </div>

        <div id="adminMap"></div>

        <p style="margin-top: 15px;">
            Total mapped reports:
            <strong><?= count($reports) ?></strong>
        </p>
    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const reports = <?= $reports_json ?: '[]' ?>;

const defaultLatitude = 15.4859;
const defaultLongitude = 120.9665;

const map = L.map("adminMap").setView(
    [defaultLatitude, defaultLongitude],
    13
);

L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        attribution: '&copy; OpenStreetMap contributors'
    }
).addTo(map);

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, function(char) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[char];
    });
}

const markerCoordinates = [];

reports.forEach(function(report) {
    const latitude = Number(report.latitude);
    const longitude = Number(report.longitude);

    if (
        !Number.isFinite(latitude) ||
        !Number.isFinite(longitude) ||
        latitude < -90 || latitude > 90 ||
        longitude < -180 || longitude > 180
    ) {
        return;
    }

    let markerColor = "#e5a900";

    if (report.status === "Verified") {
        markerColor = "#21b879";
    } else if (report.status === "Resolved") {
        markerColor = "#64748b";
    }

    const marker = L.circleMarker(
        [latitude, longitude],
        {
            radius: 9,
            color: markerColor,
            fillColor: markerColor,
            fillOpacity: 0.85,
            weight: 2
        }
    ).addTo(map);

    const popupContent = `
        <strong>${escapeHtml(report.hazard_type)}</strong>
        <br><br>
        <strong>Location:</strong> ${escapeHtml(report.location)}
        <br>
        <strong>Status:</strong> ${escapeHtml(report.status)}
        <br>
        <strong>Reported by:</strong> ${escapeHtml(report.full_name)}
        <br>
        <strong>Description:</strong> ${escapeHtml(report.description || "No description")}
        <br><br>
        <a href="update_status.php?id=${encodeURIComponent(report.id)}">
            Manage Report
        </a>
    `;

    marker.bindPopup(popupContent);
    markerCoordinates.push([latitude, longitude]);
});

if (markerCoordinates.length > 0) {
    map.fitBounds(markerCoordinates, {
        padding: [30, 30],
        maxZoom: 15
    });
}
</script>

</body>
</html>