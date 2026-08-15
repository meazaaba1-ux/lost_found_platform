<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];


    // Check password confirmation
    if ($password !== $confirm_password) {

        die("Passwords do not match.");

    }


    // Check if username already exists
    $check_username = "SELECT id FROM users WHERE username = ?";

    $stmt = mysqli_prepare($conn, $check_username);

    mysqli_stmt_bind_param($stmt, "s", $username);

    mysqli_stmt_execute($stmt);

    mysqli_stmt_store_result($stmt);


    if (mysqli_stmt_num_rows($stmt) > 0) {

        die("Username already exists. Please choose another username.");

    }

    mysqli_stmt_close($stmt);


    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = ?";

    $stmt = mysqli_prepare($conn, $check_email);

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    mysqli_stmt_store_result($stmt);


    if (mysqli_stmt_num_rows($stmt) > 0) {

        die("Email already exists. Please use another email.");

    }

    mysqli_stmt_close($stmt);


    // Hash password
    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    // Insert user into database
    $sql = "INSERT INTO users
            (username, full_name, email, phone, password)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $username,
        $full_name,
        $email,
        $phone,
        $hashed_password
    );


    if (mysqli_stmt_execute($stmt)) {

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Account Created</title>
        </head>

        <body>

            <h1>Account Created Successfully! ✅</h1>

            <p>Your account has been created.</p>

            <a href='../login.html'>
                Go to Sign In
            </a>

        </body>
        </html>
        ";

    } else {

        echo "Registration failed: "
             . mysqli_error($conn);

    }


    mysqli_stmt_close($stmt);
    mysqli_close($conn);

}

?>