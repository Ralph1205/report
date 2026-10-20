
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

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

// ==============================
// GET ALL HAZARD REPORTS
// ==============================

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
        users.full_name,
        users.username
    FROM hazard_reports
    INNER JOIN users
        ON hazard_reports.user_id = users.id
    ORDER BY hazard_reports.created_at DESC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Road Reports - Admin | RoadAlert</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <!-- ADMIN NAVIGATION -->

    <header class="topbar">

        <a class="brand" href="dashboard.php">
            Road<span>Alert</span>
        </a>

        <nav class="nav-links">
            <a href="dashboard.php">Admin Dashboard</a>
            <a href="reports.php">Reports</a>
            <a href="map.php">Road Map</a>
            <a href="../logout.php">Logout</a>
        </nav>

    </header>

    <!-- MAIN CONTENT -->

    <main class="container">

        <section class="card">
            <h1>Road Hazard Reports</h1>

            <p>
                Review reports submitted by residents
                and manage their current status.
            </p>
        </section>

        <section class="card">

            <?php if (!$result): ?>

                <h2>Unable to Load Reports</h2>

                <p>
                    There was a problem retrieving reports.
                    Please check your database connection.
                </p>

            <?php elseif ($result->num_rows === 0): ?>

                <h2>No Reports</h2>

                <p>
                    There are currently no road hazard reports.
                </p>

            <?php else: ?>

                <div style="overflow-x: auto;">

                    <table>

                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Hazard</th>
                                <th>Location</th>
                                <th>Photo</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($report = $result->fetch_assoc()): ?>

                                <?php
                                $status = $report["status"];

                                $status_class = strtolower(
                                    str_replace(" ", "-", $status)
                                );

                                $photo = $report["photo"] ?? "";

                                $valid_photo = (
                                    $photo !== "" &&
                                    preg_match(
                                        '/\A[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)\z/i',
                                        $photo
                                    )
                                );
                                ?>

                                <tr>

                                    <!-- RESIDENT -->

                                    <td>
                                        <?= htmlspecialchars(
                                            $report["full_name"] ?? "Unknown",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                        <br>

                                        <small>
                                            @<?= htmlspecialchars(
                                                $report["username"] ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </small>
                                    </td>

                                    <!-- HAZARD TYPE -->

                                    <td>
                                        <?= htmlspecialchars(
                                            $report["hazard_type"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <!-- LOCATION -->

                                    <td>
                                        <?= htmlspecialchars(
                                            $report["location"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                        <?php if (
                                            $report["latitude"] !== null &&
                                            $report["longitude"] !== null &&
                                            $report["latitude"] !== "" &&
                                            $report["longitude"] !== ""
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= htmlspecialchars(
                                                    (string) $report["latitude"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>,
                                                <?= htmlspecialchars(
                                                    (string) $report["longitude"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </small>

                                        <?php endif; ?>
                                    </td>

                                    <!-- PHOTO -->

                                    <td>
                                        <?php if ($valid_photo): ?>

                                            <a
                                                href="../assets/uploads/<?= rawurlencode($photo) ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                View Photo
                                            </a>

                                        <?php else: ?>

                                            No photo

                                        <?php endif; ?>
                                    </td>

                                    <!-- STATUS -->

                                    <td>
                                        <span class="badge <?= htmlspecialchars(
                                            $status_class,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>">
                                            <?= htmlspecialchars(
                                                $status,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    </td>

                                    <!-- DATE -->

                                    <td>
                                        <?php
                                        $timestamp = strtotime(
                                            $report["created_at"] ?? ""
                                        );

                                        echo $timestamp
                                            ? date("M d, Y", $timestamp)
                                            : "Unknown";
                                        ?>
                                    </td>

                                    <!-- ACTION -->

                                    <td>
                                        <a
                                            href="update_status.php?id=<?= (int) $report["id"] ?>"
                                            class="btn"
                                        >
                                            Manage
                                        </a>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</body>
</html>
