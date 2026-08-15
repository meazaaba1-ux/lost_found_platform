
<?php

include "backend/db.php";


// ==========================================
// GET LOST ITEMS
// ==========================================

$lost_sql = "
    SELECT
        id,
        user_id,
        item_name,
        category,
        description,
        lost_location AS location,
        lost_date AS item_date,
        contact_phone,
        image,
        status,
        'Lost' AS item_type
    FROM lost_items
    ORDER BY created_at DESC
";

$lost_result = mysqli_query($conn, $lost_sql);


// ==========================================
// GET FOUND ITEMS
// ==========================================

$found_sql = "
    SELECT
        id,
        user_id,
        item_name,
        category,
        description,
        found_location AS location,
        found_date AS item_date,
        contact_phone,
        image,
        status,
        'Found' AS item_type
    FROM found_items
    ORDER BY created_at DESC
";

$found_result = mysqli_query($conn, $found_sql);


// ==========================================
// COMBINE BOTH ITEMS
// ==========================================

$items = [];


// Add lost items

if ($lost_result) {

    while ($row = mysqli_fetch_assoc($lost_result)) {

        $items[] = $row;

    }

}


// Add found items

if ($found_result) {

    while ($row = mysqli_fetch_assoc($found_result)) {

        $items[] = $row;

    }

}


// ==========================================
// SORT BY DATE
// ==========================================

usort($items, function ($a, $b) {

    return strtotime($b["item_date"])
        - strtotime($a["item_date"]);

});

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
        View All Items - Lost & Found
    </title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<!-- ==========================================
     HEADER
========================================== -->

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

        <a href="lost.html">
            Report Lost
        </a>

        <a href="found.html">
            Report Found
        </a>

        <a href="backend/logout.php">
            Logout
        </a>

    </nav>

</header>



<!-- ==========================================
     MAIN
========================================== -->

<main>


<section class="items-section">


    <h1>
        🔍 View All Items
    </h1>


    <p class="items-subtitle">

        Browse reported lost and found items.

    </p>



    <!-- ======================================
         FILTER BUTTONS
    ======================================= -->

    <div class="item-filters">


        <button
            type="button"
            class="filter-btn"
            onclick="showAllItems()"
        >
            All Items
        </button>


        <button
            type="button"
            class="filter-btn"
            onclick="filterItems('Lost')"
        >
            🔴 Lost
        </button>


        <button
            type="button"
            class="filter-btn"
            onclick="filterItems('Found')"
        >
            🟢 Found
        </button>


    </div>



    <!-- ======================================
         SEARCH
    ======================================= -->

    <div class="item-search">

        <input
            type="text"
            id="searchInput"
            placeholder="Search item..."
            onkeyup="searchItems()"
        >

    </div>



    <!-- ======================================
         ITEMS GRID
    ======================================= -->

    <div
        class="items-grid"
        id="itemsGrid"
    >


        <?php if (count($items) > 0): ?>


            <?php foreach ($items as $item): ?>


                <?php

                $item_type =
                    $item["item_type"];

                $item_name =
                    htmlspecialchars(
                        $item["item_name"]
                    );

                $category =
                    htmlspecialchars(
                        $item["category"]
                    );

                $location =
                    htmlspecialchars(
                        $item["location"]
                    );

                $item_date =
                    htmlspecialchars(
                        $item["item_date"]
                    );

                $description =
                    htmlspecialchars(
                        $item["description"]
                    );

                $image =
                    $item["image"];

                ?>


                <!-- ==================================
                     ITEM CARD
                =================================== -->

                <div
                    class="item-card"
                    data-type="<?php
                        echo $item_type;
                    ?>"
                >


                    <!-- ==================================
                         IMAGE
                    =================================== -->

                    <div class="item-image">


                        <?php

                        if (
                            !empty($image) &&
                            file_exists($image)
                        ):

                        ?>


                            <img
                                src="<?php
                                    echo htmlspecialchars(
                                        $image
                                    );
                                ?>"
                                alt="<?php
                                    echo $item_name;
                                ?>"
                            >


                        <?php else: ?>


                            <div class="no-image">

                                📷 No Image

                            </div>


                        <?php endif; ?>


                    </div>



                    <!-- ==================================
                         STATUS
                    =================================== -->

                    <div
                        class="item-status
                        <?php
                            echo strtolower(
                                $item_type
                            );
                        ?>"
                    >


                        <?php if (
                            $item_type === "Lost"
                        ): ?>


                            🔴 LOST


                        <?php else: ?>


                            🟢 FOUND


                        <?php endif; ?>


                    </div>



                    <!-- ==================================
                         ITEM CONTENT
                    =================================== -->

                    <div class="item-content">


                        <h2>

                            <?php
                            echo $item_name;
                            ?>

                        </h2>



                        <!-- CATEGORY -->

                        <p>

                            <strong>
                                Category:
                            </strong>

                            <?php
                            echo $category;
                            ?>

                        </p>



                        <!-- GENERAL LOCATION -->

                        <p>

                            <strong>
                                General Location:
                            </strong>

                            <?php
                            echo $location;
                            ?>

                        </p>



                        <!-- DATE -->

                        <p>

                            <strong>
                                Date:
                            </strong>

                            <?php
                            echo $item_date;
                            ?>

                        </p>



                        <!-- ==================================
                             DESCRIPTION
                             ONLY SHORT DESCRIPTION
                        =================================== -->

                        <p class="description">

                            <?php

                            if (
                                strlen($description)
                                > 100
                            ) {

                                echo
                                    substr(
                                        $description,
                                        0,
                                        100
                                    )
                                    . "...";

                            } else {

                                echo $description;

                            }

                            ?>

                        </p>



                        <!-- ==================================
                             LOST ITEM
                             VIEW DETAILS ONLY
                        =================================== -->

                        <?php if (
                            $item_type === "Lost"
                        ): ?>


                            <a
                                class="details-btn"
                                href="item_details.php?type=lost&id=<?php
                                    echo $item["id"];
                                ?>"
                            >

                                👁️ View Details

                            </a>


                        <?php endif; ?>



                        <!-- ==================================
                             FOUND ITEM
                             CLAIM ONLY
                        =================================== -->

                        <?php if (
                            $item_type === "Found"
                        ): ?>


                            <a
                                class="claim-list-btn"
                                href="item_details.php?type=found&id=<?php
                                    echo $item["id"];
                                ?>" 
                                ```php
<a
    href="item_details.php?type=found&id=<?php echo $item["id"]; ?>"
    style="
        display:block;
        width:100%;
        box-sizing:border-box;
        padding:12px 20px;
        margin-top:15px;
        background: #1e3a8a;
        color:#ffffff;
        text-align:center;
        text-decoration:none;
        font-size:16px;
        font-weight:bold;
        border-radius:8px;
        cursor:pointer;
    "
 

                            >

                                🤝 Claim Item

                            </a>


                        <?php endif; ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- ==================================
                 NO ITEMS
            =================================== -->

            <div class="no-items">


                <h2>

                    No Items Found

                </h2>


                <p>

                    There are currently
                    no reported items.

                </p>


            </div>


        <?php endif; ?>


    </div>


</section>


</main>



<!-- ==========================================
     FOOTER
========================================== -->

<footer>

    <p>

        © 2026 Lost & Found Management System

    </p>

</footer>



<!-- ==========================================
     JAVASCRIPT
========================================== -->

<script>


// ==========================================
// SEARCH ITEMS
// ==========================================

function searchItems() {


    const searchInput =
        document.getElementById(
            "searchInput"
        );


    const search =
        searchInput.value
        .toLowerCase()
        .trim();


    const cards =
        document.querySelectorAll(
            ".item-card"
        );


    cards.forEach(function(card) {


        const text =
            card.innerText
            .toLowerCase();


        if (
            text.includes(search)
        ) {

            card.style.display =
                "block";

        } else {

            card.style.display =
                "none";

        }


    });

}



// ==========================================
// SHOW ALL
// ==========================================

function showAllItems() {


    const cards =
        document.querySelectorAll(
            ".item-card"
        );


    cards.forEach(function(card) {

        card.style.display =
            "block";

    });


}



// ==========================================
// FILTER LOST / FOUND
// ==========================================

function filterItems(type) {


    const cards =
        document.querySelectorAll(
            ".item-card"
        );


    cards.forEach(function(card) {


        if (
            card.dataset.type === type
        ) {

            card.style.display =
                "block";

        } else {

            card.style.display =
                "none";

        }


    });

}


</script>


</body>

</html>
```
