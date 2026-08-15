<?php

include "backend/db.php";


// ==========================================
// FUNCTION: CALCULATE MATCH SCORE
// ==========================================

function calculateScore($lost, $found)
{
    $score = 0;


    // -------------------------------
    // 1. ITEM NAME = 30 POINTS
    // -------------------------------

    $lostName =
        strtolower(trim($lost["item_name"]));

    $foundName =
        strtolower(trim($found["item_name"]));


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


    // -------------------------------
    // 2. CATEGORY = 20 POINTS
    // -------------------------------

    $lostCategory =
        strtolower(trim($lost["category"]));

    $foundCategory =
        strtolower(trim($found["category"]));


    if (
        $lostCategory !== "" &&
        $foundCategory !== "" &&
        $lostCategory === $foundCategory
    ) {

        $score += 20;

    }


    // -------------------------------
    // 3. BRAND = 15 POINTS
    // -------------------------------

    if (
        isset($lost["brand"]) &&
        isset($found["brand"])
    ) {

        $lostBrand =
            strtolower(trim($lost["brand"]));

        $foundBrand =
            strtolower(trim($found["brand"]));


        if (
            $lostBrand !== "" &&
            $foundBrand !== "" &&
            $lostBrand === $foundBrand
        ) {

            $score += 15;

        }

    }


    // -------------------------------
    // 4. COLOR = 15 POINTS
    // -------------------------------

    if (
        isset($lost["color"]) &&
        isset($found["color"])
    ) {

        $lostColor =
            strtolower(trim($lost["color"]));

        $foundColor =
            strtolower(trim($found["color"]));


        if (
            $lostColor !== "" &&
            $foundColor !== "" &&
            $lostColor === $foundColor
        ) {

            $score += 15;

        }

    }


    // -------------------------------
    // 5. DESCRIPTION = 15 POINTS
    // -------------------------------

    $lostDescription =
        strtolower(trim($lost["description"]));

    $foundDescription =
        strtolower(trim($found["description"]));


    if (
        $lostDescription !== "" &&
        $foundDescription !== ""
    ) {

        $lostWords =
            preg_split(
                '/\s+/',
                $lostDescription
            );

        $foundWords =
            preg_split(
                '/\s+/',
                $foundDescription
            );


        $commonWords =
            array_intersect(
                $lostWords,
                $foundWords
            );


        if (count($commonWords) >= 5) {

            $score += 15;

        } elseif (count($commonWords) >= 3) {

            $score += 10;

        } elseif (count($commonWords) >= 1) {

            $score += 5;

        }

    }


    // -------------------------------
    // 6. LOCATION = 5 POINTS
    // -------------------------------

    $lostLocation =
        strtolower(trim($lost["lost_location"]));

    $foundLocation =
        strtolower(trim($found["found_location"]));


    if (
        $lostLocation !== "" &&
        $foundLocation !== ""
    ) {

        if ($lostLocation === $foundLocation) {

            $score += 5;

        } elseif (
            strpos(
                $lostLocation,
                $foundLocation
            ) !== false ||
            strpos(
                $foundLocation,
                $lostLocation
            ) !== false
        ) {

            $score += 3;

        }

    }


    return $score;
}


// ==========================================
// GET LOST ITEMS
// ==========================================

$lostSql = "
    SELECT *
    FROM lost_items
    WHERE status = 'Lost'
    ORDER BY id DESC
";


$lostResult =
    mysqli_query(
        $conn,
        $lostSql
    );


// ==========================================
// GET FOUND ITEMS
// ==========================================

$foundSql = "
    SELECT *
    FROM found_items
    WHERE status = 'Found'
    ORDER BY id DESC
";


$foundResult =
    mysqli_query(
        $conn,
        $foundSql
    );


if (!$lostResult) {

    die(
        "Lost items error: "
        . mysqli_error($conn)
    );

}


if (!$foundResult) {

    die(
        "Found items error: "
        . mysqli_error($conn)
    );

}


// ==========================================
// STORE FOUND ITEMS
// ==========================================

$foundItems = [];


while (
    $found = mysqli_fetch_assoc(
        $foundResult
    )
) {

    $foundItems[] = $found;

}


// ==========================================
// FIND MATCHES
// ==========================================

$matches = [];


while (
    $lost = mysqli_fetch_assoc(
        $lostResult
    )
) {


    foreach (
        $foundItems as $found
    ) {


        $score =
            calculateScore(
                $lost,
                $found
            );


        // Only show matches >= 60%

        if ($score >= 60) {


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
                    $found["found_location"]

            ];

        }

    }

}


// ==========================================
// SORT BY SCORE
// ==========================================

usort(
    $matches,
    function ($a, $b) {

        return
            $b["score"]
            -
            $a["score"];

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
        Possible Matches
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
            All Items
        </a>

        <a href="profile.php">
            Profile
        </a>

    </nav>

</header>


<main>


<section class="items-section">


    <h1>
        🔎 Possible Matches
    </h1>


    <p class="items-subtitle">

        The system compares lost and found
        reports automatically.

    </p>


    <?php if (count($matches) > 0): ?>


        <?php foreach ($matches as $match): ?>


            <div class="match-card">


                <h2>

                    🔔 Possible Match Found

                </h2>


                <div class="match-score">

                    Match Score:

                    <strong>

                        <?php
                        echo $match["score"];
                        ?>%

                    </strong>

                </div>


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
                        Location:
                    </strong>

                    <?php
                    echo htmlspecialchars(
                        $match["lost_location"]
                    );
                    ?>

                </p>


                <a
                    href="item_details.php?type=found&id=<?php echo $match["found_id"]; ?>"
                    class="claim-list-btn"
                >

                    🤝 Review & Claim

                </a>


            </div>


        <?php endforeach; ?>


    <?php else: ?>


        <div class="no-items">

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