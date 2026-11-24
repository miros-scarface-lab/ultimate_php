<?php

session_start();
include "db.php";


/* ==============================
   ADMIN LOGIN CHECK
   ============================== */

if (!isset($_SESSION['admin_username'])) {

    header("Location: admin-login.php");
    exit();

}


/* ==============================
   GET PENDING LOST ITEMS
   ============================== */

$lost_sql = "
    SELECT
        lost_id,
        item_name
    FROM lost_items
    WHERE status = 'Pending'
    ORDER BY lost_id DESC
";

$lost_result = $conn->query($lost_sql);


/* ==============================
   GET PENDING FOUND ITEMS
   ============================== */

$found_sql = "
    SELECT
        found_id,
        item_name
    FROM found_items
    WHERE status = 'Pending'
    ORDER BY found_id DESC
";

$found_result = $conn->query($found_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<div class="container">


    <!-- ==========================
         ADMIN DASHBOARD HEADER
         ========================== -->

    <h1>Admin Dashboard</h1>


    <p>
        Welcome,
        <?php
        echo htmlspecialchars(
            $_SESSION['admin_username']
        );
        ?>!
    </p>


    <!-- ==========================
         NOTIFICATION SECTION
         ========================== -->

    <h2>Notifications</h2>


    <?php

    /* ==============================
       PENDING LOST ITEMS
       ============================== */

    if ($lost_result->num_rows > 0) {

        while ($row = $lost_result->fetch_assoc()) {

    ?>

        <div>

            <p>

                🔔
                <b>New Lost Item Post</b>

                <br>

                A new lost item

                <b>
                    <?php
                    echo htmlspecialchars(
                        $row['item_name']
                    );
                    ?>
                </b>

                is waiting for approval.

            </p>

        </div>

    <?php

        }

    }


    /* ==============================
       PENDING FOUND ITEMS
       ============================== */

    if ($found_result->num_rows > 0) {

        while ($row = $found_result->fetch_assoc()) {

    ?>

        <div>

            <p>

                🔔
                <b>New Found Item Post</b>

                <br>

                A new found item

                <b>
                    <?php
                    echo htmlspecialchars(
                        $row['item_name']
                    );
                    ?>
                </b>

                is waiting for approval.

            </p>

        </div>

    <?php

        }

    }


    /* ==============================
       NO PENDING POSTS
       ============================== */

    if (
        $lost_result->num_rows == 0 &&
        $found_result->num_rows == 0
    ) {

    ?>

        <p>No New Notifications</p>

    <?php

    }

    ?>


    <br>
    <br>


    <!-- ==========================
         DASHBOARD BUTTONS
         ========================== -->

    <div class="dashboard-buttons">


        <!-- VIEW ALL LOST ITEMS -->

        <a
            href="view-lost-items.php"
            class="btn"
        >
            View All Lost Items
        </a>


        <!-- VIEW ALL FOUND ITEMS -->

        <a
            href="view-found-items.php"
            class="btn"
        >
            View All Found Items
        </a>


        <!-- PENDING POSTS -->

        <a
            href="pending-posts.php"
            class="btn"
        >
            Pending Posts
        </a>


        <!-- RETURNED ITEMS -->

        <a
            href="returned-items.php"
            class="btn"
        >
            Returned Items
        </a>


        <!-- MANAGE USERS -->

        <a
            href="manage-users.php"
            class="btn"
        >
            Manage Users
        </a>


        <!-- DELETE POSTS -->

        <a
            href="delete-posts.php"
            class="btn"
        >
            Delete Posts
        </a>


        <!-- ==========================
             APPROVE CLAIM
             SAME BUTTON DESIGN
             ========================== -->

        <a
            href="admin-approved-claims.php"
            class="btn"
        >
            Approve Claim
        </a>


        <!-- LOGOUT -->

        <a
            href="admin-logout.php"
        >
            Logout
        </a>


    </div>


</div>


<script src="js/script.js"></script>


</body>

</html>