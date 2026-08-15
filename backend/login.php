<?php

session_start();

include "db.php";


/* Check request */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Invalid request.");

}


/* Get form data */

$username = trim($_POST["username"] ?? "");

$password = $_POST["password"] ?? "";


/* Check empty fields */

if (empty($username) || empty($password)) {

    die("Please enter username and password.");

}


/* Find user */

$sql = "SELECT * FROM users WHERE username = ?";

$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die("Database error: " . mysqli_error($conn));

}


mysqli_stmt_bind_param(
    $stmt,
    "s",
    $username
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


/* Check username */

if (mysqli_num_rows($result) == 1) {

    $user = mysqli_fetch_assoc($result);


    /* Check password */

    if (password_verify(
        $password,
        $user["password"]
    )) {


        /* Create session */

        $_SESSION["user_id"] = $user["id"];

        $_SESSION["username"] = $user["username"];

        $_SESSION["full_name"] = $user["full_name"];

        $_SESSION["email"] = $user["email"];

        $_SESSION["phone"] = $user["phone"];


        /* Go to profile */

        header("Location: ../profile.php");

        exit();


    } else {

        die("Incorrect password.");

    }


} else {

    die("Username not found.");

}


mysqli_stmt_close($stmt);

mysqli_close($conn);

?>