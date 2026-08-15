<?php

include "backend/db.php";


// =====================================================
// CALCULATE MATCH SCORE
// =====================================================

function calculateScore($lost, $found)
{
    $score = 0;


    // =================================================
    // 1. ITEM NAME = 30 POINTS
    // =================================================

    $lostName = strtolower(trim($lost["item_name"] ?? ""));
    $foundName = strtolower(trim($found["item_name"] ?? ""));

    if ($lostName !== "" && $foundName !== "") {

        if ($lostName === $foundName) {

            $score += 30;

        } elseif (
            strpos($lostName, $foundName) !== false ||
            strpos($foundName, $lostName) !== false
        ) {

            $score += 20;
        }
    }


    // =================================================
    // 2. CATEGORY = 20 POINTS
    // =================================================

    $lostCategory = strtolower(trim($lost["category"] ?? ""));
    $foundCategory = strtolower(trim($found["category"] ?? ""));

    if (
        $lostCategory !== "" &&
        $foundCategory !== "" &&
        $lostCategory === $foundCategory
    ) {

        $score += 20;
    }


    // =================================================
    // 3. BRAND = 15 POINTS
    // =================================================

    $lostBrand = strtolower(trim($lost["brand"] ?? ""));
    $foundBrand = strtolower(trim($found["brand"] ?? ""));

    if (
        $lostBrand !== "" &&
        $foundBrand !== "" &&
        $lostBrand === $foundBrand
    ) {

        $score += 15;
    }


    // =================================================
    // 4. COLOR = 15 POINTS
    // =================================================

    $lostColor = strtolower(trim($lost["color"] ?? ""));
    $foundColor = strtolower(trim($found["color"] ?? ""));

    if (
        $lostColor !== "" &&
        $foundColor !== "" &&
        $lostColor === $foundColor
    ) {

        $score += 15;
    }


    // =================================================
    // 5. DESCRIPTION = 15 POINTS
    // =================================================

    $lostDescription =
        strtolower(trim($lost["description"] ?? ""));

    $foundDescription =
        strtolower(trim($found["description"] ?? ""));


    if (
        $lostDescription !== "" &&
        $foundDescription !== ""
    ) {

        $lostWords = preg_split(
            '/\s+/',
            $lostDescription
        );

        $foundWords = preg_split(
            '/\s+/',
            $foundDescription
        );


        // Remove very short words
        $lostWords = array_filter(
            $lostWords,
            function ($word) {
                return strlen($word) > 2;
            }
        );

        $foundWords = array_filter(
            $foundWords,
            function ($word) {
                return strlen($word) > 2;
            }
        );


        $commonWords = array_intersect(
            $lostWords,
            $foundWords
        );


        $commonCount = count(
            array_unique($commonWords)
        );


        if ($commonCount >= 5) {

            $score += 15;

        } elseif ($commonCount >= 3) {

            $score += 10;

        } elseif ($commonCount >= 1) {

            $score += 5;
        }
    }


    // =================================================
    // 6. LOCATION = 5 POINTS
    // =================================================

    $lostLocation =
        strtolower(trim($lost["lost_location"] ?? ""));

    $foundLocation =
        strtolower(trim($found["found_location"] ?? ""));


    if (
        $lostLocation !== "" &&
        $foundLocation !== ""
    ) {

        if ($lostLocation === $foundLocation) {

            $score += 5;

        } elseif (
            strpos($lostLocation, $foundLocation) !== false ||
            strpos($foundLocation, $lostLocation) !== false
        ) {

            $score += 3;
        }
    }


    return $score;
}


// =====================================================
// GET LOST ITEMS
// =====================================================

$lostSql = "
    SELECT *
    FROM lost_items
    WHERE status = 'Lost'
    ORDER BY id DESC
";


$lostResult = mysqli_query(
    $conn,
    $lostSql
);


if (!$lostResult) {

    die(
        "Lost items error: " .
        mysqli_error($conn)
    );
}


// =====================================================
// GET FOUND ITEMS
// =====================================================

$foundSql = "
    SELECT *
    FROM found_items
    WHERE status = 'Found'
    ORDER BY id DESC
";


$foundResult = mysqli_query(
    $conn,
    $foundSql
);


if (!$foundResult) {

    die(
        "Found items error: " .
        mysqli_error($conn)
    );
}


// =====================================================
// STORE FOUND ITEMS
// =====================================================

$foundItems = [];


while (
    $found = mysqli_fetch_assoc(
        $foundResult
    )
) {

    $foundItems[] = $found;
}


// =====================================================
// FIND MATCHES
// =====================================================

$matches = [];


while (
    $lost = mysqli_fetch_assoc(
        $lostResult
    )
) {


    foreach (
        $foundItems as $found
    ) {


        // Calculate score

        $score = calculateScore(
            $lost,
            $found
        );


        // =================================================
        // ONLY MATCH IF SCORE >= 60
        // =================================================

        if ($score >= 60) {


            // =============================================
            // USER WHO REPORTED THE LOST ITEM
            // =============================================

            $userId =
                (int)$lost["user_id"];


            $lostId =
                (int)$lost["id"];


            $foundId =
                (int)$found["id"];


            // =============================================
            // NOTIFICATION MESSAGE
            // =============================================

            $message =
                "🔔 Possible Match Found! " .
                "Your lost item '" .
                $lost["item_name"] .
                "' may match a found item. " .
                "Match Score: " .
                $score .
                "%. Please login and review the item.";


            // =============================================
            // CHECK DUPLICATE NOTIFICATION
            // =============================================

            $checkNotification = mysqli_prepare(
                $conn,
                "SELECT id
                 FROM notifications
                 WHERE user_id = ?
                 AND lost_item_id = ?
                 AND found_item_id = ?"
            );


            if ($checkNotification) {

                mysqli_stmt_bind_param(
                    $checkNotification,
                    "iii",
                    $userId,
                    $lostId,
                    $foundId
                );


                mysqli_stmt_execute(
                    $checkNotification
                );


                mysqli_stmt_store_result(
                    $checkNotification
                );


                $notificationExists =
                    mysqli_stmt_num_rows(
                        $checkNotification
                    );


                // =========================================
                // CREATE NOTIFICATION
                // =========================================

                if ($notificationExists === 0) {


                    $insertNotification =
                        mysqli_prepare(
                            $conn,

                            "INSERT INTO notifications
                            (
                                user_id,
                                lost_item_id,
                                found_item_id,
                                message,
                                match_score
                            )
                            VALUES (?, ?, ?, ?, ?)"
                        );


                    if ($insertNotification) {


                        mysqli_stmt_bind_param(
                            $insertNotification,
                            "iiisi",
                            $userId,
                            $lostId,
                            $foundId,
                            $message,
                            $score
                        );


                        mysqli_stmt_execute(
                            $insertNotification
                        );


                        mysqli_stmt_close(
                            $insertNotification
                        );
                    }
                }


                mysqli_stmt_close(
                    $checkNotification
                );
            }


            // =============================================
            // STORE MATCH FOR DISPLAY
            // =============================================

            $matches[] = [

                "lost_id" =>
                    $lost["id"],

                "lost_name" =>
                    $lost["item_name"],

                "found_id" =>
                    $found["id"],

                "found_name" =>
                    $found["item_name"],

                "score" =>
                    $score,

                "lost_location" =>
                    $lost["lost_location"],

                "found_location" =>
                    $found["found_location"],

                "brand" =>
                    $found["brand"] ?? "",

                "color" =>
                    $found["color"] ?? "",

                "image" =>
                    $found["image"] ?? ""

            ];
        }
    }
}


// =====================================================
// SORT MATCHES BY SCORE
// =====================================================

usort(
    $matches,

    function ($a, $b) {

        return $b["score"] - $a["score"];
    }
);

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
        Possible Matches - Lost & Found
    </title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <style>

        .matching-container {

            max-width: 1000px;

            margin: 40px auto;

            padding: 20px;
        }


        .matching-title {

            text-align: center;

            margin-bottom: 10px;
        }


        .matching-subtitle {

            text-align: center;

            color: #666;

            margin-bottom: 30px;
        }


        .match-card {

            background: #ffffff;

            border-radius: 12px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 15px
                rgba(0, 0, 0, 0.10);

            border-left:
                5px solid #16a34a;
        }


        .match-card h2 {

            color: #15803d;

            margin-top: 0;
        }


        .match-score {

            display: inline-block;

            background: #dcfce7;

            color: #166534;

            padding: 8px 14px;

            border-radius: 20px;

            font-weight: bold;

            margin-bottom: 15px;
        }


        .match-info {

            line-height: 1.8;

            margin-bottom: 15px;
        }


        .match-image {

            width: 150px;

            height: 150px;

            object-fit: cover;

            border-radius: 10px;

            margin-bottom: 15px;
        }


        .review-claim-btn {

            display: inline-block;

            padding: 12px 20px;

            background: #16a34a;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;

            transition: 0.3s;
        }


        .review-claim-btn:hover {

            background: #15803d;

            transform: translateY(-2px);
        }


        .no-matches {

            text-align: center;

            background: #f8f9fa;

            padding: 40px;

            border-radius: 12px;
        }

    </style>

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
            All Items
        </a>


        <a href="profile.php">
            Profile
        </a>

    </nav>

</header>



<main>


    <section class="matching-container">


        <h1 class="matching-title">

            🔎 Possible Matches

        </h1>


        <p class="matching-subtitle">

            The system automatically compares
            lost and found item reports.

        </p>



        <?php if (count($matches) > 0): ?>


            <?php foreach ($matches as $match): ?>


                <div class="match-card">


                    <h2>

                        🔔 Possible Match Found

                    </h2>


                    <div class="match-score">

                        Match Score:

                        <?php
                        echo $match["score"];
                        ?>%

                    </div>



                    <?php

                    if (
                        !empty(
                            $match["image"]
                        )
                    ):

                    ?>

                        <img
                            src="images/<?php
                                echo htmlspecialchars(
                                    $match["image"]
                                );
                            ?>"
                            alt="Found Item"
                            class="match-image"
                        >

                    <?php endif; ?>



                    <div class="match-info">


                        <p>

                            <strong>
                                Lost Item:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $match["lost_name"]
                            );
                            ?>

                        </p>



                        <p>

                            <strong>
                                Found Item:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $match["found_name"]
                            );
                            ?>

                        </p>



                        <p>

                            <strong>
                                Brand:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $match["brand"]
                            );
                            ?>

                        </p>



                        <p>

                            <strong>
                                Color:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $match["color"]
                            );
                            ?>

                        </p>



                        <p>

                            <strong>
                                Lost Location:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $match["lost_location"]
                            );
                            ?>

                        </p>



                        <p>

                            <strong>
                                Found Location:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $match["found_location"]
                            );
                            ?>

                        </p>


                    </div>



                    <a
                        href="item_details.php?type=found&id=<?php echo (int)$match["found_id"]; ?>"
                        class="review-claim-btn"
                    >

                        🤝 Review & Claim

                    </a>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="no-matches">


                <h2>

                    No Possible Matches

                </h2>


                <p>

                    No lost and found items currently
                    have a matching score of 60% or higher.

                </p>


            </div>


        <?php endif; ?>


    </section>


</main>



<footer>

    <p>

        © 2026 Lost & Found Management System

    </p>

</footer>


</body>

</html>