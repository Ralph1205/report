<?php

session_start();

require_once "../config/database.php";


// ==============================
// LOGIN CHECK
// ==============================

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");
    exit;

}


// ==============================
// ADMIN CHECK
// ==============================

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: ../dashboard.php");
    exit;

}


// ==============================
// GET REPORT COUNTS
// ==============================


// Total reports

$total_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM hazard_reports"
);

$total_reports =
    $total_result->fetch_assoc()["total"];


// Pending reports

$pending_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM hazard_reports
     WHERE status = 'Pending'"
);

$pending_reports =
    $pending_result->fetch_assoc()["total"];


// Verified reports

$verified_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM hazard_reports
     WHERE status = 'Verified'"
);

$verified_reports =
    $verified_result->fetch_assoc()["total"];


// Resolved reports

$resolved_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM hazard_reports
     WHERE status = 'Resolved'"
);

$resolved_reports =
    $resolved_result->fetch_assoc()["total"];

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
        Admin Dashboard - RoadAlert
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body>


<!-- ============================== -->
<!-- ADMIN NAVIGATION -->
<!-- ============================== -->

<header class="navbar">

    <div class="logo">
        Road<span>Alert</span> + FuelTrack
    </div>


    <div class="nav-links">

        <a href="dashboard.php">
            Admin Dashboard
        </a>

        <a href="reports.php">
            Reports
        </a>

        <a href="map.php">
            Map
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</header>


<!-- ============================== -->
<!-- MAIN CONTENT -->
<!-- ============================== -->

<div class="container">


    <!-- ============================== -->
    <!-- ADMIN WELCOME -->
    <!-- ============================== -->

    <section class="hero">

        <p>
            ROADALERT ADMINISTRATION
        </p>

        <h1>

            Welcome,

            <span>
                <?= htmlspecialchars($_SESSION["full_name"]) ?>
            </span>

        </h1>

        <p>
            Manage submitted road hazard reports,
            monitor their status, and view reported
            hazards on the map.
        </p>

    </section>


    <!-- ============================== -->
    <!-- ADMIN FEATURES -->
    <!-- ============================== -->

    <div class="grid">


        <!-- REPORT MANAGEMENT -->

        <div class="card">

            <h2>
                🚧 Manage Reports
            </h2>

            <p>
                Review submitted road hazards
                and update their status.
            </p>

            <br>

            <a
                href="reports.php"
                class="btn"
            >
                View Reports
            </a>

        </div>


        <!-- ROAD MAP -->

        <div class="card">

            <h2>
                🗺️ Road Hazard Map
            </h2>

            <p>
                View submitted road hazards
                and their locations on an
                interactive map.
            </p>

            <br>

            <a
                href="map.php"
                class="btn"
            >
                Open Map
            </a>

        </div>


        <!-- REPORT HISTORY -->

        <div class="card">

            <h2>
                📋 Road Reports
            </h2>

            <p>
                Monitor the status of all
                submitted road hazard reports.
            </p>

            <br>

            <a
                href="reports.php"
                class="btn btn-secondary"
            >
                View All Reports
            </a>

        </div>


    </div>


    <!-- ============================== -->
    <!-- REPORT STATISTICS -->
    <!-- ============================== -->

    <div class="grid stats-grid">


        <!-- TOTAL -->

        <div class="stat stat-total">

            <h3>
                Total Reports
            </h3>

            <strong>
                <?= $total_reports ?>
            </strong>

        </div>


        <!-- PENDING -->

        <div class="stat stat-pending">

            <h3>
                Pending
            </h3>

            <strong>
                <?= $pending_reports ?>
            </strong>

        </div>


        <!-- VERIFIED -->

        <div class="stat stat-verified">

            <h3>
                Verified
            </h3>

            <strong>
                <?= $verified_reports ?>
            </strong>

        </div>


        <!-- RESOLVED -->

        <div class="stat stat-resolved">

            <h3>
                Resolved
            </h3>

            <strong>
                <?= $resolved_reports ?>
            </strong>

        </div>


    </div>


    


    
    


</div>


</body>

</html>