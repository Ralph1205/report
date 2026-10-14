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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: reports.php");
    exit;
}

$report_id = (int) $_GET["id"];

$message = "";
$message_type = "";

// Update status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $new_status = $_POST["status"] ?? "";
    $notes = trim($_POST["notes"] ?? "");

    $allowed_statuses = [
        "Pending",
        "Verified",
        "Resolved"
    ];

    if (!in_array($new_status, $allowed_statuses)) {

        $message = "Invalid status.";
        $message_type = "error";

    } else {

        // Get the current report status
        $check = $conn->prepare(
            "SELECT status
             FROM hazard_reports
             WHERE id = ?"
        );

        $check->bind_param(
            "i",
            $report_id
        );

        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows === 0) {

            $message = "Report not found.";
            $message_type = "error";

        } else {

            $old_data = $check_result->fetch_assoc();

            $old_status = $old_data["status"];

            // Update the report
            if ($new_status === "Verified") {

                $stmt = $conn->prepare(
                    "UPDATE hazard_reports
                     SET status = ?,
                         verified_at = NOW()
                     WHERE id = ?"
                );

            } elseif ($new_status === "Resolved") {

                $stmt = $conn->prepare(
                    "UPDATE hazard_reports
                     SET status = ?,
                         resolved_at = NOW()
                     WHERE id = ?"
                );

            } else {

                $stmt = $conn->prepare(
                    "UPDATE hazard_reports
                     SET status = ?
                     WHERE id = ?"
                );
            }

            $stmt->bind_param(
                "si",
                $new_status,
                $report_id
            );

            if ($stmt->execute()) {

                // Save the action in road history
                $action =
                    $old_status . " → " . $new_status;

                $history = $conn->prepare(
                    "INSERT INTO road_history
                    (
                        hazard_report_id,
                        action,
                        notes
                    )
                    VALUES (?, ?, ?)"
                );

                $history->bind_param(
                    "iss",
                    $report_id,
                    $action,
                    $notes
                );

                $history->execute();

                $history->close();

                $message =
                    "Report status updated successfully.";

                $message_type = "success";

            } else {

                $message =
                    "Failed to update report.";

                $message_type = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


// Get report information
$stmt = $conn->prepare(
    "SELECT
        hazard_reports.*,
        users.full_name,
        users.username
     FROM hazard_reports
     INNER JOIN users
        ON hazard_reports.user_id = users.id
     WHERE hazard_reports.id = ?"
);

$stmt->bind_param(
    "i",
    $report_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    echo "Report not found.";
    exit;
}

$report = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Report - RoadAlert</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
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

        <a href="reports.php">
            Reports
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</header>


<div class="form-container">

    <div class="card">

        <h1>
            Manage Road Report
        </h1>

        <p>
            Review the report before changing its status.
        </p>

        <br>


        <?php if ($message !== ""): ?>

            <div class="alert <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <h2>
            Report Information
        </h2>


        <p>
            <strong>Resident:</strong>

            <?= htmlspecialchars(
                $report["full_name"]
            ) ?>
        </p>

        <br>


        <p>
            <strong>Username:</strong>

            <?= htmlspecialchars(
                $report["username"]
            ) ?>
        </p>

        <br>


        <p>
            <strong>Hazard Type:</strong>

            <?= htmlspecialchars(
                $report["hazard_type"]
            ) ?>
        </p>

        <br>


        <p>
            <strong>Location:</strong>

            <?= htmlspecialchars(
                $report["location"]
            ) ?>
        </p>

        <br>


        <p>
            <strong>Description:</strong>

            <?= nl2br(
                htmlspecialchars(
                    $report["description"]
                )
            ) ?>
        </p>

        <br>


        <p>
            <strong>Current Status:</strong>

            <span class="badge <?= strtolower(
                $report["status"]
            ) ?>">
                <?= htmlspecialchars(
                    $report["status"]
                ) ?>
            </span>
        </p>

        <br>


        <?php if (!empty($report["photo"])): ?>

            <p>
                <strong>Photo:</strong>
            </p>

            <br>

            <img
                src="../assets/uploads/<?= htmlspecialchars($report["photo"]) ?>"
                alt="Road hazard photo"
                style="
                    width: 100%;
                    max-width: 600px;
                    border-radius: 12px;
                "
            >

            <br>
            <br>

        <?php endif; ?>


        <hr>

        <br>


        <h2>
            Update Status
        </h2>

        <form method="POST">

            <label>
                New Status
            </label>

            <select
                name="status"
                required
            >

                <option
                    value="Pending"
                    <?= $report["status"] === "Pending"
                        ? "selected"
                        : "" ?>
                >
                    Pending
                </option>

                <option
                    value="Verified"
                    <?= $report["status"] === "Verified"
                        ? "selected"
                        : "" ?>
                >
                    Verified
                </option>

                <option
                    value="Resolved"
                    <?= $report["status"] === "Resolved"
                        ? "selected"
                        : "" ?>
                >
                    Resolved
                </option>


                <option
                    value="Underconstruction"
                    <?= $report["status"] === "Underconstruction"
                        ? "selected"
                        : "" ?>
                >
                    Under Construction
                </option>


                <option
                    value="roadclosed"
                    <?= $report["status"] === "roadclosed"
                        ? "selected"
                        : "" ?>
                >
                    Road Closed
                </option>

            </select>


            <label>
                Notes
            </label>

            <textarea
                name="notes"
                rows="5"
                placeholder="Enter notes about this status change..."
            ></textarea>


            <button type="submit">
                Update Status
            </button>

            <a
                href="reports.php"
                class="btn btn-secondary"
            >
                Back to Reports
            </a>

        </form>

    </div>

</div>

</body>

</html>