
<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";

// Redirect users who are already logged in
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    }

    if (($_SESSION["role"] ?? "") === "resident") {
        header("Location: dashboard.php");
        exit;
    }
}

// Registration message
if (isset($_GET["registered"]) && $_GET["registered"] === "1") {
    $message = "Account created successfully. You can now log in.";
    $message_type = "success";
}

// Process login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $message = "Please enter your username and password.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, username, password, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {
                    if (
                        $user["role"] !== "admin" &&
                        $user["role"] !== "resident"
                    ) {
                        $message = "Invalid account role.";
                        $message_type = "error";
                    } else {
                        session_regenerate_id(true);

                        $_SESSION["user_id"] = $user["id"];
                        $_SESSION["full_name"] = $user["full_name"];
                        $_SESSION["username"] = $user["username"];
                        $_SESSION["role"] = $user["role"];

                        if ($user["role"] === "admin") {
                            header("Location: admin/dashboard.php");
                            exit;
                        }

                        header("Location: dashboard.php");
                        exit;
                    }
                } else {
                    $message = "Incorrect username or password.";
                    $message_type = "error";
                }
            } else {
                $message = "Incorrect username or password.";
                $message_type = "error";
            }

            $stmt->close();
        } else {
            $message = "Unable to process your login. Please try again.";
            $message_type = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f1f6f7">

    <title>Login - RoadAlert</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --background: #f1f6f7;
            --white: #ffffff;
            --teal: #087f8c;
            --teal-dark: #075f68;
            --teal-light: #0799a1;
            --text: #172a30;
            --muted: #5f6b70;
            --border: #d8e5e8;
            --input-bg: #ffffff;
        }

        body {
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
        }

        /* HEADER */

        .navbar {
            width: 100%;
            padding: 17px 7%;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 11px;
            font-size: 22px;
            font-weight: 800;
            color: var(--teal-dark);
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--teal);
            color: var(--white);
            border-radius: 10px;
            font-size: 20px;
        }

        .logo span {
            color: var(--teal);
        }

        /* MAIN LAYOUT */

        main {
            width: 100%;
            max-width: 1100px;
            margin: auto;
            padding: 40px 24px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1fr 0.85fr;
            gap: 45px;
            align-items: center;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.045);
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            border: 30px solid rgba(8, 127, 140, 0.045);
            border-radius: 50%;
            right: -100px;
            top: -130px;
            pointer-events: none;
        }

        .eyebrow {
            color: var(--teal);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 18px;
        }

        .hero h1 {
            font-size: clamp(32px, 4vw, 48px);
            line-height: 1.12;
            margin-bottom: 18px;
            color: var(--text);
        }

        .hero h1 span {
            color: var(--teal);
        }

        .hero-description {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
            max-width: 440px;
            margin-bottom: 25px;
        }

        .hero-points {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .hero-point {
            color: var(--teal-dark);
            font-size: 12px;
            background: #f5fafb;
            border: 1px solid var(--border);
            padding: 9px 12px;
            border-radius: 8px;
        }

        .hero-point i {
            color: var(--teal);
            margin-right: 5px;
        }

        /* LOGIN CARD */

        .login-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 28px;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.035);
        }

        .login-heading {
            margin-bottom: 24px;
        }

        .login-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5f4f5;
            color: var(--teal);
            border-radius: 10px;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .login-heading h2 {
            font-size: 26px;
            margin-bottom: 8px;
            color: var(--text);
        }

        .login-heading p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        /* ALERT MESSAGES */

        .alert {
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 18px;
        }

        .alert.success {
            color: #176b49;
            background: #e8f7ef;
            border: 1px solid #bce8ce;
        }

        .alert.error {
            color: #a62c2c;
            background: #fff0f0;
            border: 1px solid #f2caca;
        }

        /* FORM */

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #71858a;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 42px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--input-bg);
            color: var(--text);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input::placeholder {
            color: #829398;
        }

        .form-group input:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(8, 127, 140, 0.10);
        }

        .password-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #71858a;
            cursor: pointer;
            padding: 4px;
        }

        .password-toggle:hover {
            color: var(--teal);
        }

        /* LOGIN BUTTON */

        .login-button {
            width: 100%;
            border: none;
            border-radius: 9px;
            padding: 14px;
            margin-top: 5px;
            background: var(--teal);
            color: var(--white);
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .login-button:hover {
            background: var(--teal-dark);
            transform: translateY(-1px);
        }

        .login-button i {
            margin-left: 7px;
        }

        .register-text {
            color: var(--muted);
            text-align: center;
            font-size: 13px;
            margin-top: 22px;
        }

        .register-text a {
            color: var(--teal);
            font-weight: 700;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        /* FEATURE CARDS */

        .features {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .feature-card {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 23px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 13px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 18px rgba(0, 0, 0, 0.05);
        }

        .feature-icon {
            flex-shrink: 0;
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--teal);
            background: #e5f4f5;
            border-radius: 10px;
            font-size: 19px;
        }

        .feature-card h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: var(--text);
        }

        .feature-card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        /* FOOTER */

        footer {
            text-align: center;
            padding: 22px 15px;
            color: var(--muted);
            font-size: 12px;
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 800px) {
            .hero {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 28px;
            }

            .hero-description {
                max-width: 100%;
            }

            .login-card {
                padding: 25px;
            }
        }

        @media (max-width: 520px) {
            .navbar {
                padding: 16px 5%;
            }

            .logo {
                font-size: 18px;
            }

            .logo-icon {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }

            main {
                padding: 24px 14px;
            }

            .hero {
                padding: 22px 17px;
                border-radius: 15px;
            }

            .hero h1 {
                font-size: 34px;
            }

            .hero-description {
                font-size: 14px;
            }

            .login-card {
                padding: 21px 17px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 19px;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->

    <header class="navbar">
        <a href="login.php" class="logo">
            <div class="logo-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div>
                Road<span>Alert</span>
            </div>
        </a>
    </header>

    <main>

        <!-- HERO AND LOGIN FORM -->

        <section class="hero">

            <div class="hero-content">

                <div class="eyebrow">ROAD SAFETY SYSTEM</div>

                <h1>
                    Know the road.<br>
                    <span>Report hazards.</span>
                </h1>

                <p class="hero-description">
                    RoadAlert helps drivers report potholes, flooding,
                    construction, debris, damaged roads, and road closures.
                    Help keep your community informed and make every journey safer.
                </p>

                <div class="hero-points">

                    <div class="hero-point">
                        <i class="fa-solid fa-circle-check"></i>
                        Report Hazards
                    </div>

                    <div class="hero-point">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Check Road Conditions
                    </div>

                    <div class="hero-point">
                        <i class="fa-solid fa-route"></i>
                        Track Report Status
                    </div>

                </div>

            </div>

            <!-- LOGIN FORM -->

            <div class="login-card">

                <div class="login-heading">

                    <div class="login-icon">
                        <i class="fa-solid fa-user-lock"></i>
                    </div>

                    <h2>Welcome Back</h2>

                    <p>
                        Log in to your RoadAlert account to report hazards
                        and check road conditions.
                    </p>

                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert <?= htmlspecialchars($message_type, ENT_QUOTES, "UTF-8") ?>">
                        <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">

                    <div class="form-group">
                        <label for="username">Username</label>

                        <div class="input-wrapper">
                            <i class="fa-solid fa-user"></i>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Enter your username"
                                autocomplete="username"
                                value="<?= htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>

                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock"></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                id="togglePassword"
                                aria-label="Show password"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        Login
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                </form>

                <p class="register-text">
                    Don't have an account?
                    <a href="register.php">Create Account</a>
                </p>

            </div>

        </section>

        <!-- FEATURES -->

        <section class="features">

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div>
                    <h3>RoadAlert Reports</h3>

                    <p>
                        Report potholes, flooding, road construction,
                        debris, damaged roads, and road closures.
                    </p>
                </div>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>

                <div>
                    <h3>Road Map</h3>

                    <p>
                        View reported road hazards and road conditions
                        on the interactive map, when available in your account.
                    </p>
                </div>
            </article>

        </section>

    </main>

    <footer>
        &copy; <?= date("Y") ?> RoadAlert. Road safety starts with awareness.
    </footer>

    <script>
        // Show or hide password
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("password");

        togglePassword.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";

            passwordInput.type = isPassword ? "text" : "password";

            togglePassword.innerHTML = isPassword
                ? '<i class="fa-solid fa-eye-slash"></i>'
                : '<i class="fa-solid fa-eye"></i>';

            togglePassword.setAttribute(
                "aria-label",
                isPassword ? "Hide password" : "Show password"
            );
        });
    </script>

</body>
</html>