
<?php

session_start();

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Only residents can access this dashboard
if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "resident"
) {
    header("Location: admin/dashboard.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "Resident";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#087f8c">

    <title>Dashboard - RoadAlert</title>

    <link rel="stylesheet" href="assets/css/style.css">

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
            padding: 16px 5%;
            background: #ffffff;
            border-bottom: 1px solid #d8e5e8;
        }

        .brand {
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
            color: #087f8c;
        }

        .brand span {
            color: #087f8c;
        }

        .logout-btn {
            display: inline-block;
            background: #087f8c;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .logout-btn:hover {
            background: #075f68;
        }

        /* MAIN DASHBOARD */

        .dashboard-container {
            width: min(1200px, 92%);
            margin: 35px auto 60px;
        }

        /* WELCOME BANNER */

        .dashboard-welcome {
            display: grid;
            grid-template-columns: 1fr;
            align-items: center;
            position: relative;
            overflow: hidden;

            padding: 35px 30px;
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

        .dashboard-welcome::after {
            content: "";
            position: absolute;
            width: 125px;
            height: 125px;
            right: 30px;
            top: 30px;
            border: 17px solid rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            pointer-events: none;
        }

        .welcome-label {
            margin: 0 0 12px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1.5px;
        }

        .dashboard-welcome h1 {
            margin: 0 0 10px;
            font-size: clamp(25px, 3vw, 34px);
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .welcome-description {
            margin: 0;
            max-width: 650px;
            font-size: 14px;
            line-height: 1.7;
        }

        /* SECTION HEADING */

        .section-heading {
            margin: 30px 0 18px;
        }

        .section-heading h2 {
            margin: 0 0 7px;
            font-size: 22px;
            color: #102c35;
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
            gap: 20px;
        }

        .feature-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;

            min-width: 0;
            min-height: 220px;
            padding: 24px;

            background: #ffffff;
            border: 1px solid #d8e5e8;
            border-radius: 13px;

            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.035);

            transition: transform 0.2s ease,
                        box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
        }

        .feature-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 42px;
            height: 42px;
            margin-bottom: 14px;

            background: #e4f4f5;
            border-radius: 11px;
            font-size: 23px;
        }

        .feature-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #102c35;
        }

        .feature-card p {
            margin: 0 0 22px;
            color: #5f6b70;
            font-size: 14px;
            line-height: 1.7;
        }

        .feature-card .btn {
            margin-top: auto;
            text-decoration: none;
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 850px) {
            .dashboard-features {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-welcome::after {
                right: -35px;
            }
        }

        @media (max-width: 550px) {
            .topbar {
                padding: 15px 4%;
            }

            .brand {
                font-size: 21px;
            }

            .logout-btn {
                padding: 9px 13px;
            }

            .dashboard-container {
                width: 92%;
                margin-top: 22px;
            }

            .dashboard-welcome {
                padding: 27px 22px;
            }

            .dashboard-welcome::after {
                width: 80px;
                height: 80px;
                border-width: 12px;
                right: -30px;
                top: 20px;
            }

            .dashboard-features {
                grid-template-columns: 1fr;
            }

            .feature-card {
                min-height: auto;
                padding: 22px;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER: LOGO AND LOGOUT BUTTON -->

    <header class="topbar">

        <a class="brand" href="dashboard.php">
            Road<span>Alert</span>
        </a>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </header>

    <main class="dashboard-container">

        <!-- WELCOME BANNER -->

        <section class="dashboard-welcome">

            <p class="welcome-label">
                ROAD HAZARD MONITORING SYSTEM
            </p>

            <h1>
                Hello,
                <?= htmlspecialchars(
                    $full_name,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </h1>

            <p class="welcome-description">
                Report road hazards, view road conditions,
                and help keep your community safe.
            </p>

        </section>

        <!-- DASHBOARD OVERVIEW -->

        <section>

            <div class="section-heading">
                <h2>Dashboard Overview</h2>
                <p>
                    Choose a section to manage and monitor road hazards.
                </p>
            </div>

            <div class="dashboard-features">

                <!-- REPORT A HAZARD -->

                <article class="feature-card">

                    <div class="feature-icon">🚧</div>

                    <h3>Report a Hazard</h3>

                    <p>
                        Report potholes, flooding, road construction,
                        debris, and other road hazards.
                    </p>

                    <a href="report.php" class="btn">
                        Report a Hazard
                    </a>

                </article>

                <!-- ROAD HAZARD MAP -->

                <article class="feature-card">

                    <div class="feature-icon">🗺️</div>

                    <h3>Road Hazard Map</h3>

                    <p>
                        View road hazards reported by other users
                        and check road conditions before traveling.
                    </p>

                    <a href="road_map.php" class="btn">
                        View Road Map
                    </a>

                </article>

                <!-- ROAD HISTORY -->

                <article class="feature-card">

                    <div class="feature-icon">📋</div>

                    <h3>Road History</h3>

                    <p>
                        View your previous road hazard reports
                        and check their current status.
                    </p>

                    <a href="history.php" class="btn">
                        View Road History
                    </a>

                </article>

            </div>

        </section>

    </main>

</body>
</html>
