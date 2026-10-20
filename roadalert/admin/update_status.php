
<?php

session_start();

require_once "../config/database.php";

// ==============================
// LOGIN AND ADMIN CHECK
// ==============================

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

// ==============================
// VALIDATE REPORT ID
// ==============================

if (!isset($_GET["id"]) || !ctype_digit((string) $_GET["id"]) || (int) $_GET["id"] < 1) {
    header("Location: reports.php");
    exit;
}

$report_id = (int) $_GET["id"];

$message = "";
$message_type = "";

// These values must match the status names used throughout RoadAlert.
$allowed_statuses = [
    "Pending",
    "Verified",
    "Resolved",
    "Under Construction",
    "Road Closed"
];

// ==============================
// UPDATE STATUS
// ==============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $new_status = trim($_POST["status"] ?? "");
    $notes = trim($_POST["notes"] ?? "");

    if (!in_array($new_status, $allowed_statuses, true)) {

        $message = "Invalid status selected.";
        $message_type = "error";

    } elseif (strlen($notes) > 5000) {

        $message = "Notes must not exceed 5000 characters.";
        $message_type = "error";

    } else {

        // Check that the report exists.
        $check = $conn->prepare(
            "SELECT status
             FROM hazard_reports
             WHERE id = ?"
        );

        $check->bind_param("i", $report_id);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows === 0) {

            $message = "Report not found.";
            $message_type = "error";

        } else {

            $old_data = $check_result->fetch_assoc();
            $old_status = $old_data["status"];

            // Update the report and relevant timestamps.
            if ($new_status === "Verified") {

                $stmt = $conn->prepare(
                    "UPDATE hazard_reports
                     SET status = ?, verified_at = NOW()
                     WHERE id = ?"
                );

            } elseif ($new_status === "Resolved") {

                $stmt = $conn->prepare(
                    "UPDATE hazard_reports
                     SET status = ?, resolved_at = NOW()
                     WHERE id = ?"
                );

            } else {

                $stmt = $conn->prepare(
                    "UPDATE hazard_reports
                     SET status = ?
                     WHERE id = ?"
                );
            }

            $stmt->bind_param("si", $new_status, $report_id);

            if ($stmt->execute()) {

                // Record the change in road history.
                $action = $old_status . " → " . $new_status;

                $history = $conn->prepare(
                    "INSERT INTO road_history
                     (hazard_report_id, action, notes)
                     VALUES (?, ?, ?)"
                );

                $history->bind_param(
                    "iss",
                    $report_id,
                    $action,
                    $notes
                );

                if ($history->execute()) {
                    $message = "Report status updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Status updated, but the history entry could not be saved.";
                    $message_type = "error";
                }

                $history->close();

            } else {

                $message = "Failed to update report status.";
                $message_type = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}

// ==============================
// GET REPORT INFORMATION
// ==============================

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

$stmt->bind_param("i", $report_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo "Report not found.";
    exit;
}

$report = $result->fetch_assoc();
$stmt->close();

$current_status = $report["status"] ?? "Pending";

$photo = $report["photo"] ?? "";

$valid_photo = (
    $photo !== "" &&
    preg_match(
        '/\A[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)\z/i',
        $photo
    )
);

$status_class = strtolower(
    str_replace(" ", "-", $current_status)
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Report - RoadAlert</title>

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

    <!-- REPORT DETAILS -->

    <main class="container">

        <section class="card">

            <h1>Manage Road Report</h1>

            <p>
                Review the report details before changing its status.
            </p>

            <br>

            <?php if ($message !== ""): ?>
                <div class="alert <?= htmlspecialchars($message_type, ENT_QUOTES, "UTF-8") ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <h2>Report Information</h2>

            <p>
                <strong>Resident:</strong>
                <?= htmlspecialchars($report["full_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Username:</strong>
                <?= htmlspecialchars($report["username"] ?? "", ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Hazard Type:</strong>
                <?= htmlspecialchars($report["hazard_type"] ?? "", ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Location:</strong>
                <?= htmlspecialchars($report["location"] ?? "", ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Description:</strong><br>
                <?= nl2br(htmlspecialchars($report["description"] ?? "", ENT_QUOTES, "UTF-8")) ?>
            </p>

            <p>
                <strong>Current Status:</strong>

                <span class="badge <?= htmlspecialchars($status_class, ENT_QUOTES, "UTF-8") ?>">
                    <?= htmlspecialchars($current_status, ENT_QUOTES, "UTF-8") ?>
                </span>
            </p>

            <?php if ($valid_photo): ?>

                <p><strong>Hazard Photo:</strong></p>

                <a
                    href="../assets/uploads/<?= rawurlencode($photo) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <img
                        src="../assets/uploads/<?= rawurlencode($photo) ?>"
                        alt="Road hazard photo"
                        style="width: 100%; max-width: 600px; border-radius: 12px;"
                    >
                </a>

            <?php endif; ?>

            <hr>

            <br>

            <h2>Update Status</h2>

            <form method="POST" action="update_status.php?id=<?= $report_id ?>">

                <label for="status">New Status</label>

                <select name="status" id="status" required>

                    <?php foreach ($allowed_statuses as $status): ?>

                        <option
                            value="<?= htmlspecialchars($status, ENT_QUOTES, "UTF-8") ?>"
                            <?= $current_status === $status ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($status, ENT_QUOTES, "UTF-8") ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <label for="notes">Notes</label>

                <textarea
                    name="notes"
                    id="notes"
                    rows="5"
                    maxlength="5000"
                    placeholder="Enter notes about this status change..."
                ></textarea>

                <button type="submit" class="btn">
                    Update Status
                </button>

                <a href="reports.php" class="btn btn-secondary">
                    Back to Reports
                </a>

            </form>

        </section>

    </main>

</body>
</html>
