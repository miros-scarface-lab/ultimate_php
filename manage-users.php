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
   DELETE USER
========================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_id']) &&
    is_numeric($_POST['delete_id'])
) {

    csrfVerify();

    $delete_id = (int) $_POST['delete_id'];


    /* ------------------------------------------
       START TRANSACTION
    ------------------------------------------ */

    $conn->begin_transaction();

    try {


        /* ------------------------------------------
           1. DELETE USER NOTIFICATIONS
        ------------------------------------------ */

        $sql = "
            DELETE FROM notifications
            WHERE user_id = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Notification delete error: " .
                $conn->error
            );
        }

        $stmt->bind_param("i", $delete_id);

        $stmt->execute();

        $stmt->close();


        /* ------------------------------------------
           2. DELETE USER
        ------------------------------------------ */

        $sql = "
            DELETE FROM users
            WHERE user_id = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "User delete error: " .
                $conn->error
            );
        }

        $stmt->bind_param("i", $delete_id);

        $stmt->execute();

        $stmt->close();


        /* ------------------------------------------
           COMMIT
        ------------------------------------------ */

        $conn->commit();


        header("Location: manage-users.php?deleted=1");
        exit();


    } catch (Exception $e) {


        /* ------------------------------------------
           ROLLBACK
        ------------------------------------------ */

        $conn->rollback();


        die(
            "Unable to delete user. " .
            htmlspecialchars($e->getMessage())
        );

    }

}


/* ==========================================
   GET ALL USERS
========================================== */

$sql = "
    SELECT
        user_id,
        full_name,
        student_id,
        email,
        department,
        created_at
    FROM users
    ORDER BY user_id DESC
";


$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Users</title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #eef7f8,
                    #f8fafc
                );

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #1f2937;

        }


        /* =====================================
           MAIN CONTAINER
        ===================================== */

        .page-container {

            max-width: 1250px;

            margin: 45px auto;

            padding: 0 20px;

        }


        /* =====================================
           HEADER
        ===================================== */

        .page-header {

            background: #003844;

            color: white;

            padding: 28px 30px;

            border-radius: 18px 18px 0 0;

            box-shadow:
                0 8px 25px
                rgba(0, 56, 68, 0.15);

        }


        .page-header h1 {

            margin: 0;

            font-size: 28px;

            font-weight: 800;

        }


        .page-header p {

            margin: 7px 0 0;

            color: #cce9ec;

            font-size: 14px;

        }


        /* =====================================
           CONTENT CARD
        ===================================== */

        .content-card {

            background: white;

            padding: 25px;

            border-radius:
                0 0 18px 18px;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.07);

        }


        /* =====================================
           SUCCESS MESSAGE
        ===================================== */

        .success-message {

            background: #ecfdf5;

            color: #047857;

            border:
                1px solid #a7f3d0;

            padding: 12px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            font-weight: 600;

        }


        /* =====================================
           TABLE WRAPPER
        ===================================== */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

        }


        /* =====================================
           TABLE
        ===================================== */

        table {

            width: 100%;

            min-width: 900px;

            border-collapse: collapse;

        }


        thead {

            background: #003844;

        }


        th {

            color: white;

            padding: 15px;

            text-align: left;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

        }


        td {

            padding: 15px;

            border-bottom:
                1px solid #edf0f2;

            font-size: 14px;

            vertical-align: middle;

        }


        tbody tr {

            transition:
                background 0.2s ease;

        }


        tbody tr:hover {

            background: #f7fbfc;

        }


        tbody tr:last-child td {

            border-bottom: none;

        }


        /* =====================================
           USER ID
        ===================================== */

        .user-id {

            font-weight: 800;

            color: #003844;

        }


        /* =====================================
           NAME
        ===================================== */

        .user-name {

            font-weight: 700;

            color: #111827;

        }


        /* =====================================
           STUDENT ID
        ===================================== */

        .student-id {

            display: inline-block;

            background: #eef7f8;

            color: #006b7a;

            padding: 5px 9px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================
           EMAIL
        ===================================== */

        .email {

            color: #475569;

        }


        /* =====================================
           DEPARTMENT
        ===================================== */

        .department {

            display: inline-block;

            background: #f1f5f9;

            color: #475569;

            padding: 5px 9px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 600;

        }


        /* =====================================
           DATE
        ===================================== */

        .date {

            color: #64748b;

            font-size: 12px;

        }


        /* =====================================
           DELETE BUTTON
        ===================================== */

        .delete-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding: 9px 14px;

            background: #dc2626;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 12px;

            font-weight: 700;

            transition: all 0.2s ease;

        }


        .delete-btn:hover {

            background: #b91c1c;

            transform:
                translateY(-1px);

        }


        /* =====================================
           EMPTY
        ===================================== */

        .empty {

            text-align: center;

            padding: 45px;

            color: #64748b;

            font-size: 14px;

        }


        /* =====================================
           BACK BUTTON
        ===================================== */

        .bottom-actions {

            margin-top: 22px;

        }


        .back-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 18px;

            background: #003844;

            color: white;

            text-decoration: none;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;

            transition: all 0.2s ease;

        }


        .back-btn:hover {

            background: #005766;

            transform:
                translateY(-1px);

        }


        /* =====================================
           MOBILE
        ===================================== */

        @media (max-width: 700px) {

            .page-container {

                margin: 20px auto;

                padding: 0 12px;

            }


            .page-header {

                padding: 22px;

            }


            .page-header h1 {

                font-size: 22px;

            }


            .content-card {

                padding: 15px;

            }

        }

    </style>

</head>


<body>


<div class="page-container">


    <!-- =====================================
         PAGE HEADER
    ===================================== -->

    <div class="page-header">

        <h1>
            Manage Users
        </h1>

        <p>
            View and manage registered users.
        </p>

    </div>


    <!-- =====================================
         CONTENT
    ===================================== -->

    <div class="content-card">


        <?php if (
            isset($_GET['deleted']) &&
            $_GET['deleted'] == '1'
        ): ?>

            <div class="success-message">

                ✓ User deleted successfully.

            </div>

        <?php endif; ?>


        <!-- =================================
             USERS TABLE
        ================================== -->

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            User ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Student ID
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Department
                        </th>

                        <th>
                            Created At
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        $result &&
                        $result->num_rows > 0
                    ): ?>


                        <?php while (
                            $user =
                            $result->fetch_assoc()
                        ): ?>


                            <tr>


                                <!-- USER ID -->

                                <td>

                                    <span class="user-id">

                                        #
                                        <?php
                                        echo (int)
                                            $user['user_id'];
                                        ?>

                                    </span>

                                </td>


                                <!-- NAME -->

                                <td>

                                    <span class="user-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $user['full_name']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- STUDENT ID -->

                                <td>

                                    <span class="student-id">

                                        <?php
                                        echo htmlspecialchars(
                                            $user['student_id']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <span class="email">

                                        <?php
                                        echo htmlspecialchars(
                                            $user['email']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- DEPARTMENT -->

                                <td>

                                    <span class="department">

                                        <?php
                                        echo htmlspecialchars(
                                            $user['department']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <span class="date">

                                        <?php
                                        echo htmlspecialchars(
                                            $user['created_at']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="manage-users.php?delete_id=<?php echo (int) $user['user_id']; ?>"
                                        data-post="1"
                                        class="delete-btn"

                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this user? The user notifications will also be deleted.'
                                            );
                                        "
                                    >

                                        🗑 Delete

                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="empty"
                            >

                                No users available.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>


        <!-- =================================
             BACK
        ================================== -->

        <div class="bottom-actions">

            <a
                href="admin-dashboard.php"
                class="back-btn"
            >

                ← Back to Dashboard

            </a>

        </div>


    </div>


</div>


<?php echo csrfPostScript(); ?>
</body>

</html>


<?php

$conn->close();

?>