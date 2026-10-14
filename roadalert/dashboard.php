<?php

session_start();

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Make sure residents stay on the resident dashboard
if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "resident"
) {
    header("Location: admin/dashboard.php");
    exit;
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

    <title>Dashboard - RoadAlert + FuelTrack</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <!-- WELCOME SECTION -->
    <section class="hero">

        <div class="hero-content">

            <p class="hero-label">
                WELCOME TO ROADALERT + FUELTRACK
            </p>

            <h1>
                Hello,
                <span>
                    <?= htmlspecialchars($_SESSION["full_name"]) ?>
                </span>
            </h1>

            <p class="hero-description">
                Monitor road conditions and track your vehicle's
                fuel usage in one system.
            </p>

        </div>

        <div class="hero-circle"></div>

    </section>


    <!-- FEATURE GRID -->
    <div class="grid">


        <!-- ROADALERT -->
        <div class="card">

            <div class="card-icon">
                🚧
            </div>

            <h2>
                RoadAlert
            </h2>

            <p>
                Report potholes, flooding, construction,
                debris, and other road hazards.
            </p>

            <a
                href="report.php"
                class="btn"
            >
                Report a Hazard
            </a>

        </div>


        <!-- ROAD HAZARD MAP -->
        <div class="card">

            <div class="card-icon">
                🗺️
            </div>

            <h2>
                Road Hazard Map
            </h2>

            <p>
                View road hazards reported by other users
                and check road conditions before traveling.
            </p>

            <a
                href="road_map.php"
                class="btn"
            >
                View Road Map
            </a>

        </div>


        <!-- FUELTRACK -->
        <div class="card">

            <div class="card-icon">
                ⛽
            </div>

            <h2>
                FuelTrack
            </h2>

            <p>
                Record fuel usage and monitor your vehicle's
                fuel efficiency and cost.
            </p>

            <div class="button-group">

                <a
                    href="fuel.php"
                    class="btn"
                >
                    Record Fuel
                </a>

                <a
                    href="fuel_history.php"
                    class="btn btn-secondary"
                >
                    Fuel History
                </a>

            </div>

        </div>


        <!-- MY VEHICLES -->
        <div class="card">

            <div class="card-icon">
                🚗
            </div>

            <h2>
                My Vehicles
            </h2>

            <p>
                Register and manage your vehicles
                for FuelTrack.
            </p>

            <a
                href="vehicle.php"
                class="btn"
            >
                Manage Vehicles
            </a>

        </div>


        <!-- HISTORY -->
        <div class="card">

            <div class="card-icon">
                📋
            </div>

            <h2>
                History
            </h2>

            <p>
                View your previous road reports
                and fuel records.
            </p>

            <div class="button-group">

                <a
                    href="history.php"
                    class="btn"
                >
                    Road History
                </a>

                <a
                    href="fuel_history.php"
                    class="btn btn-secondary"
                >
                    Fuel History
                </a>

            </div>

        </div>


        <!-- LOGOUT -->
        <div class="card logout-card">

            <div class="card-icon">
                👋
            </div>

            <h2>
                Account
            </h2>

            <p>
                Finished using RoadAlert + FuelTrack?
                You can safely log out of your account.
            </p>

            <a
                href="logout.php"
                class="btn btn-danger"
            >
                Logout
            </a>

        </div>


    </div>

</div>

</body>

</html>