<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}


/* =========================
   RETURNED LOST ITEMS
   ========================= */

$lost_sql = "SELECT
                l.lost_id,
                l.item_name,
                l.category,
                u.full_name AS owner_name,
                l.status
             FROM lost_items l
             LEFT JOIN users u ON l.user_id = u.user_id
             WHERE l.status = 'Returned'
             ORDER BY l.lost_id DESC";

$lost_result = $conn->query($lost_sql);


/* =========================
   RETURNED FOUND ITEMS
   ========================= */

$found_sql = "SELECT
                f.found_id,
                f.item_name,
                f.category,
                u.full_name AS owner_name,
                f.status
              FROM found_items f
              LEFT JOIN users u ON f.user_id = u.user_id
              WHERE f.status = 'Returned'
              ORDER BY f.found_id DESC";

$found_result = $conn->query($found_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Returned Items</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h1>Returned Items</h1>

    <p>Items that have been successfully returned to their owners.</p>


    <!-- =========================
         RETURNED LOST ITEMS
         ========================= -->

    <h2>Returned Lost Items</h2>

    <table border="1" width="100%" cellpadding="10">

        <tr>
            <th>ID</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Owner Name</th>
            <th>Status</th>
        </tr>

        <?php

        if ($lost_result->num_rows > 0) {

            while ($row = $lost_result->fetch_assoc()) {

        ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($row['lost_id']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['item_name']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['category']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['owner_name'] ?? 'Unknown'); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['status']); ?>
            </td>

        </tr>

        <?php

            }

        } else {

        ?>

        <tr>

            <td colspan="5" align="center">
                No returned lost items available
            </td>

        </tr>

        <?php

        }

        ?>

    </table>


    <br><br>


    <!-- =========================
         RETURNED FOUND ITEMS
         ========================= -->

    <h2>Returned Found Items</h2>

    <table border="1" width="100%" cellpadding="10">

        <tr>
            <th>ID</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Owner Name</th>
            <th>Status</th>
        </tr>

        <?php

        if ($found_result->num_rows > 0) {

            while ($row = $found_result->fetch_assoc()) {

        ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($row['found_id']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['item_name']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['category']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['owner_name'] ?? 'Unknown'); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row['status']); ?>
            </td>

        </tr>

        <?php

            }

        } else {

        ?>

        <tr>

            <td colspan="5" align="center">
                No returned found items available
            </td>

        </tr>

        <?php

        }

        ?>

    </table>


    <br><br>


    <a href="admin-dashboard.php" class="btn">
        Back to Dashboard
    </a>

</div>

</body>

</html>