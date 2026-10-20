
<?php
session_start();

require_once "config/database.php";

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$message_type = "";

$hazard_type = "";
$location = "";
$description = "";

$latitude_input = "";
$longitude_input = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = (int) $_SESSION["user_id"];

    $hazard_type = trim($_POST["hazard_type"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $latitude_input = trim($_POST["latitude"] ?? "");
    $longitude_input = trim($_POST["longitude"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if (
        $hazard_type === "" ||
        $location === "" ||
        $description === ""
    ) {
        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (
        !is_numeric($latitude_input) ||
        !is_numeric($longitude_input)
    ) {
        $message = "Please select the hazard location on the map.";
        $message_type = "error";

    } elseif (
        (float) $latitude_input < -90 ||
        (float) $latitude_input > 90 ||
        (float) $longitude_input < -180 ||
        (float) $longitude_input > 180
    ) {
        $message = "The selected map coordinates are invalid.";
        $message_type = "error";

    } else {
        $latitude = (float) $latitude_input;
        $longitude = (float) $longitude_input;
        $photo_name = null;
        $upload_ok = true;

        // Handle optional photo upload
        if (
            isset($_FILES["photo"]) &&
            $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            $file = $_FILES["photo"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $message = "There was a problem uploading the photo.";
                $message_type = "error";
                $upload_ok = false;

            } elseif ($file["size"] > 5 * 1024 * 1024) {
                $message = "The photo must not exceed 5 MB.";
                $message_type = "error";
                $upload_ok = false;

            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($file["tmp_name"]);

                $allowed_types = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowed_types[$mime_type])) {
                    $message = "Only JPG, PNG, and WEBP images are allowed.";
                    $message_type = "error";
                    $upload_ok = false;

                } else {
                    $upload_directory = __DIR__ . "/assets/uploads/";

                    if (
                        !is_dir($upload_directory) &&
                        !mkdir($upload_directory, 0755, true)
                    ) {
                        $message = "Unable to create the photo upload directory.";
                        $message_type = "error";
                        $upload_ok = false;
                    }

                    if ($upload_ok) {
                        $photo_name = bin2hex(random_bytes(16))
                            . "."
                            . $allowed_types[$mime_type];

                        $upload_path = $upload_directory . $photo_name;

                        if (
                            !move_uploaded_file(
                                $file["tmp_name"],
                                $upload_path
                            )
                        ) {
                            $message = "Failed to upload the photo.";
                            $message_type = "error";
                            $upload_ok = false;
                            $photo_name = null;
                        }
                    }
                }
            }
        }

        // Save report if validation and upload succeed
        if ($upload_ok) {
            $stmt = $conn->prepare(
                "INSERT INTO hazard_reports
                (
                    user_id,
                    hazard_type,
                    location,
                    latitude,
                    longitude,
                    description,
                    photo,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "issddss",
                    $user_id,
                    $hazard_type,
                    $location,
                    $latitude,
                    $longitude,
                    $description,
                    $photo_name
                );

                if ($stmt->execute()) {
                    $message = "Road hazard report submitted successfully! Status: Pending.";
                    $message_type = "success";

                    $hazard_type = "";
                    $location = "";
                    $description = "";
                    $latitude_input = "";
                    $longitude_input = "";

                } else {
                    $message = "Failed to save the report. Please try again.";
                    $message_type = "error";

                    if ($photo_name !== null) {
                        $uploaded_file = __DIR__ . "/assets/uploads/" . $photo_name;

                        if (is_file($uploaded_file)) {
                            unlink($uploaded_file);
                        }
                    }
                }

                $stmt->close();

            } else {
                $message = "Unable to prepare the database query.";
                $message_type = "error";

                if ($photo_name !== null) {
                    $uploaded_file = __DIR__ . "/assets/uploads/" . $photo_name;

                    if (is_file($uploaded_file)) {
                        unlink($uploaded_file);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Hazard - RoadAlert</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        .report-page {
            max-width: 1000px;
            margin: 35px auto;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: clamp(26px, 4vw, 34px);
            margin-bottom: 8px;
            color: var(--text);
        }

        .page-heading p {
            color: var(--muted);
            font-size: 15px;
        }

        .topbar {
            position: relative;
        }

        .brand span {
            color: var(--text);
        }

        .report-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .report-form .form-group {
            margin-bottom: 0;
        }

        .report-form label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
        }

        .report-form input[type="text"],
        .report-form select,
        .report-form textarea,
        .report-form input[type="file"] {
            width: 100%;
            min-width: 0;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #fff;
            color: var(--text);
            font-size: 14px;
        }

        .report-form input:focus,
        .report-form select:focus,
        .report-form textarea:focus {
            outline: 2px solid #b4e2e6;
            border-color: var(--primary);
        }

        .report-form input[readonly] {
            background: #f3f7f8;
            color: #52666b;
        }

        .report-form textarea {
            min-height: 125px;
            resize: vertical;
        }

        .field-help {
            color: var(--muted);
            font-size: 12px;
            margin-top: 6px;
        }

        #reportMap {
            width: 100%;
            height: 380px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-top: 8px;
            z-index: 1;
        }

        .map-instructions {
            background: #eaf7f7;
            color: #31545a;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .coordinates-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 600;
        }

        .alert.success {
            background: #e2f5e9;
            color: #17683b;
            border: 1px solid #b9e5c9;
        }

        .alert.error {
            background: #fde8e7;
            color: #a52b24;
            border: 1px solid #f4c7c4;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 5px;
        }

        .submit-btn,
        .cancel-btn {
            display: inline-block;
            padding: 12px 22px;
            border: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .submit-btn {
            background: var(--primary);
            color: #fff;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: #e6f5f6;
            color: var(--primary-dark);
        }

        .cancel-btn:hover {
            background: #d0ecee;
        }

        @media (max-width: 700px) {
            .report-page {
                width: 92%;
                margin: 24px auto;
            }

            .coordinates-grid {
                grid-template-columns: 1fr;
            }

            #reportMap {
                height: 300px;
            }

            .form-card {
                padding: 20px;
            }

            .nav-links {
                gap: 12px;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions a,
            .form-actions button {
                width: 100%;
                text-align: center;
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

<main class="container report-page">

    <div class="page-heading">
        <h1>Report a Road Hazard</h1>
        <p>
            Help keep your community safe by reporting potholes,
            flooding, road construction, and other road hazards.
        </p>
    </div>

    <section class="form-card">

        <?php if ($message !== ""): ?>
            <div
                class="alert <?= htmlspecialchars($message_type) ?>"
                role="status"
            >
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form
            class="report-form"
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="form-group">
                <label for="hazard_type">Hazard Type</label>

                <select name="hazard_type" id="hazard_type" required>
                    <option value="">Select hazard type</option>

                    <?php
                    $hazard_options = [
                        "Pothole",
                        "Flooding",
                        "Road Construction",
                        "Road Closure",
                        "Debris",
                        "Damaged Road",
                        "Fallen Tree",
                        "Other"
                    ];

                    foreach ($hazard_options as $option):
                    ?>
                        <option
                            value="<?= htmlspecialchars($option) ?>"
                            <?= $hazard_type === $option ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="location">Location / Road Name</label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="Example: Cabanatuan City, Nueva Ecija"
                    value="<?= htmlspecialchars($location) ?>"
                    maxlength="255"
                    required
                >

                <p class="field-help">
                    Enter the road name or area where the hazard is located.
                </p>
            </div>

            <div class="form-group">
                <label for="reportMap">Select Hazard Location on the Map</label>

                <div class="map-instructions">
                    <strong>Instructions:</strong>
                    Click the map at the exact location of the road hazard.
                    A marker will show your selected location.
                </div>

                <div id="reportMap"></div>

                <p class="field-help">
                    You must select a point on the map before submitting.
                </p>
            </div>

            <div class="coordinates-grid">

                <div class="form-group">
                    <label for="latitude">Latitude</label>

                    <input
                        type="text"
                        id="latitude"
                        name="latitude"
                        placeholder="Click the map to select"
                        readonly
                        required
                        value="<?= htmlspecialchars($latitude_input) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="longitude">Longitude</label>

                    <input
                        type="text"
                        id="longitude"
                        name="longitude"
                        placeholder="Click the map to select"
                        readonly
                        required
                        value="<?= htmlspecialchars($longitude_input) ?>"
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="description">Hazard Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    placeholder="Describe the road hazard and any danger it may cause..."
                    required
                ><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div class="form-group">
                <label for="photo">Upload a Photo (Optional)</label>

                <input
                    type="file"
                    id="photo"
                    name="photo"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <p class="field-help">
                    Accepted formats: JPG, PNG, WEBP. Maximum size: 5 MB.
                </p>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">
                    Submit Road Hazard
                </button>

                <a href="dashboard.php" class="cancel-btn">
                    Cancel
                </a>
            </div>

        </form>
    </section>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const mapElement = document.getElementById("reportMap");

    if (!mapElement || typeof L === "undefined") {
        if (mapElement) {
            mapElement.textContent =
                "The map could not be loaded. Please check your internet connection.";
        }
        return;
    }

    // Default map location: Cabanatuan City
    const defaultLatitude = 15.4859;
    const defaultLongitude = 120.9665;

    const map = L.map("reportMap").setView(
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

    let marker = null;

    const latitudeInput = document.getElementById("latitude");
    const longitudeInput = document.getElementById("longitude");

    // Restore previously selected coordinates after a validation error
    const savedLatitude = parseFloat(latitudeInput.value);
    const savedLongitude = parseFloat(longitudeInput.value);

    if (
        latitudeInput.value !== "" &&
        longitudeInput.value !== "" &&
        Number.isFinite(savedLatitude) &&
        Number.isFinite(savedLongitude) &&
        Math.abs(savedLatitude) <= 90 &&
        Math.abs(savedLongitude) <= 180
    ) {
        marker = L.marker([savedLatitude, savedLongitude]).addTo(map);
        map.setView([savedLatitude, savedLongitude], 15);
        marker.bindPopup("Selected Hazard Location");
    }

    // Choose the hazard location by clicking the map
    map.on("click", function (event) {
        const latitude = event.latlng.lat.toFixed(7);
        const longitude = event.latlng.lng.toFixed(7);

        latitudeInput.value = latitude;
        longitudeInput.value = longitude;

        if (marker !== null) {
            map.removeLayer(marker);
        }

        marker = L.marker([
            event.latlng.lat,
            event.latlng.lng
        ]).addTo(map);

        marker.bindPopup(
            "<strong>Selected Hazard Location</strong><br>" +
            "Latitude: " + latitude + "<br>" +
            "Longitude: " + longitude
        ).openPopup();
    });

    // Make sure the map renders at the correct size
    setTimeout(function () {
        map.invalidateSize();
    }, 300);
});
</script>

</body>
</html>
