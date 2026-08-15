<?php

session_start();

include "db.php";


/* Check login */

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.html");
    exit();

}


/* Check request */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Invalid request.");

}


/* Get form data */

$user_id = $_SESSION["user_id"];

$item_name = trim($_POST["item_name"] ?? "");

$category = trim($_POST["category"] ?? "");

$description = trim($_POST["description"] ?? "");

$lost_location = trim($_POST["lost_location"] ?? "");

$lost_date = $_POST["lost_date"] ?? "";

$contact_phone = trim($_POST["contact_phone"] ?? "");


/* Check text fields */

if (
    empty($item_name) ||
    empty($category) ||
    empty($description) ||
    empty($lost_location) ||
    empty($lost_date) ||
    empty($contact_phone)
) {

    die("Please fill in all fields.");

}


/* Check image */

if (!isset($_FILES["image"]) ||
    $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

    die("Please upload an image.");

}


$image = $_FILES["image"];


/* Maximum size = 5MB */

$max_size = 5 * 1024 * 1024;

if ($image["size"] > $max_size) {

    die("Image is too large. Maximum size is 5MB.");

}


/* Check image type */

$allowed_types = [
    "image/jpeg",
    "image/png",
    "image/webp"
];


$image_type = mime_content_type($image["tmp_name"]);


if (!in_array($image_type, $allowed_types)) {

    die("Only JPG, PNG and WEBP images are allowed.");

}


/* Create upload folder */

$upload_dir = "../uploads/lost/";


if (!is_dir($upload_dir)) {

    mkdir($upload_dir, 0777, true);

}


/* Create unique filename */

$extension = pathinfo(
    $image["name"],
    PATHINFO_EXTENSION
);

$new_filename =
    uniqid("lost_", true)
    . "."
    . strtolower($extension);


/* Full file path */

$target_file =
    $upload_dir
    . $new_filename;


/* Move image */

if (!move_uploaded_file(
    $image["tmp_name"],
    $target_file
)) {

    die("Failed to upload image.");

}


/* Database image path */

$image_path =
    "uploads/lost/"
    . $new_filename;


/* Insert into database */

$sql = "INSERT INTO lost_items
(
    user_id,
    item_name,
    category,
    description,
    lost_location,
    lost_date,
    contact_phone,
    image
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die(
        "Database error: "
        . mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $stmt,
    "isssssss",
    $user_id,
    $item_name,
    $category,
    $description,
    $lost_location,
    $lost_date,
    $contact_phone,
    $image_path
);


/* Save */

if (mysqli_stmt_execute($stmt)) {

    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Lost Item Reported</title>

        <link
            rel="stylesheet"
            href="../css/style.css"
        >

    </head>


    <body>

        <main>

            <section class="form-section">

                <h1>
                    Lost Item Reported! ✅
                </h1>

                <p>
                    Your lost item has been
                    saved successfully.
                </p>


                <img
                    src="../<?php echo htmlspecialchars($image_path); ?>"
                    alt="Lost Item"
                    style="
                        width: 250px;
                        max-width: 100%;
                        border-radius: 10px;
                        margin: 20px 0;
                    "
                >


                <p>

                    <a href="../profile.php">
                        Back to Profile
                    </a>

                </p>


                <p>

                    <a href="../lost.html">
                        Report Another Lost Item
                    </a>

                </p>

            </section>

        </main>

    </body>

    </html>

    <?php

} else {

    /* Delete uploaded image
       if database insert fails */

    if (file_exists($target_file)) {

        unlink($target_file);

    }

    die(
        "Failed to save lost item: "
        . mysqli_error($conn)
    );

}


mysqli_stmt_close($stmt);

mysqli_close($conn);

?>