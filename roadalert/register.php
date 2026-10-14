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

        // Check if username or email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );

        $check->bind_param(
            "ss",
            $username,
            $email
        );

        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Username or email is already registered.";
            $message_type = "error";

        } else {

            // Securely hash the password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert new resident
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account - RoadAlert + FuelTrack</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<header class="navbar">

    <div class="logo">
        Road<span>Alert</span> + FuelTrack
    </div>

    <div class="nav-links">

        <a href="index.php">Home</a>

        <a href="login.php">Login</a>

    </div>

</header>

<div class="form-container">

    <div class="card">

        <h1>Create Account</h1>

        <p>
            Register as a RoadAlert + FuelTrack resident.
        </p>

        <br>

        <?php if ($message !== ""): ?>

            <div class="alert <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label>
                Full Name
            </label>

            <input
                type="text"
                name="full_name"
                placeholder="Enter your full name"
                required
            >

            <label>
                Date of Birth
            </label>

            <input
                type="date"
                name="date_of_birth"
                required
            >

            <label>
                Sex
            </label>

            <select name="sex" required>

                <option value="">
                    Select Sex
                </option>

                <option value="Male">
                    Male
                </option>

                <option value="Female">
                    Female
                </option>

            </select>

            <label>
                Civil Status
            </label>

            <select name="civil_status" required>

                <option value="">
                    Select Civil Status
                </option>

                <option value="Single">
                    Single
                </option>

                <option value="Married">
                    Married
                </option>

                <option value="Widowed">
                    Widowed
                </option>

                <option value="Separated">
                    Separated
                </option>

            </select>

            <label>
                Complete Address
            </label>

            <textarea
                name="address"
                placeholder="Enter your complete address"
                rows="4"
                required
            ></textarea>

            <label>
                Contact Number
            </label>

            <input
                type="text"
                name="contact_number"
                placeholder="09XXXXXXXXX"
                required
            >

            <label>
                Email Address
            </label>

            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <label>
                Username
            </label>

            <input
                type="text"
                name="username"
                placeholder="Create a username"
                required
            >

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Create a password"
                required
            >

            <label>
                Confirm Password
            </label>

            <input
                type="password"
                name="confirm_password"
                placeholder="Confirm your password"
                required
            >

            <button type="submit">
                Create Account
            </button>

        </form>

        <br>

        <p>
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

    </div>

</div>

</body>

</html>