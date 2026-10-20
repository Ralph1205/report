
<?php

require_once "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $date_of_birth = $_POST["date_of_birth"] ?? "";
    $sex = $_POST["sex"] ?? "";
    $civil_status = $_POST["civil_status"] ?? "";
    $address = trim($_POST["address"] ?? "");
    $contact_number = trim($_POST["contact_number"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $full_name === "" ||
        $date_of_birth === "" ||
        $sex === "" ||
        $civil_status === "" ||
        $address === "" ||
        $contact_number === "" ||
        $email === "" ||
        $username === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $message_type = "error";

    } else {

        // Check whether username or email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );

        $check->bind_param("ss", $username, $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Username or email is already registered.";
            $message_type = "error";

        } else {

            // Hash password securely
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Register resident account
            $stmt = $conn->prepare(
                "INSERT INTO users
                (
                    full_name,
                    date_of_birth,
                    sex,
                    civil_status,
                    address,
                    contact_number,
                    email,
                    username,
                    password,
                    role
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'resident')"
            );

            $stmt->bind_param(
                "sssssssss",
                $full_name,
                $date_of_birth,
                $sex,
                $civil_status,
                $address,
                $contact_number,
                $email,
                $username,
                $hashed_password
            );

            if ($stmt->execute()) {

                $stmt->close();
                $check->close();
                $conn->close();

                header("Location: login.php?registered=1");
                exit;

            } else {

                $message = "Account creation failed.";
                $message_type = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#f0f6f7">

    <title>Create Account - RoadAlert</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        /* =========================
           GENERAL
        ========================= */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f6f7;
            color: #183342;
        }

        a {
            color: #078995;
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            color: #056c76;
            text-decoration: underline;
        }

        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            width: 100%;
            min-height: 56px;
            padding: 0 7%;
            display: flex;
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #dce7e9;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #078995;
            font-size: 19px;
            font-weight: 800;
        }

        .logo-icon {
            width: 31px;
            height: 31px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #078995;
            color: #ffffff;
            border-radius: 7px;
            font-size: 16px;
        }

        /* =========================
           MAIN LAYOUT
        ========================= */

        .register-page {
            width: 100%;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 38px 20px;
        }

        .register-wrapper {
            width: 100%;
            max-width: 1050px;
        }

        .register-card {
            position: relative;
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 35px;
            align-items: center;
            padding: 36px;
            background: #ffffff;
            border: 1px solid #d9e5e8;
            border-radius: 14px;
            box-shadow: 0 5px 18px rgba(28, 60, 70, 0.05);
            overflow: hidden;
        }

        .register-card::before {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            top: -145px;
            right: -80px;
            border: 18px solid #f4f9fa;
            border-radius: 50%;
            pointer-events: none;
        }

        /* =========================
           LEFT INTRODUCTION
        ========================= */

        .intro {
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            margin-bottom: 13px;
            color: #078995;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .intro h1 {
            margin: 0 0 16px;
            color: #183342;
            font-size: clamp(29px, 3vw, 38px);
            line-height: 1.15;
            font-weight: 800;
        }

        .intro h1 span {
            color: #078995;
        }

        .intro p {
            margin: 0 0 23px;
            color: #627684;
            font-size: 13px;
            line-height: 1.8;
        }

        .feature-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .feature {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 10px;
            color: #087d89;
            background: #f4f9fa;
            border: 1px solid #d9e8eb;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
        }

        /* =========================
           FORM CARD
        ========================= */

        .form-card {
            position: relative;
            z-index: 1;
            padding: 25px 23px;
            background: #ffffff;
            border: 1px solid #d9e5e8;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(28, 60, 70, 0.04);
        }

        .form-heading-icon {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: #078995;
            background: #e5f4f5;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-card h2 {
            margin: 0 0 7px;
            color: #183342;
            font-size: 21px;
        }

        .form-description {
            margin: 0 0 20px;
            color: #647784;
            font-size: 12px;
            line-height: 1.6;
        }

        /* =========================
           ALERT MESSAGE
        ========================= */

        .alert {
            padding: 11px 12px;
            margin-bottom: 17px;
            border-radius: 7px;
            font-size: 12px;
            line-height: 1.5;
        }

        .alert.error {
            color: #a52b35;
            background: #fff0f0;
            border: 1px solid #f3caca;
        }

        /* =========================
           INPUTS
        ========================= */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 12px;
        }

        .form-group {
            min-width: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #183342;
            font-size: 11px;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap > i {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #8297a2;
            font-size: 12px;
            pointer-events: none;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            display: block;
            width: 100%;
            min-width: 0;
            padding: 11px 12px;
            color: #263e4a;
            background: #ffffff;
            border: 1px solid #d5e2e6;
            border-radius: 7px;
            outline: none;
            font-family: inherit;
            font-size: 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group .input-wrap input {
            padding-left: 34px;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #8a9da7;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #078995;
            box-shadow: 0 0 0 3px rgba(7, 137, 149, 0.10);
        }

        .form-group textarea {
            min-height: 76px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-group select {
            cursor: pointer;
        }

        /* =========================
           PASSWORD VISIBILITY
        ========================= */

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            padding: 4px;
            color: #718792;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 12px;
        }

        .password-toggle:hover {
            color: #078995;
        }

        /* =========================
           BUTTON
        ========================= */

        .submit-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: 20px;
            padding: 13px 16px;
            color: #ffffff;
            background: #078995;
            border: none;
            border-radius: 7px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .submit-btn:hover {
            background: #067580;
            transform: translateY(-1px);
        }

        /* =========================
           LOGIN LINK
        ========================= */

        .login-link {
            margin: 17px 0 0;
            color: #647784;
            text-align: center;
            font-size: 11px;
            line-height: 1.7;
        }

        /* =========================
           BOTTOM INFORMATION CARDS
        ========================= */

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 17px;
        }

        .info-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 17px;
            background: #ffffff;
            border: 1px solid #d9e5e8;
            border-radius: 10px;
        }

        .info-icon {
            flex-shrink: 0;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #078995;
            background: #e5f4f5;
            border-radius: 8px;
        }

        .info-card h3 {
            margin: 1px 0 6px;
            color: #183342;
            font-size: 12px;
        }

        .info-card p {
            margin: 0;
            color: #647784;
            font-size: 11px;
            line-height: 1.6;
        }

        /* =========================
           FOOTER
        ========================= */

        .footer {
            padding: 16px 20px;
            color: #71858f;
            text-align: center;
            font-size: 10px;
        }

        /* =========================
           RESPONSIVE DESIGN
        ========================= */

        @media (max-width: 800px) {

            .register-page {
                padding: 28px 16px;
            }

            .register-card {
                grid-template-columns: 1fr;
                gap: 26px;
                padding: 25px;
            }

            .intro h1 {
                font-size: 31px;
            }

            .form-card {
                padding: 22px;
            }
        }

        @media (max-width: 480px) {

            .navbar {
                padding: 0 18px;
            }

            .register-page {
                padding: 20px 12px;
            }

            .register-card {
                padding: 18px;
                gap: 22px;
            }

            .intro h1 {
                font-size: 28px;
            }

            .form-card {
                padding: 18px 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 13px;
            }

            .form-group.full-width {
                grid-column: auto;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .feature-list {
                gap: 6px;
            }
        }

    </style>

</head>

<body>

    <!-- NAVBAR -->

    <header class="navbar">

        <a href="login.php" class="logo">
            <span class="logo-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </span>

            <span>RoadAlert</span>
        </a>

    </header>

    <!-- MAIN CONTENT -->

    <main class="register-page">

        <div class="register-wrapper">

            <section class="register-card">

                <!-- LEFT INTRODUCTION -->

                <div class="intro">

                    <div class="eyebrow">
                        Road Safety System
                    </div>

                    <h1>
                        Join the community.<br>
                        <span>Make roads safer.</span>
                    </h1>

                    <p>
                        Create your RoadAlert account to report road hazards,
                        check road conditions, and help keep your community
                        informed about safer routes.
                    </p>

                    <div class="feature-list">

                        <span class="feature">
                            <i class="fa-solid fa-circle-check"></i>
                            Report Hazards
                        </span>

                        <span class="feature">
                            <i class="fa-solid fa-map-location-dot"></i>
                            Check Road Conditions
                        </span>

                        <span class="feature">
                            <i class="fa-solid fa-route"></i>
                            Track Report Status
                        </span>

                    </div>

                </div>

                <!-- REGISTRATION FORM -->

                <div class="form-card">

                    <div class="form-heading-icon">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>

                    <h2>Create Account</h2>

                    <p class="form-description">
                        Register as a RoadAlert resident to get started.
                    </p>

                    <?php if ($message !== ""): ?>

                        <div class="alert <?= htmlspecialchars($message_type) ?>">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?= htmlspecialchars($message) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST" action="">

                        <div class="form-grid">

                            <!-- FULL NAME -->

                            <div class="form-group full-width">

                                <label for="full_name">
                                    Full Name
                                </label>

                                <div class="input-wrap">

                                    <i class="fa-solid fa-user"></i>

                                    <input
                                        type="text"
                                        id="full_name"
                                        name="full_name"
                                        placeholder="Enter your full name"
                                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                        autocomplete="name"
                                        required
                                    >

                                </div>

                            </div>

                            <!-- DATE OF BIRTH -->

                            <div class="form-group">

                                <label for="date_of_birth">
                                    Date of Birth
                                </label>

                                <input
                                    type="date"
                                    id="date_of_birth"
                                    name="date_of_birth"
                                    value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>"
                                    required
                                >

                            </div>

                            <!-- SEX -->

                            <div class="form-group">

                                <label for="sex">
                                    Sex
                                </label>

                                <select id="sex" name="sex" required>

                                    <option value="">
                                        Select Sex
                                    </option>

                                    <option
                                        value="Male"
                                        <?= (($_POST['sex'] ?? '') === 'Male') ? 'selected' : '' ?>
                                    >
                                        Male
                                    </option>

                                    <option
                                        value="Female"
                                        <?= (($_POST['sex'] ?? '') === 'Female') ? 'selected' : '' ?>
                                    >
                                        Female
                                    </option>

                                </select>

                            </div>

                            <!-- CIVIL STATUS -->

                            <div class="form-group">

                                <label for="civil_status">
                                    Civil Status
                                </label>

                                <select
                                    id="civil_status"
                                    name="civil_status"
                                    required
                                >

                                    <option value="">
                                        Select Civil Status
                                    </option>

                                    <?php
                                    $civil_options = [
                                        "Single",
                                        "Married",
                                        "Widowed",
                                        "Separated"
                                    ];

                                    foreach ($civil_options as $option):
                                    ?>

                                        <option
                                            value="<?= htmlspecialchars($option) ?>"
                                            <?= (($_POST['civil_status'] ?? '') === $option) ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($option) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- CONTACT NUMBER -->

                            <div class="form-group">

                                <label for="contact_number">
                                    Contact Number
                                </label>

                                <div class="input-wrap">

                                    <i class="fa-solid fa-phone"></i>

                                    <input
                                        type="tel"
                                        id="contact_number"
                                        name="contact_number"
                                        placeholder="09XXXXXXXXX"
                                        value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>"
                                        autocomplete="tel"
                                        required
                                    >

                                </div>

                            </div>

                            <!-- ADDRESS -->

                            <div class="form-group full-width">

                                <label for="address">
                                    Complete Address
                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    placeholder="Enter your complete address"
                                    autocomplete="street-address"
                                    required
                                ><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

                            </div>

                            <!-- EMAIL -->

                            <div class="form-group full-width">

                                <label for="email">
                                    Email Address
                                </label>

                                <div class="input-wrap">

                                    <i class="fa-solid fa-envelope"></i>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        placeholder="Enter your email"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                        autocomplete="email"
                                        required
                                    >

                                </div>

                            </div>

                            <!-- USERNAME -->

                            <div class="form-group full-width">

                                <label for="username">
                                    Username
                                </label>

                                <div class="input-wrap">

                                    <i class="fa-solid fa-user"></i>

                                    <input
                                        type="text"
                                        id="username"
                                        name="username"
                                        placeholder="Create a username"
                                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                        autocomplete="username"
                                        required
                                    >

                                </div>

                            </div>

                            <!-- PASSWORD -->

                            <div class="form-group">

                                <label for="password">
                                    Password
                                </label>

                                <div class="input-wrap">

                                    <i class="fa-solid fa-lock"></i>

                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        placeholder="Create a password"
                                        minlength="6"
                                        autocomplete="new-password"
                                        style="padding-right: 36px;"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        onclick="togglePassword('password', this)"
                                        aria-label="Show password"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                </div>

                            </div>

                            <!-- CONFIRM PASSWORD -->

                            <div class="form-group">

                                <label for="confirm_password">
                                    Confirm Password
                                </label>

                                <div class="input-wrap">

                                    <i class="fa-solid fa-lock"></i>

                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        placeholder="Confirm your password"
                                        minlength="6"
                                        autocomplete="new-password"
                                        style="padding-right: 36px;"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        onclick="togglePassword('confirm_password', this)"
                                        aria-label="Show confirm password"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                </div>

                            </div>

                        </div>

                        <!-- SUBMIT BUTTON -->

                        <button type="submit" class="submit-btn">

                            Create Account

                            <i class="fa-solid fa-arrow-right"></i>

                        </button>

                    </form>

                    <p class="login-link">
                        Already have an account?
                        <a href="login.php">Login here</a>
                    </p>

                </div>

            </section>

            <!-- INFORMATION CARDS -->

            <section class="info-grid">

                <div class="info-card">

                    <div class="info-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <div>

                        <h3>RoadAlert Reports</h3>

                        <p>
                            Report potholes, flooding, road construction,
                            debris, damaged roads, and road closures.
                        </p>

                    </div>

                </div>

                <div class="info-card">

                    <div class="info-icon">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>

                    <div>

                        <h3>Road Map</h3>

                        <p>
                            View reported road hazards and road conditions
                            on the interactive map when available.
                        </p>

                    </div>

                </div>

            </section>

        </div>

    </main>

    <!-- FOOTER -->

    <footer class="footer">
        © <?= date("Y") ?> RoadAlert. Road safety starts with awareness.
    </footer>

    <!-- PASSWORD VISIBILITY SCRIPT -->

    <script>

        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);
            const icon = button.querySelector("i");

            if (input.type === "password") {

                input.type = "text";

                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");

                button.setAttribute("aria-label", "Hide password");

            } else {

                input.type = "password";

                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");

                button.setAttribute("aria-label", "Show password");

            }

        }

        // Check that both passwords match before submitting.
        document.querySelector("form").addEventListener("submit", function (event) {

            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;

            if (password !== confirmPassword) {

                event.preventDefault();

                alert("Passwords do not match. Please try again.");

                document.getElementById("confirm_password").focus();

            }

        });

    </script>

</body>

</html>
