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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $_SESSION["user_id"];

    $hazard_type = trim($_POST["hazard_type"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $latitude = $_POST["latitude"] ?? "";
    $longitude = $_POST["longitude"] ?? "";
    $description = trim($_POST["description"] ?? "");

    // Basic validation
    if (
        $hazard_type === "" ||
        $location === "" ||
        $description === ""
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif ($latitude === "" || $longitude === "") {

        $message = "Please click the map to select the road hazard location.";
        $message_type = "error";

    } else {

        $photo_name = null;

        // Check if a photo was uploaded
        if (
            isset($_FILES["photo"]) &&
            $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES["photo"]["error"] === UPLOAD_ERR_OK) {

                $allowed_types = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];

                $file_type = $_FILES["photo"]["type"];

                if (!in_array($file_type, $allowed_types)) {

                    $message = "Only JPG, PNG, and WEBP images are allowed.";
                    $message_type = "error";

                } else {

                    $file_extension = pathinfo(
                        $_FILES["photo"]["name"],
                        PATHINFO_EXTENSION
                    );

                    $photo_name =
                        uniqid("hazard_", true) .
                        "." .
                        strtolower($file_extension);

                    // Upload directory
                    $upload_directory = __DIR__ . "/assets/uploads/";

                    // Create directory if it does not exist
                    if (!is_dir($upload_directory)) {
                        mkdir($upload_directory, 0777, true);
                    }

                    $upload_path = $upload_directory . $photo_name;

                    if (
                        !move_uploaded_file(
                            $_FILES["photo"]["tmp_name"],
                            $upload_path
                        )
                    ) {

                        $message = "Failed to upload the photo.";
                        $message_type = "error";

                        $photo_name = null;
                    }
                }

            } else {

                $message = "There was a problem uploading the photo.";
                $message_type = "error";
            }
        }

        // Only save if there is no upload error
        if ($message === "") {

            $latitude = (float) $latitude;
            $longitude = (float) $longitude;

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

                $message =
                    "Road hazard report submitted successfully. Status: Pending.";

                $message_type = "success";

                // Clear submitted values after successful submission
                $_POST = [];

            } else {

                $message =
                    "Failed to save the road hazard report.";

                $message_type = "error";
            }

            $stmt->close();
        }
    }
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

    <title>
        Report Hazard - RoadAlert
    </title>

    <!-- Main Website CSS -->
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

        /* Road Hazard Map */

        #reportMap {
            width: 100%;
            height: 400px;
            border-radius: 15px;
            margin-top: 12px;
            margin-bottom: 18px;
            border: 1px solid #dce5e1;
            overflow: hidden;
        }

        .map-help {
            color: #66757d;
            font-size: 14px;
            margin-bottom: 10px;
        }

    </style>

</head>


<body>


<!-- ============================== -->
<!-- NAVIGATION -->
<!-- ============================== -->

<header class="navbar">

    <div class="logo">
        Road<span>Alert</span> + FuelTrack
    </div>

    <div class="nav-links">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="report.php">
            Report Hazard
        </a>

        <a href="fuel.php">
            FuelTrack
        </a>

        <a href="history.php">
            History
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</header>


<!-- ============================== -->
<!-- REPORT FORM -->
<!-- ============================== -->

<div class="form-container">

    <div class="card">

        <h1>
            Report a Road Hazard
        </h1>

        <p>
            Help other drivers by reporting a road problem.
        </p>

        <br>


        <!-- MESSAGE -->

        <?php if ($message !== ""): ?>

            <div class="alert <?= htmlspecialchars($message_type) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- ============================== -->
        <!-- FORM -->
        <!-- ============================== -->

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- HAZARD TYPE -->

            <label>
                Hazard Type
            </label>

            <select
                name="hazard_type"
                required
            >

                <option value="">
                    Select hazard type
                </option>

                <option value="Pothole">
                    Pothole
                </option>

                <option value="Flooding">
                    Flooding
                </option>

                <option value="Road Construction">
                    Road Construction
                </option>

                <option value="Road Closure">
                    Road Closure
                </option>

                <option value="Debris">
                    Road Debris
                </option>

                <option value="Damaged Road">
                    Damaged Road
                </option>

                <option value="Fallen Tree">
                    Fallen Tree
                </option>

                <option value="Other">
                    Other
                </option>

            </select>


            <!-- LOCATION -->

            <label>
                Location
            </label>

            <input
                type="text"
                name="location"
                placeholder="Example: Cabanatuan City, Nueva Ecija"
                required
            >


            <!-- ============================== -->
            <!-- MAP -->
            <!-- ============================== -->

            <label>
                Select Hazard Location
            </label>

            <p class="map-help">
                Click on the map to select the exact location
                of the road hazard.
            </p>

            <div id="reportMap"></div>


            <!-- LATITUDE -->

            <label>
                Latitude
            </label>

            <input
                type="text"
                name="latitude"
                id="latitude"
                placeholder="Click the map"
                readonly
                required
            >


            <!-- LONGITUDE -->

            <label>
                Longitude
            </label>

            <input
                type="text"
                name="longitude"
                id="longitude"
                placeholder="Click the map"
                readonly
                required
            >


            <!-- DESCRIPTION -->

            <label>
                Description
            </label>

            <textarea
                name="description"
                rows="5"
                placeholder="Describe the road hazard..."
                required
            ></textarea>


            <!-- PHOTO -->

            <label>
                Photo
            </label>

            <input
                type="file"
                name="photo"
                accept="image/jpeg,image/png,image/webp"
            >

            <p>
                <small>
                    Accepted formats: JPG, PNG, WEBP
                </small>
            </p>

            <br>


            <!-- BUTTONS -->

            <button
                type="submit"
            >
                Submit Road Hazard
            </button>

            <a
                href="dashboard.php"
                class="btn btn-secondary"
            >
                Cancel
            </a>


        </form>

    </div>

</div>


<!-- ============================== -->
<!-- LEAFLET JAVASCRIPT -->
<!-- ============================== -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const mapElement =
        document.getElementById("reportMap");

    if (!mapElement) {
        return;
    }


    // Default location: Cabanatuan City
    const defaultLatitude = 15.4859;
    const defaultLongitude = 120.9665;


    // Create map
    const map = L.map("reportMap").setView(
        [
            defaultLatitude,
            defaultLongitude
        ],
        13
    );


    // OpenStreetMap
    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,

            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    let marker = null;


    // Click map
    map.on("click", function (event) {

        const latitude =
            event.latlng.lat.toFixed(7);

        const longitude =
            event.latlng.lng.toFixed(7);


        // Put coordinates into inputs
        document.getElementById("latitude").value =
            latitude;

        document.getElementById("longitude").value =
            longitude;


        // Remove previous marker
        if (marker !== null) {

            map.removeLayer(marker);

        }


        // Add new marker
        marker = L.marker(
            [
                event.latlng.lat,
                event.latlng.lng
            ]
        ).addTo(map);


        marker.bindPopup(
            "<strong>Selected Hazard Location</strong><br>" +
            "Latitude: " + latitude + "<br>" +
            "Longitude: " + longitude
        ).openPopup();

    });


    // Fix map display when page finishes loading
    setTimeout(function () {

        map.invalidateSize();

    }, 300);

});

</script>


</body>

</html>