<?php

session_start();

require_once "config/database.php";


// ==============================
// ACCESS CONTROL
// ==============================

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Only residents can access this page
if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "resident"
) {
    header("Location: admin/dashboard.php");
    exit;
}


$user_id = $_SESSION["user_id"];


// ==============================
// GET USER'S ROAD REPORTS
// ==============================

$stmt = $conn->prepare(
    "SELECT
        id,
        hazard_type,
        location,
        description,
        photo,
        status,
        created_at
     FROM hazard_reports
     WHERE user_id = ?
     ORDER BY created_at DESC"
);


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result = $stmt->get_result();

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
        History - RoadAlert + FuelTrack
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


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


<div class="container">


    <div class="card">


        <h1>
            My Road Hazard Reports
        </h1>


        <p>
            View the road hazards you have reported.
        </p>


    </div>


    <?php if ($result->num_rows === 0): ?>


        <div class="card">


            <h2>
                No Reports Yet
            </h2>


            <p>
                You haven't submitted any road
                hazard reports.
            </p>


            <br>


            <a
                href="report.php"
                class="btn"
            >
                Report a Hazard
            </a>


        </div>


    <?php else: ?>


        <div class="card">


            <table>


                <thead>

                    <tr>

                        <th>
                            Hazard
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Description
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

                    </tr>

                </thead>


                <tbody>


                <?php while (
                    $report =
                    $result->fetch_assoc()
                ): ?>


                    <tr>


                        <td>

                            <?= htmlspecialchars(
                                $report["hazard_type"]
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $report["location"]
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $report["description"]
                            ) ?>

                        </td>


                        <td>


                            <?php if (
                                !empty($report["photo"])
                            ): ?>


                                <a
                                    href="assets/uploads/<?= htmlspecialchars($report["photo"]) ?>"
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
                                "M d, Y h:i A",
                                strtotime(
                                    $report["created_at"]
                                )
                            ) ?>

                        </td>


                    </tr>


                <?php endwhile; ?>


                </tbody>


            </table>


        </div>


    <?php endif; ?>


</div>


</body>

</html>

<?php

$stmt->close();

?>