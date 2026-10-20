
<?php
session_start();

require_once "config/database.php";

// Login check
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Resident only
if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "resident"
) {
    header("Location: admin/dashboard.php");
    exit;
}

// Get road hazard reports
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

    <title>Road Hazard Map - RoadAlert</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
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
            color: var(--text);
        }

        .map-header p {
            color: var(--muted);
        }

        #userRoadMap {
            width: 100%;
            height: 600px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            z-index: 1;
        }

        .map-legend {
            background: white;
            padding: 18px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .map-legend h3 {
            margin-bottom: 12px;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            margin-right: 25px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            margin-right: 8px;
            flex-shrink: 0;
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

        .legend-construction {
            background: #2563eb;
        }

        .report-popup {
            min-width: 230px;
            max-width: 280px;
            overflow-wrap: anywhere;
        }

        .report-popup h3 {
            margin-bottom: 8px;
        }

        .report-popup p {
            margin-bottom: 6px;
        }

        .report-photo {
            display: block;
            width: 100%;
            max-width: 220px;
            max-height: 180px;
            object-fit: cover;
            margin-top: 10px;
            border-radius: 8px;
        }

        .map-empty {
            background: white;
            padding: 14px 18px;
            margin-top: 16px;
            border-radius: 10px;
            color: var(--muted);
        }

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

            .nav-links {
                flex-wrap: wrap;
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            #userRoadMap {
                height: 380px;
            }

            .map-header h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <a class="brand" href="dashboard.php">
        Road<span>Alert</span>
    </a>

    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="report.php">Report Hazard</a>
        <a href="road_map.php">Road Map</a>
        <a href="history.php">History</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </nav>
</header>

<main class="container map-page">

    <div class="map-header">
        <h1>🗺️ Road Hazard Map</h1>
        <p>
            View reported road hazards and check the current status
            of affected roads in your community.
        </p>
    </div>

    <div id="userRoadMap"></div>

    <div id="mapMessage" class="map-empty" hidden></div>

    <section class="map-legend">
        <h3>Hazard Status</h3>

        <div class="legend-item">
            <span class="legend-dot legend-pending"></span>
            Pending
        </div>

        <div class="legend-item">
            <span class="legend-dot legend-verified"></span>
            Verified
        </div>

        <div class="legend-item">
            <span class="legend-dot legend-resolved"></span>
            Resolved
        </div>

        <div class="legend-item">
            <span class="legend-dot legend-construction"></span>
            Under Construction
        </div>
    </section>

</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const reports = <?= $reports_json ?: "[]" ?>;

const mapElement = document.getElementById("userRoadMap");
const mapMessage = document.getElementById("mapMessage");

if (typeof L === "undefined") {
    mapMessage.hidden = false;
    mapMessage.textContent =
        "The map could not load. Please check your internet connection.";
} else {
    // Default map location: Cabanatuan City
    const defaultLatitude = 15.4859;
    const defaultLongitude = 120.9665;

    const map = L.map("userRoadMap").setView(
        [defaultLatitude, defaultLongitude],
        13
    );

    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors"
        }
    ).addTo(map);

    const markers = [];

    reports.forEach(function(report) {
        const latitude = Number(report.latitude);
        const longitude = Number(report.longitude);

        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude) ||
            latitude < -90 ||
            latitude > 90 ||
            longitude < -180 ||
            longitude > 180
        ) {
            return;
        }

        // Marker colors for each status
        let markerColor = "#f59e0b";

        if (report.status === "Verified") {
            markerColor = "#16a34a";
        } else if (report.status === "Resolved") {
            markerColor = "#6b7280";
        } else if (report.status === "Under Construction") {
            markerColor = "#2563eb";
        }

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

        const marker = L.marker(
            [latitude, longitude],
            { icon: markerIcon }
        ).addTo(map);

        // Build popup safely without inserting report text as HTML
        const popup = document.createElement("div");
        popup.className = "report-popup";

        const heading = document.createElement("h3");
        heading.textContent = "🚧 " + (report.hazard_type || "Road Hazard");
        popup.appendChild(heading);

        function addDetail(label, value) {
            const paragraph = document.createElement("p");
            const strong = document.createElement("strong");

            strong.textContent = label + ": ";
            paragraph.appendChild(strong);

            paragraph.appendChild(
                document.createTextNode(value || "Not provided")
            );

            popup.appendChild(paragraph);
        }

        addDetail("Location", report.location);
        addDetail("Status", report.status);
        addDetail("Description", report.description);
        addDetail("Reported by", report.full_name);
        addDetail("Date", report.created_at);

        // Show uploaded photo when available
        if (
            report.photo &&
            /^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/i.test(report.photo)
        ) {
            const photo = document.createElement("img");
            photo.src = "assets/uploads/" + report.photo;
            photo.alt = "Road hazard photo";
            photo.className = "report-photo";
            photo.loading = "lazy";

            photo.onerror = function() {
                photo.remove();
            };

            popup.appendChild(photo);
        }

        marker.bindPopup(popup);
        markers.push(marker);
    });

    if (markers.length > 0) {
        const group = L.featureGroup(markers);

        map.fitBounds(group.getBounds(), {
            padding: [30, 30],
            maxZoom: 15
        });
    } else {
        mapMessage.hidden = false;
        mapMessage.textContent =
            "No road hazard reports with map coordinates are available yet.";
    }

    setTimeout(function() {
        map.invalidateSize();
    }, 300);
}
</script>

</body>
</html>
