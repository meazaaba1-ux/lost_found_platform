<?php

session_start();


// ==========================================
// CHECK IF USER IS LOGGED IN
// ==========================================

if (!isset($_SESSION["user_id"])) {

    header("Location: login.html");

    exit();
}


// ==========================================
// DATABASE CONNECTION
// ==========================================

include "backend/db.php";


// ==========================================
// GET CURRENT USER ID
// ==========================================

$userId = (int)$_SESSION["user_id"];


// ==========================================
// GET NOTIFICATIONS
// ==========================================

$notifications = [];

$notificationSql = "
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $notificationSql
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    while ($row = mysqli_fetch_assoc($result)) {

        $notifications[] = $row;
    }


    mysqli_stmt_close($stmt);
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
        My Profile - Lost & Found
    </title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <style>

        /* =====================================
           NOTIFICATIONS SECTION
        ===================================== */

        .notifications-section {

            margin-top: 30px;

            padding: 20px;

            background: #f8f9fa;

            border-radius: 12px;
        }


        .notifications-section h2 {

            margin-bottom: 20px;

        }


        .notification-card {

            background: white;

            padding: 20px;

            margin-bottom: 15px;

            border-radius: 10px;

            border-left: 5px solid #16a34a;

            box-shadow:
                0 3px 10px
                rgba(0, 0, 0, 0.08);
        }


        .notification-message {

            font-size: 16px;

            line-height: 1.6;

            margin-bottom: 12px;
        }


        .notification-score {

            margin-bottom: 15px;

            font-size: 15px;
        }


        .notification-score strong {

            color: #16a34a;

            font-size: 18px;
        }


        .notification-claim-btn {

            display: inline-block;

            background: #16a34a;

            color: white;

            text-decoration: none;

            padding: 10px 16px;

            border-radius: 8px;

            font-weight: bold;

            transition: 0.3s;
        }


        .notification-claim-btn:hover {

            background: #15803d;

            transform: translateY(-2px);
        }


        .notification-date {

            display: block;

            margin-top: 12px;

            color: #777;

            font-size: 13px;
        }


        .no-notifications {

            background: white;

            padding: 25px;

            text-align: center;

            border-radius: 10px;

            color: #666;
        }


        .notification-badge {

            display: inline-block;

            background: #dc2626;

            color: white;

            padding: 4px 9px;

            border-radius: 20px;

            font-size: 12px;

            margin-left: 5px;
        }

    </style>

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


    <section class="form-section">


        <!-- ======================================
             PROFILE
        ======================================= -->

        <h1>
            My Profile 👤
        </h1>


        <p>

            Welcome to your Lost & Found account

        </p>



        <div class="profile-info">


            <p>

                <strong>
                    Full Name:
                </strong>


                <?php

                echo htmlspecialchars(
                    $_SESSION["full_name"] ?? ""
                );

                ?>

            </p>



            <p>

                <strong>
                    Username:
                </strong>


                <?php

                echo htmlspecialchars(
                    $_SESSION["username"] ?? ""
                );

                ?>

            </p>



            <p>

                <strong>
                    Email:
                </strong>


                <?php

                echo htmlspecialchars(
                    $_SESSION["email"] ?? ""
                );

                ?>

            </p>



            <p>

                <strong>
                    Phone:
                </strong>


                <?php

                echo htmlspecialchars(
                    $_SESSION["phone"] ?? ""
                );

                ?>

            </p>


        </div>



        <hr>



        <!-- ======================================
             NOTIFICATIONS
        ======================================= -->

        <section class="notifications-section">


            <h2>

                🔔 Notifications


                <?php

                if (count($notifications) > 0) {

                    echo '<span class="notification-badge">'
                        . count($notifications)
                        . '</span>';

                }

                ?>

            </h2>



            <?php if (count($notifications) > 0): ?>


                <?php foreach (
                    $notifications as $notification
                ): ?>


                    <div class="notification-card">


                        <div class="notification-message">

                            <?php

                            echo htmlspecialchars(
                                $notification["message"]
                            );

                            ?>

                        </div>



                        <div class="notification-score">

                            Match Score:

                            <strong>

                                <?php

                                echo (int)
                                    $notification["match_score"];

                                ?>%

                            </strong>

                        </div>



                        <a
                            href="item_details.php?type=found&id=<?php echo (int)$notification["found_item_id"]; ?>"
                            class="notification-claim-btn"
                        >

                            🤝 Review & Claim

                        </a>



                        <span class="notification-date">

                            <?php

                            echo htmlspecialchars(
                                $notification["created_at"]
                            );

                            ?>

                        </span>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="no-notifications">

                    <p>

                        🔕 No notifications yet.

                    </p>

                    <p>

                        When the system finds a possible
                        match for your lost item,
                        the notification will appear here.

                    </p>

                </div>


            <?php endif; ?>


        </section>



        <!-- ======================================
             ACTION BUTTONS
        ======================================= -->

        <h2 style="margin-top: 25px;">

            What would you like to do?

        </h2>



        <div
            class="hero-buttons"
            style="margin-top: 20px;"
        >


            <a href="lost.html">

                🔴 Report Lost Item

            </a>



            <a href="found.html">

                🟢 Report Found Item

            </a>


        </div>



        <p style="margin-top: 25px;">


            <a href="backend/logout.php">

                Sign Out

            </a>


        </p>


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


</body>

</html>