
<?php

session_start();

require_once "config/database.php";

// ==============================
// ACCESS CONTROL
// ==============================

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "resident"
) {
    header("Location: admin/dashboard.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

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

if (!$stmt) {
    die("Unable to retrieve report history. Please try again later.");
}

$stmt->bind_param("i", $user_id);
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

    <title>Report History - RoadAlert</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>
        .history-page {
            margin-top: 35px;
            margin-bottom: 40px;
        }

        .history-heading {
            margin-bottom: 22px;
        }

        .history-heading h1 {
            margin-bottom: 8px;
            color: var(--text);
        }

        .history-heading p {
            color: var(--muted);
        }

        .history-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .history-table th,
        .history-table td {
            padding: 14px 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--border);
        }

        .history-table th {
            font-size: 13px;
            color: var(--text);
            background: #f3f8f9;
        }

        .history-table td {
            font-size: 14px;
            line-height: 1.5;
        }

        .history-table tr:last-child td {
            border-bottom: none;
        }

        .history-photo {
            color: var(--primary-dark);
            font-weight: 600;
            text-decoration: none;
        }

        .history-photo:hover {
            text-decoration: underline;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff1cc;
            color: #925c00;
        }

        .status-verified {
            background: #dcfce7;
            color: #166534;
        }

        .status-resolved {
            background: #e5e7eb;
            color: #374151;
        }

        .status-under-construction {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .empty-history {
            text-align: center;
            padding: 35px 20px;
        }

        .empty-history h2 {
            margin-bottom: 10px;
        }

        .empty-history p {
            color: var(--muted);
            margin-bottom: 20px;
        }

        .history-button {
            display: inline-block;
            padding: 11px 18px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
        }

        .history-button:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 700px) {
            .history-page {
                width: 94%;
                margin-top: 24px;
            }

            .history-heading h1 {
                font-size: 26px;
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

<main class="container history-page">

    <section class="card history-heading">
        <h1>My Road Hazard Reports</h1>
        <p>
            View the road hazards you have submitted and check their
            latest status.
        </p>
    </section>

    <?php if ($result->num_rows === 0): ?>

        <section class="card empty-history">
            <h2>No Reports Yet</h2>

            <p>
                You haven't submitted any road hazard reports.
            </p>

            <a href="report.php" class="history-button">
                Report a Hazard
            </a>
        </section>

    <?php else: ?>

        <section class="card">
            <div class="history-table-wrapper">

                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Hazard</th>
                            <th>Location</th>
                            <th>Description</th>
                            <th>Photo</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($report = $result->fetch_assoc()): ?>

                            <?php
                            $status = trim($report["status"] ?? "");

                            $status_class = match ($status) {
                                "Pending" => "status-pending",
                                "Verified" => "status-verified",
                                "Resolved" => "status-resolved",
                                "Under Construction" => "status-under-construction",
                                default => ""
                            };
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars($report["hazard_type"] ?? "") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($report["location"] ?? "") ?>
                                </td>

                                <td>
                                    <?= nl2br(htmlspecialchars($report["description"] ?? "")) ?>
                                </td>

                                <td>
                                    <?php
                                    $photo = $report["photo"] ?? "";

                                    $valid_photo = (
                                        $photo !== "" &&
                                        preg_match(
                                            '/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/i',
                                            $photo
                                        )
                                    );
                                    ?>

                                    <?php if ($valid_photo): ?>
                                        <a
                                            class="history-photo"
                                            href="assets/uploads/<?= rawurlencode($photo) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            View Photo
                                        </a>
                                    <?php else: ?>
                                        No photo
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status-badge <?= htmlspecialchars($status_class) ?>">
                                        <?= htmlspecialchars($status ?: "Unknown") ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    $timestamp = strtotime($report["created_at"] ?? "");
                                    echo $timestamp
                                        ? date("M d, Y h:i A", $timestamp)
                                        : "Unavailable";
                                    ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                    </tbody>
                </table>

            </div>
        </section>

    <?php endif; ?>

</main>

</body>
</html>

<?php
$stmt->close();
?>
