<?php

session_start();

include "backend/db.php";


/* Get URL data */

$type = $_GET["type"] ?? "";

$id = intval($_GET["id"] ?? 0);


/* Validate */

if (
    !in_array($type, ["lost", "found"]) ||
    $id <= 0
) {

    die("Invalid item.");

}


/* Choose table */

if ($type === "lost") {

    $table = "lost_items";

    $location_field = "lost_location";

    $date_field = "lost_date";

} else {

    $table = "found_items";

    $location_field = "found_location";

    $date_field = "found_date";

}


/* Get item */

$sql = "
    SELECT
        i.*,
        u.full_name,
        u.username,
        u.email
    FROM $table i
    INNER JOIN users u
        ON i.user_id = u.id
    WHERE i.id = ?
";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die(
        "Database error: "
        . mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) !== 1) {

    die("Item not found.");

}


$item =
    mysqli_fetch_assoc($result);

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
        <?php
        echo htmlspecialchars(
            $item["item_name"]
        );
        ?>
        - Lost & Found
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<header>

    <div class="logo">

        <h2>
            🔍 Lost & Found
        </h2>

    </div>


    <nav>

        <a href="index.html">
            Home
        </a>

        <a href="view_all_items.php">
            View All Items
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="backend/logout.php">
            Logout
        </a>

    </nav>

</header>



<main>


<section class="details-section">


    <h1>
        Item Details
    </h1>


    <!-- Item Image -->

    <div class="details-image">

        <?php if (!empty($item["image"])): ?>

            <img
                src="<?php
                    echo htmlspecialchars(
                        $item["image"]
                    );
                ?>"
                alt="Item Image"
            >

        <?php else: ?>

            <div class="no-image">
                📷 No Image
            </div>

        <?php endif; ?>

    </div>



    <!-- Status -->

    <div class="details-status">

        <?php if ($type === "lost"): ?>

            <span class="status-lost">
                🔴 LOST
            </span>

        <?php else: ?>

            <span class="status-found">
                🟢 FOUND
            </span>

        <?php endif; ?>

    </div>



    <!-- Main information -->

    <div class="details-content">


        <h2>

            <?php
            echo htmlspecialchars(
                $item["item_name"]
            );
            ?>

        </h2>


        <div class="detail-row">

            <strong>
                Category:
            </strong>

            <span>
                <?php
                echo htmlspecialchars(
                    $item["category"]
                );
                ?>
            </span>

        </div>


        <div class="detail-row">

            <strong>
                Description:
            </strong>

            <span>
                <?php
                echo nl2br(
                    htmlspecialchars(
                        $item["description"]
                    )
                );
                ?>
            </span>

        </div>


        <div class="detail-row">

            <strong>
                Location:
            </strong>

            <span>
                <?php
                echo htmlspecialchars(
                    $item[$location_field]
                );
                ?>
            </span>

        </div>


        <div class="detail-row">

            <strong>
                Date:
            </strong>

            <span>
                <?php
                echo htmlspecialchars(
                    $item[$date_field]
                );
                ?>
            </span>

        </div>


        <div class="detail-row">

            <strong>
                Contact Phone:
            </strong>

            <span>
                <?php
                echo htmlspecialchars(
                    $item["contact_phone"]
                );
                ?>
            </span>

        </div>


        <hr>


        <h3>
            Reported By
        </h3>


        <div class="detail-row">

            <strong>
                Name:
            </strong>

            <span>
                <?php
                echo htmlspecialchars(
                    $item["full_name"]
                );
                ?>
            </span>

        </div>


        <div class="detail-row">

            <strong>
                Username:
            </strong>

            <span>
                <?php
                echo htmlspecialchars(
                    $item["username"]
                );
                ?>
            </span>

        </div>


        <!-- CLAIM ONLY FOR FOUND -->

        <?php if ($type === "found"): ?>


            <div class="claim-box">

                <h2>
                    Is this your item?
                </h2>

                <?php if (
                    isset($_SESSION["user_id"])
                ): ?>


                    <?php

                    /*
                     Check if current user
                     already claimed this item
                    */

                    $check_sql = "
                        SELECT id
                        FROM claims
                        WHERE item_id = ?
                        AND item_type = 'found'
                        AND claimant_id = ?
                    ";


                    $check_stmt =
                        mysqli_prepare(
                            $conn,
                            $check_sql
                        );


                    mysqli_stmt_bind_param(
                        $check_stmt,
                        "ii",
                        $id,
                        $_SESSION["user_id"]
                    );


                    mysqli_stmt_execute(
                        $check_stmt
                    );


                    $check_result =
                        mysqli_stmt_get_result(
                            $check_stmt
                        );


                    ?>


                    <?php if (
                        mysqli_num_rows(
                            $check_result
                        ) > 0
                    ): ?>

                        <p class="already-claimed">

                            ✅ Claim already submitted.

                        </p>

                    <?php else: ?>


                        <form
                            action="backend/claim_item.php"
                            method="POST"
                        >


                            <input
                                type="hidden"
                                name="item_id"
                                value="<?php
                                    echo $id;
                                ?>"
                            >


                            <input
                                type="hidden"
                                name="item_type"
                                value="found"
                            >


                            <label>
                                Why do you believe
                                this is your item?
                            </label>


                            <textarea
                                name="claim_message"
                                rows="5"
                                placeholder="Describe identifying details..."
                                required
                            ></textarea>


                            <button
                                type="submit"
                            >
                                🤝 Claim This Item
                            </button>


                        </form>


                    <?php endif; ?>


                <?php else: ?>


                    <p>
                        Please sign in to claim
                        this item.
                    </p>


                    <a
                        href="login.html"
                        class="details-btn"
                    >
                        Sign In to Claim
                    </a>


                <?php endif; ?>


            </div>


        <?php endif; ?>


        <a
            href="view_all_items.php"
            class="back-btn"
        >
            ← Back to All Items
        </a>


    </div>


</section>


</main>


<footer>

    <p>
        © 2026 Lost & Found Management System
    </p>

</footer>


</body>

</html>

<?php

mysqli_stmt_close($stmt);

mysqli_close($conn);

?>