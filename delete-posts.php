<?php

session_start();
include "db.php";


/* ==========================================
   ADMIN LOGIN CHECK
========================================== */

if (!isset($_SESSION['admin_username'])) {

    header("Location: admin-login.php");
    exit();

}


/* ==========================================
   DELETE LOST POST
========================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_lost']) &&
    is_numeric($_POST['delete_lost'])
) {

    csrfVerify();

    $lost_id = (int) $_POST['delete_lost'];

    /*
       First delete related notifications
    */
    $sql = "
        DELETE FROM notifications
        WHERE match_id IN (
            SELECT match_id
            FROM matches
            WHERE lost_id = ?
        )
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $lost_id);
        $stmt->execute();
        $stmt->close();

    }


    /*
       Delete related matches
    */
    $sql = "
        DELETE FROM matches
        WHERE lost_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $lost_id);
        $stmt->execute();
        $stmt->close();

    }


    /*
       Finally delete lost item
    */
    $sql = "
        DELETE FROM lost_items
        WHERE lost_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $lost_id);
        $stmt->execute();
        $stmt->close();

    }


    header("Location: delete-posts.php");
    exit();

}


/* ==========================================
   DELETE FOUND POST
========================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_found']) &&
    is_numeric($_POST['delete_found'])
) {

    csrfVerify();

    $found_id = (int) $_POST['delete_found'];


    /*
       First delete related notifications
    */
    $sql = "
        DELETE FROM notifications
        WHERE match_id IN (
            SELECT match_id
            FROM matches
            WHERE found_id = ?
        )
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $found_id);
        $stmt->execute();
        $stmt->close();

    }


    /*
       Delete related matches
    */
    $sql = "
        DELETE FROM matches
        WHERE found_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $found_id);
        $stmt->execute();
        $stmt->close();

    }


    /*
       Finally delete found item
    */
    $sql = "
        DELETE FROM found_items
        WHERE found_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $found_id);
        $stmt->execute();
        $stmt->close();

    }


    header("Location: delete-posts.php");
    exit();

}


/* ==========================================
   GET LOST POSTS
========================================== */

$lost_sql = "
    SELECT
        l.lost_id,
        l.item_name,
        l.category,
        l.status,
        u.full_name
    FROM lost_items l

    LEFT JOIN users u
        ON l.user_id = u.user_id

    ORDER BY l.lost_id DESC
";

$lost_result = $conn->query($lost_sql);


/* ==========================================
   GET FOUND POSTS
========================================== */

$found_sql = "
    SELECT
        f.found_id,
        f.item_name,
        f.category,
        f.status,
        u.full_name
    FROM found_items f

    LEFT JOIN users u
        ON f.user_id = u.user_id

    ORDER BY f.found_id DESC
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

    <title>Delete Posts</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <style>

        body {

            background: #f5f7f9;

        }

        .container {

            max-width: 1200px;

            margin: 40px auto;

            padding: 30px;

            background: #ffffff;

            border-radius: 16px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);

        }

        h1 {

            margin-bottom: 8px;

            color: #003844;

        }

        .subtitle {

            color: #6b7280;

            margin-bottom: 30px;

        }

        .section-title {

            margin-top: 30px;

            margin-bottom: 15px;

            color: #003844;

            font-size: 20px;

        }

        .table-wrapper {

            overflow-x: auto;

            margin-bottom: 25px;

        }

        table {

            width: 100%;

            border-collapse: collapse;

            background: white;

        }

        th {

            background: #003844;

            color: white;

            padding: 13px;

            text-align: left;

        }

        td {

            padding: 13px;

            border-bottom:
                1px solid #e5e7eb;

        }

        tr:hover {

            background: #f8fafc;

        }

        .lost-label {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 6px;

            background: #fee2e2;

            color: #b91c1c;

            font-size: 12px;

            font-weight: bold;

        }

        .found-label {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 6px;

            background: #dcfce7;

            color: #15803d;

            font-size: 12px;

            font-weight: bold;

        }

        .status {

            font-size: 13px;

            font-weight: 600;

        }

        .delete-btn {

            display: inline-block;

            padding: 8px 13px;

            background: #dc2626;

            color: white;

            text-decoration: none;

            border-radius: 7px;

            font-size: 13px;

            font-weight: bold;

        }

        .delete-btn:hover {

            background: #b91c1c;

        }

        .empty {

            text-align: center;

            color: #777;

            padding: 25px;

        }

        .back-btn {

            display: inline-block;

            margin-top: 15px;

            padding: 11px 20px;

            background: #003844;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;

        }

        .back-btn:hover {

            background: #005766;

        }

    </style>

</head>


<body>


<div class="container">


    <h1>
        Delete Posts
    </h1>


    <p class="subtitle">
        Remove unwanted or invalid lost and found posts.
    </p>


    <!-- ==========================================
         LOST POSTS
    =========================================== -->

    <h2 class="section-title">
        Lost Item Posts
    </h2>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>Post ID</th>

                    <th>Item Name</th>

                    <th>Category</th>

                    <th>Posted By</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

                <?php if (
                    $lost_result &&
                    $lost_result->num_rows > 0
                ): ?>


                    <?php while (
                        $post =
                        $lost_result->fetch_assoc()
                    ): ?>


                        <tr>

                            <td>
                                #
                                <?php
                                echo (int)
                                    $post['lost_id'];
                                ?>
                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $post['item_name']
                                    );
                                    ?>

                                </strong>

                                <br>

                                <span class="lost-label">
                                    LOST
                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $post['category']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $post['full_name']
                                    ??
                                    'Unknown User'
                                );
                                ?>

                            </td>


                            <td class="status">

                                <?php
                                echo htmlspecialchars(
                                    $post['status']
                                );
                                ?>

                            </td>


                            <td>

                                <a
                                    href="delete-posts.php?delete_lost=<?php echo (int) $post['lost_id']; ?>"
                                    data-post="1"
                                    class="delete-btn"

                                    onclick="
                                        return confirm(
                                            'Are you sure you want to delete this lost item post? Related matches and notifications will also be removed.'
                                        );
                                    "
                                >

                                    Delete

                                </a>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty"
                        >

                            No lost item posts available.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>



    <!-- ==========================================
         FOUND POSTS
    =========================================== -->

    <h2 class="section-title">

        Found Item Posts

    </h2>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>Post ID</th>

                    <th>Item Name</th>

                    <th>Category</th>

                    <th>Posted By</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

                <?php if (
                    $found_result &&
                    $found_result->num_rows > 0
                ): ?>


                    <?php while (
                        $post =
                        $found_result->fetch_assoc()
                    ): ?>


                        <tr>

                            <td>

                                #
                                <?php
                                echo (int)
                                    $post['found_id'];
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $post['item_name']
                                    );
                                    ?>

                                </strong>

                                <br>

                                <span class="found-label">

                                    FOUND

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $post['category']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $post['full_name']
                                    ??
                                    'Unknown User'
                                );
                                ?>

                            </td>


                            <td class="status">

                                <?php
                                echo htmlspecialchars(
                                    $post['status']
                                );
                                ?>

                            </td>


                            <td>

                                <a
                                    href="delete-posts.php?delete_found=<?php echo (int) $post['found_id']; ?>"
                                    data-post="1"
                                    class="delete-btn"

                                    onclick="
                                        return confirm(
                                            'Are you sure you want to delete this found item post? Related matches and notifications will also be removed.'
                                        );
                                    "
                                >

                                    Delete

                                </a>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty"
                        >

                            No found item posts available.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>



    <!-- ==========================================
         BACK
    =========================================== -->

    <a
        href="admin-dashboard.php"
        class="back-btn"
    >

        ← Back to Dashboard

    </a>


</div>


<?php echo csrfPostScript(); ?>
</body>

</html>


<?php

$conn->close();

?>