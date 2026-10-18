<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Only admins can access this page
if ($_SESSION["role"] !== "admin") {
    header("Location: ../dashboard.php");
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Road Reports - Admin</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
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

        <a href="reports.php">
            Reports
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</header>


<div class="container">

    <div class="card">

        <h1>
            Road Hazard Reports
        </h1>

        <p>
            Review reports submitted by residents.
        </p>

    </div>


    <div class="card">

        <?php if ($result->num_rows === 0): ?>

            <h2>
                No Reports
            </h2>

            <p>
                There are currently no road hazard reports.
            </p>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            Resident
                        </th>

                        <th>
                            Hazard
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Photo
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php while ($report = $result->fetch_assoc()): ?>

                    <tr>

                        <td>

                            <?= htmlspecialchars(
                                $report["full_name"]
                            ) ?>

                            <br>

                            <small>
                                @<?= htmlspecialchars(
                                    $report["username"]
                                ) ?>
                            </small>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $report["hazard_type"]
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $report["location"]
                            ) ?>

                            <?php if (
                                $report["latitude"] !== null &&
                                $report["longitude"] !== null
                            ): ?>

                                <br>

                                <small>
                                    <?= htmlspecialchars(
                                        $report["latitude"]
                                    ) ?>,
                                    <?= htmlspecialchars(
                                        $report["longitude"]
                                    ) ?>
                                </small>

                            <?php endif; ?>

                        </td>


                        <td>

                            <?php if (
                                !empty($report["photo"])
                            ): ?>

                                <a
                                    href="../assets/uploads/<?= htmlspecialchars($report["photo"]) ?>"
                                    target="_blank"
                                >
                                    View Photo
                                </a>

                            <?php else: ?>

                                No photo

                            <?php endif; ?>

                        </td>


                        <td>

                            <?php
                            $status_class =
                                strtolower(
                                    $report["status"]
                                );
                            ?>

                            <span
                                class="badge <?= htmlspecialchars($status_class) ?>"
                            >
                                <?= htmlspecialchars(
                                    $report["status"]
                                ) ?>
                            </span>

                        </td>


                        <td>

                            <?= date(
                                "M d, Y",
                                strtotime(
                                    $report["created_at"]
                                )
                            ) ?>

                        </td>


                        <td>

                            <a
                                href="update_status.php?id=<?= $report["id"] ?>"
                                class="btn"
                            >
                                Manage
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>

</body>

</html>