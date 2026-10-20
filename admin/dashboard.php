
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
            background: #f3f7f8;
            color: #183238;
        }

        /* HEADER */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 5%;
            background: #ffffff;
            border-bottom: 1px solid #dce7e9;
        }

        .brand {
            color: #087e8b;
            text-decoration: none;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 19px;
            background: #087f8c;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .logout-btn:hover {
            background: #075f68;
        }

        /* MAIN LAYOUT */

        .dashboard-container {
            width: min(1100px, 92%);
            margin: 35px auto 55px;
        }

        /* WELCOME BANNER */

        .dashboard-welcome {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            align-items: center;
            gap: 35px;
            padding: 35px;
            margin-bottom: 35px;
            border-radius: 16px;
            background: linear-gradient(120deg, #075f68, #0799a1);
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(8, 126, 139, 0.14);
        }

        .welcome-label {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            margin: 0 0 10px;
            color: #d7f7f8;
        }

        .dashboard-welcome h1 {
            font-size: clamp(27px, 3vw, 36px);
            line-height: 1.25;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .dashboard-welcome h1 span {
            display: block;
        }

        .welcome-description {
            font-size: 15px;
            line-height: 1.8;
            margin: 0;
            color: #efffff;
        }

        /* SECTION HEADINGS */

        .section-heading {
            margin: 0 0 18px;
        }

        .section-heading h2 {
            margin: 0 0 5px;
            font-size: 23px;
            color: #183238;
        }

        .section-heading p {
            margin: 0;
            font-size: 14px;
            color: #6b8085;
        }

        /* FEATURE CARDS */

        .dashboard-features {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-bottom: 38px;
        }

        .feature-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
            min-height: 235px;
            padding: 28px;
            background: #ffffff;
            border: 1px solid #dce7e9;
            border-radius: 15px;
            box-shadow: 0 5px 18px rgba(24, 50, 56, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(24, 50, 56, 0.09);
        }

        .feature-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            margin-bottom: 17px;
            border-radius: 14px;
            background: #e6f5f6;
            font-size: 27px;
        }

        .feature-card h3 {
            font-size: 19px;
            margin: 0 0 9px;
            color: #183238;
        }

        .feature-card p {
            color: #6b8085;
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 22px;
        }

        .feature-card .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
            padding: 11px 19px;
            border-radius: 8px;
            background: #087e8b;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .feature-card .btn:hover {
            background: #06616b;
        }

        .feature-card .btn-secondary {
            background: #e6f5f6;
            color: #06616b;
        }

        .feature-card .btn-secondary:hover {
            background: #d0ecee;
        }

        /* REPORT STATISTICS */

        .statistics-section {
            margin-top: 5px;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .dashboard-stat {
            min-width: 0;
            padding: 23px;
            background: #ffffff;
            border: 1px solid #dce7e9;
            border-top: 4px solid #087f8c;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(24, 50, 56, 0.025);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(24, 50, 56, 0.06);
        }

        .dashboard-stat h3 {
            font-size: 14px;
            font-weight: 600;
            color: #536168;
            margin: 0 0 10px;
        }

        .dashboard-stat strong {
            display: block;
            font-size: 32px;
            line-height: 1.2;
            color: #183238;
        }

        .stat-total {
            border-top-color: #087f8c;
        }

        .stat-pending {
            border-top-color: #d9a227;
        }

        .stat-verified {
            border-top-color: #27966b;
        }

        .stat-resolved {
            border-top-color: #77858b;
        }

        .stat-construction {
            border-top-color: #3488c7;
        }

        .stat-closed {
            border-top-color: #cf5151;
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 750px) {
            .dashboard-welcome {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 28px;
            }

            .dashboard-features {
                gap: 16px;
            }

            .dashboard-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .topbar {
                padding: 15px 4%;
            }

            .brand {
                font-size: 23px;
            }

            .logout-btn {
                padding: 9px 13px;
            }

            .dashboard-container {
                width: 92%;
                margin: 24px auto 35px;
            }

            .dashboard-welcome {
                padding: 24px 21px;
                margin-bottom: 28px;
            }

            .dashboard-welcome h1 {
                font-size: 27px;
            }

            .dashboard-features,
            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .feature-card {
                min-height: auto;
                padding: 23px;
            }

            .dashboard-stat {
                padding: 20px;
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
                Manage submitted road hazard reports, update their
                statuses, and locate reported hazards using the
                interactive road hazard map.
            </p>
        </section>

        <!-- DASHBOARD FEATURES: ROAD REPORTS CARD REMOVED -->

        <section>
            <div class="section-heading">
                <h2>Dashboard Overview</h2>
                <p>Choose a section to manage and monitor road hazards.</p>
            </div>

            <div class="dashboard-features">

                <!-- MANAGE REPORTS -->

                <article class="feature-card">
                    <div class="feature-icon">🚧</div>

                    <h3>Manage Reports</h3>

                    <p>
                        Review reports submitted by residents, inspect
                        hazard details, and update report statuses.
                    </p>

                    <a href="reports.php" class="btn">
                        Manage Reports
                    </a>
                </article>

                <!-- ROAD HAZARD MAP -->

                <article class="feature-card">
                    <div class="feature-icon">🗺️</div>

                    <h3>Road Hazard Map</h3>

                    <p>
                        Locate reported road hazards and inspect their
                        locations on the interactive map.
                    </p>

                    <a href="map.php" class="btn btn-secondary">
                        Open Map
                    </a>
                </article>

            </div>
        </section>

        <!-- REPORT STATISTICS -->

        <section class="statistics-section">

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
