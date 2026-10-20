
<?php

session_start();

require_once "../config/database.php";

// LOGIN CHECK
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// ADMIN CHECK
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

// GET REPORT COUNTS
function getReportCount($conn, $status = null)
{
    if ($status === null) {
        $sql = "SELECT COUNT(*) AS total FROM hazard_reports";
        $result = $conn->query($sql);
    } else {
        $sql = "SELECT COUNT(*) AS total
                FROM hazard_reports
                WHERE status = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("s", $status);
        $stmt->execute();

        $result = $stmt->get_result();
        $stmt->close();
    }

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) $row["total"];
}

$total_reports = getReportCount($conn);
$pending_reports = getReportCount($conn, "Pending");
$verified_reports = getReportCount($conn, "Verified");
$resolved_reports = getReportCount($conn, "Resolved");
$construction_reports = getReportCount($conn, "Under Construction");
$closed_reports = getReportCount($conn, "Road Closed");

$admin_name = $_SESSION["full_name"] ?? "Admin";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - RoadAlert</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
        }

        /* HEADER */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 5%;
        }

        .brand {
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
        }

        /* LOGOUT BUTTON */

        .logout-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #087f8c;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-radius: 7px;
            transition: background 0.2s ease;
        }

        .logout-btn:hover {
            background: #075f68;
        }

        /* MAIN LAYOUT */

        .dashboard-container {
            width: min(1200px, 92%);
            margin: 35px auto 60px;
        }

        /* WELCOME SECTION */

        .dashboard-welcome {
            display: grid;
            grid-template-columns: 1fr 2fr;
            align-items: center;
            gap: 25px;
            padding: 35px;
            margin-bottom: 30px;
            border-radius: 15px;

            background: linear-gradient(
                120deg,
                #075f68,
                #0799a1
            );

            color: #ffffff;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
        }

        .welcome-label {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }

        .dashboard-welcome h1 {
            font-size: clamp(25px, 3vw, 36px);
            line-height: 1.25;
            margin: 0;
        }

        .dashboard-welcome h1 span {
            display: block;
            overflow-wrap: anywhere;
        }

        .welcome-description {
            font-size: 15px;
            line-height: 1.8;
            margin: 0;
        }

        /* SECTION HEADINGS */

        .section-heading {
            margin: 35px 0 18px;
        }

        .section-heading h2 {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .section-heading p {
            margin: 0;
            font-size: 14px;
            color: #5f6b70;
        }

        /* FEATURE CARDS */

        .dashboard-features {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .feature-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 25px;
            min-height: 235px;

            background: #ffffff;
            border: 1px solid #d8e5e8;
            border-radius: 13px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.035);

            transition: transform 0.2s ease,
                        box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
        }

        .feature-icon {
            font-size: 28px;
            margin-bottom: 14px;
        }

        .feature-card h3 {
            font-size: 18px;
            margin: 0 0 10px;
        }

        .feature-card p {
            color: #5f6b70;
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 22px;
        }

        .feature-card .btn {
            margin-top: auto;
            text-decoration: none;
        }

        /* STATISTICS */

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .dashboard-stat {
            padding: 22px;
            border: 1px solid #d8e5e8;
            border-radius: 12px;
            background: #ffffff;
        }

        .dashboard-stat h3 {
            font-size: 14px;
            font-weight: 600;
            color: #536168;
            margin: 0 0 12px;
        }

        .dashboard-stat strong {
            display: block;
            font-size: 32px;
            line-height: 1.2;
        }

        .stat-total {
            border-top: 4px solid #087f8c;
        }

        .stat-pending {
            border-top: 4px solid #d9a227;
        }

        .stat-verified {
            border-top: 4px solid #27966b;
        }

        .stat-resolved {
            border-top: 4px solid #77858b;
        }

        .stat-construction {
            border-top: 4px solid #3488c7;
        }

        .stat-closed {
            border-top: 4px solid #cf5151;
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 850px) {
            .dashboard-features {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-welcome {
                grid-template-columns: 1fr;
                gap: 18px;
            }
        }

        @media (max-width: 550px) {
            .dashboard-container {
                width: 92%;
                margin-top: 22px;
            }

            .topbar {
                padding: 16px 4%;
            }

            .dashboard-welcome {
                padding: 25px 22px;
            }

            .dashboard-features,
            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .feature-card {
                min-height: auto;
            }

            .dashboard-stat {
                padding: 18px;
            }

            .logout-btn {
                padding: 9px 14px;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->

    <header class="topbar">

        <a class="brand" href="dashboard.php">
            Road<span>Alert</span>
        </a>

        <a href="../logout.php" class="logout-btn">
            Logout
        </a>

    </header>

    <main class="dashboard-container">

        <!-- WELCOME BANNER -->

        <section class="dashboard-welcome">

            <div>
                <p class="welcome-label">ROADALERT ADMINISTRATION</p>

                <h1>
                    Welcome,
                    <span>
                        <?= htmlspecialchars(
                            $admin_name,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </span>
                </h1>
            </div>

            <p class="welcome-description">
                Manage submitted road hazard reports, monitor report
                statuses, and view reported hazards on the interactive map.
            </p>

        </section>

        <!-- DASHBOARD FEATURES -->

        <section>

            <div class="section-heading">
                <h2>Dashboard Overview</h2>
                <p>Choose a section to manage and monitor road hazards.</p>
            </div>

            <div class="dashboard-features">

                <article class="feature-card">
                    <div class="feature-icon">🚧</div>

                    <h3>Manage Reports</h3>

                    <p>
                        Review submitted road hazards and update their
                        status as reports are processed.
                    </p>

                    <a href="reports.php" class="btn">
                        View Reports
                    </a>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">🗺️</div>

                    <h3>Road Hazard Map</h3>

                    <p>
                        Locate reported road hazards and inspect their
                        locations on the interactive map.
                    </p>

                    <a href="map.php" class="btn">
                        Open Map
                    </a>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">📋</div>

                    <h3>Road Reports</h3>

                    <p>
                        View submitted reports and check their current
                        status in one place.
                    </p>

                    <a href="reports.php" class="btn btn-secondary">
                        View All Reports
                    </a>
                </article>

            </div>

        </section>

        <!-- REPORT STATISTICS -->

        <section>

            <div class="section-heading">
                <h2>Report Statistics</h2>
                <p>
                    Summary of road hazard reports recorded in the system.
                </p>
            </div>

            <div class="dashboard-stats">

                <article class="dashboard-stat stat-total">
                    <h3>Total Reports</h3>
                    <strong><?= $total_reports ?></strong>
                </article>

                <article class="dashboard-stat stat-pending">
                    <h3>Pending</h3>
                    <strong><?= $pending_reports ?></strong>
                </article>

                <article class="dashboard-stat stat-verified">
                    <h3>Verified</h3>
                    <strong><?= $verified_reports ?></strong>
                </article>

                <article class="dashboard-stat stat-resolved">
                    <h3>Resolved</h3>
                    <strong><?= $resolved_reports ?></strong>
                </article>

                <article class="dashboard-stat stat-construction">
                    <h3>Under Construction</h3>
                    <strong><?= $construction_reports ?></strong>
                </article>

                <article class="dashboard-stat stat-closed">
                    <h3>Road Closed</h3>
                    <strong><?= $closed_reports ?></strong>
                </article>

            </div>

        </section>

    </main>

</body>
</html>
