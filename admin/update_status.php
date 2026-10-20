
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

if (
    !isset($_GET["id"]) ||
    !ctype_digit((string) $_GET["id"]) ||
    (int) $_GET["id"] < 1
) {
    header("Location: reports.php");
    exit;
}

$report_id = (int) $_GET["id"];

$message = "";
$message_type = "";

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

        $check = $conn->prepare(
            "SELECT status FROM hazard_reports WHERE id = ?"
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

            if ($stmt) {

                $stmt->bind_param("si", $new_status, $report_id);

                if ($stmt->execute()) {

                    $action = $old_status . " → " . $new_status;

                    $history = $conn->prepare(
                        "INSERT INTO road_history
                         (hazard_report_id, action, notes)
                         VALUES (?, ?, ?)"
                    );

                    if ($history) {

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
                        $message = "Status updated, but the history query could not be prepared.";
                        $message_type = "error";
                    }

                } else {

                    $message = "Failed to update report status.";
                    $message_type = "error";
                }

                $stmt->close();

            } else {

                $message = "Unable to prepare the status update.";
                $message_type = "error";
            }
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

    <style>
        :root {
            --ra-primary: #087f8c;
            --ra-primary-dark: #066672;
            --ra-bg: #f2f7f8;
            --ra-card: #ffffff;
            --ra-text: #20343d;
            --ra-muted: #70828b;
            --ra-border: #e0e9ec;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--ra-bg);
            color: var(--ra-text);
            font-family: Arial, Helvetica, sans-serif;
        }

        /* NAVIGATION */

        .topbar {
            min-height: 70px;
            padding: 0 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            background: #ffffff;
            border-bottom: 1px solid var(--ra-border);
        }

        .brand {
            color: var(--ra-primary);
            font-size: 23px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .brand span {
            color: #263f49;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links a {
            color: #455c65;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--ra-primary);
        }

        /* MAIN LAYOUT */

        .container {
            width: min(900px, 92%);
            margin: 42px auto;
        }

        .page-heading {
            margin-bottom: 24px;
        }

        .page-heading h1 {
            margin: 0 0 8px;
            font-size: clamp(25px, 4vw, 32px);
            letter-spacing: -0.7px;
        }

        .page-heading p {
            margin: 0;
            color: var(--ra-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .card {
            padding: clamp(22px, 4vw, 38px);
            background: var(--ra-card);
            border: 1px solid var(--ra-border);
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(29, 68, 78, 0.07);
        }

        .section-title {
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 750;
        }

        .section-subtitle {
            margin: -10px 0 22px;
            color: var(--ra-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        /* ALERT MESSAGES */

        .alert {
            margin-bottom: 24px;
            padding: 15px 17px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert.success {
            color: #17683b;
            background: #e8f7ed;
            border: 1px solid #c3e8ce;
        }

        .alert.error {
            color: #a52b24;
            background: #fff0ef;
            border: 1px solid #f2ceca;
        }

        /* REPORT INFORMATION */

        .report-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .info-item {
            min-width: 0;
            padding: 16px;
            background: #f7fafb;
            border: 1px solid #edf1f3;
            border-radius: 11px;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-label {
            display: block;
            margin-bottom: 8px;
            color: var(--ra-muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .info-value {
            overflow-wrap: anywhere;
            color: var(--ra-text);
            font-size: 14px;
            line-height: 1.7;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            border-radius: 30px;
            background: #edf1f3;
            color: #52636b;
            font-size: 12px;
            font-weight: 750;
        }

        .status-badge.pending {
            background: #fff4d8;
            color: #916300;
        }

        .status-badge.verified {
            background: #e2f2ff;
            color: #1768a3;
        }

        .status-badge.resolved {
            background: #e2f6e9;
            color: #217343;
        }

        .status-badge.under-construction {
            background: #fff0db;
            color: #99570b;
        }

        .status-badge.road-closed {
            background: #ffe5e5;
            color: #a52b2b;
        }

        .hazard-photo {
            display: block;
            width: 100%;
            max-width: 600px;
            max-height: 400px;
            margin-top: 8px;
            object-fit: contain;
            object-position: left center;
            background: #f7fafb;
            border: 1px solid var(--ra-border);
            border-radius: 12px;
        }

        /* DIVIDER */

        .section-divider {
            height: 1px;
            margin: 32px 0;
            border: 0;
            background: var(--ra-border);
        }

        /* UPDATE FORM */

        .update-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .form-group label {
            color: var(--ra-text);
            font-size: 14px;
            font-weight: 700;
        }

        .form-group select,
        .form-group textarea {
            display: block;
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #d7e2e6;
            border-radius: 10px;
            background: #ffffff;
            color: var(--ra-text);
            font: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group select {
            min-height: 49px;
            cursor: pointer;
        }

        .form-group textarea {
            min-height: 125px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--ra-primary);
            box-shadow: 0 0 0 3px rgba(8, 127, 140, 0.12);
        }

        .form-help {
            margin: 0;
            color: var(--ra-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        /* BUTTONS */

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 4px;
        }

        .btn {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 750;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-primary {
            background: var(--ra-primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--ra-primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #eaf5f6;
            border-color: #d9edef;
            color: var(--ra-primary-dark);
        }

        .btn-secondary:hover {
            background: #dceff1;
        }

        /* MOBILE */

        @media (max-width: 650px) {
            .topbar {
                padding: 16px 5%;
                align-items: flex-start;
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                width: 100%;
                flex-wrap: wrap;
                gap: 15px;
            }

            .container {
                margin: 25px auto;
            }

            .report-info {
                grid-template-columns: 1fr;
            }

            .info-item.full-width {
                grid-column: auto;
            }

            .form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
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
        <a href="dashboard.php">Admin Dashboard</a>
        <a href="reports.php">Reports</a>
        <a href="map.php">Road Map</a>
        <a href="../logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <div class="page-heading">
        <h1>Manage Road Report</h1>
        <p>
            Review the reported road hazard and update its current status.
        </p>
    </div>

    <section class="card">

        <?php if ($message !== ""): ?>
            <div
                class="alert <?= htmlspecialchars($message_type, ENT_QUOTES, "UTF-8") ?>"
                role="status"
            >
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <!-- REPORT INFORMATION -->

        <h2 class="section-title">Report Information</h2>

        <p class="section-subtitle">
            Details submitted by the resident.
        </p>

        <div class="report-info">

            <div class="info-item">
                <span class="info-label">Resident</span>
                <div class="info-value">
                    <?= htmlspecialchars($report["full_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                </div>
            </div>

            <div class="info-item">
                <span class="info-label">Username</span>
                <div class="info-value">
                    <?= htmlspecialchars($report["username"] ?? "", ENT_QUOTES, "UTF-8") ?>
                </div>
            </div>

            <div class="info-item">
                <span class="info-label">Hazard Type</span>
                <div class="info-value">
                    <?= htmlspecialchars($report["hazard_type"] ?? "", ENT_QUOTES, "UTF-8") ?>
                </div>
            </div>

            <div class="info-item">
                <span class="info-label">Current Status</span>
                <div class="info-value">
                    <span class="status-badge <?= htmlspecialchars($status_class, ENT_QUOTES, "UTF-8") ?>">
                        <?= htmlspecialchars($current_status, ENT_QUOTES, "UTF-8") ?>
                    </span>
                </div>
            </div>

            <div class="info-item full-width">
                <span class="info-label">Location</span>
                <div class="info-value">
                    <?= htmlspecialchars($report["location"] ?? "", ENT_QUOTES, "UTF-8") ?>
                </div>
            </div>

            <div class="info-item full-width">
                <span class="info-label">Description</span>
                <div class="info-value">
                    <?= nl2br(htmlspecialchars($report["description"] ?? "", ENT_QUOTES, "UTF-8")) ?>
                </div>
            </div>

            <?php if ($valid_photo): ?>

                <div class="info-item full-width">
                    <span class="info-label">Hazard Photo</span>

                    <a
                        href="../assets/uploads/<?= rawurlencode($photo) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <img
                            class="hazard-photo"
                            src="../assets/uploads/<?= rawurlencode($photo) ?>"
                            alt="Road hazard photo"
                        >
                    </a>
                </div>

            <?php endif; ?>

        </div>

        <hr class="section-divider">

        <!-- UPDATE STATUS FORM -->

        <h2 class="section-title">Update Report Status</h2>

        <p class="section-subtitle">
            Select the appropriate status and add notes about the change.
        </p>

        <form
            class="update-form"
            method="POST"
            action="update_status.php?id=<?= $report_id ?>"
        >

            <div class="form-group">
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

                <p class="form-help">
                    Choose the current condition of the reported road hazard.
                </p>
            </div>

            <div class="form-group">
                <label for="notes">Admin Notes</label>

                <textarea
                    name="notes"
                    id="notes"
                    rows="5"
                    maxlength="5000"
                    placeholder="Explain the status change or provide an update for the road report..."
                ></textarea>

                <p class="form-help">
                    Optional. Maximum 5,000 characters.
                </p>
            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Update Status
                </button>

                <a href="reports.php" class="btn btn-secondary">
                    Back to Reports
                </a>

            </div>

        </form>

    </section>

</main>

</body>
</html>