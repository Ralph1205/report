<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";


// ==============================
// SHOW REGISTRATION MESSAGE
// ==============================

if (
    isset($_GET["registered"]) &&
    $_GET["registered"] === "1"
) {

    $message =
        "Account created successfully. You can now log in.";

    $message_type = "success";
}


// ==============================
// PROCESS LOGIN
// ==============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";


    // ==============================
    // VALIDATION
    // ==============================

    if (
        $username === "" ||
        $password === ""
    ) {

        $message =
            "Please enter your username and password.";

        $message_type = "error";

    } else {


        // ==============================
        // FIND USER
        // ==============================

        $stmt = $conn->prepare(
            "SELECT
                id,
                full_name,
                username,
                password,
                role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );


        $stmt->bind_param(
            "s",
            $username
        );


        $stmt->execute();


        $result = $stmt->get_result();


        // ==============================
        // CHECK USER
        // ==============================

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();


            // ==============================
            // CHECK PASSWORD
            // ==============================

            if (
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {


                // ==============================
                // SECURITY
                // ==============================

                session_regenerate_id(true);


                // ==============================
                // STORE USER SESSION
                // ==============================

                $_SESSION["user_id"] =
                    $user["id"];

                $_SESSION["full_name"] =
                    $user["full_name"];

                $_SESSION["username"] =
                    $user["username"];

                $_SESSION["role"] =
                    $user["role"];


                // ==============================
                // ROLE-BASED REDIRECT
                // ==============================

                if (
                    $user["role"] === "admin"
                ) {

                    header(
                        "Location: admin/dashboard.php"
                    );

                    exit;

                }


                if (
                    $user["role"] === "resident"
                ) {

                    header(
                        "Location: dashboard.php"
                    );

                    exit;

                }


                // ==============================
                // UNKNOWN ROLE
                // ==============================

                session_unset();
                session_destroy();

                $message =
                    "Invalid account role.";

                $message_type = "error";


            } else {

                $message =
                    "Incorrect username or password.";

                $message_type = "error";
            }


        } else {

            $message =
                "Incorrect username or password.";

            $message_type = "error";
        }


        $stmt->close();
    }
}

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
        Login - RoadAlert
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<!-- ============================== -->
<!-- NAVIGATION -->
<!-- ============================== -->

<header class="navbar">

    <div class="logo">

        Road<span>Alert</span>

    </div>


    <div class="nav-links">

        <a href="index.php">
            Home
        </a>

        <a href="register.php">
            Register
        </a>

    </div>

</header>


<!-- ============================== -->
<!-- LOGIN FORM -->
<!-- ============================== -->

<div class="form-container">

    <div class="card">


        <h1>
            Login
        </h1>


        <p>
            Sign in to your RoadAlert
        </p>


        <br>


        <!-- ============================== -->
        <!-- MESSAGE -->
        <!-- ============================== -->

        <?php if ($message !== ""): ?>

            <div
                class="alert <?= htmlspecialchars($message_type) ?>"
            >

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- ============================== -->
        <!-- LOGIN FORM -->
        <!-- ============================== -->

        <form method="POST">


            <!-- USERNAME -->

            <label>
                Username
            </label>

            <input
                type="text"
                name="username"
                placeholder="Enter your username"
                autocomplete="username"
                required
            >


            <!-- PASSWORD -->

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
            >


            <!-- LOGIN BUTTON -->

            <button type="submit">
                Login
            </button>


        </form>


        <br>


        <p>

            Don't have an account?

            <a href="register.php">
                Create an account
            </a>

        </p>


    </div>

</div>


</body>

</html>