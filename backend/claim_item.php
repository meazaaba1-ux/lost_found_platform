<?php

session_start();

include "db.php";


/* User must login */

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.html");

    exit();

}


/* Only POST */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Invalid request.");

}


$claimant_id =
    $_SESSION["user_id"];

$item_id =
    intval($_POST["item_id"] ?? 0);

$item_type =
    $_POST["item_type"] ?? "";

$claim_message =
    trim(
        $_POST["claim_message"] ?? ""
    );


/* Validate */

if (
    $item_id <= 0 ||
    $item_type !== "found" ||
    empty($claim_message)
) {

    die("Invalid claim information.");

}


/* Check found item exists */

$check_item_sql = "
    SELECT id
    FROM found_items
    WHERE id = ?
";


$check_item_stmt =
    mysqli_prepare(
        $conn,
        $check_item_sql
    );


mysqli_stmt_bind_param(
    $check_item_stmt,
    "i",
    $item_id
);


mysqli_stmt_execute(
    $check_item_stmt
);


$item_result =
    mysqli_stmt_get_result(
        $check_item_stmt
    );


if (
    mysqli_num_rows(
        $item_result
    ) !== 1
) {

    die("Found item does not exist.");

}


/* Prevent duplicate claim */

$duplicate_sql = "
    SELECT id
    FROM claims
    WHERE item_id = ?
    AND item_type = 'found'
    AND claimant_id = ?
";


$duplicate_stmt =
    mysqli_prepare(
        $conn,
        $duplicate_sql
    );


mysqli_stmt_bind_param(
    $duplicate_stmt,
    "ii",
    $item_id,
    $claimant_id
);


mysqli_stmt_execute(
    $duplicate_stmt
);


$duplicate_result =
    mysqli_stmt_get_result(
        $duplicate_stmt
    );


if (
    mysqli_num_rows(
        $duplicate_result
    ) > 0
) {

    die("You already submitted a claim for this item.");

}


/* Insert claim */

$sql = "
    INSERT INTO claims
    (
        item_id,
        item_type,
        claimant_id,
        claim_message
    )
    VALUES (?, 'found', ?, ?)
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    die(
        "Database error: "
        . mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $stmt,
    "iis",
    $item_id,
    $claimant_id,
    $claim_message
);


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

    <title>
        Claim Submitted
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>


<body>


<main>

<section class="form-section">


    <h1>
        Claim Submitted Successfully! ✅
    </h1>


    <p>
        Your claim has been sent successfully.
    </p>


    <p>
        The item owner can review your claim.
    </p>


    <a
        href="../view_all_items.php"
        class="details-btn"
    >
        View All Items
    </a>


</section>

</main>


</body>

</html>

<?php

} else {

    die(
        "Failed to submit claim: "
        . mysqli_error($conn)
    );

}


mysqli_stmt_close($stmt);

mysqli_close($conn);

?>