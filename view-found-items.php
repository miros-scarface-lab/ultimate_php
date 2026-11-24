<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}


/* =====================================================
   DETAILS VIEW
===================================================== */

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $found_id = (int) $_GET['id'];

    $sql = "
        SELECT
            f.*,
            u.full_name,
            u.student_id,
            u.email,
            u.department
        FROM found_items f
        LEFT JOIN users u
            ON f.user_id = u.user_id
        WHERE f.found_id = ?
        AND f.status = 'Approved'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $found_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo "Found item not found.";
        exit();
    }

    $item = $result->fetch_assoc();

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Found Item Details</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            color: #1f2937;
        }


        .details-page {

            max-width: 1100px;

            margin: 40px auto;

            padding: 0 20px 40px;
        }


        .page-header {

            text-align: center;

            margin-bottom: 25px;
        }


        .page-header h1 {

            margin: 0;

            font-size: 30px;

            color: #111827;
        }


        .page-header p {

            margin-top: 8px;

            color: #6b7280;
        }


        .information-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 25px;
        }


        .info-card {

            background: white;

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.07);
        }


        .info-card h2 {

            margin-top: 0;

            margin-bottom: 22px;

            font-size: 20px;

            color: #111827;

            border-bottom: 1px solid #e5e7eb;

            padding-bottom: 12px;
        }


        .info-item {

            padding: 12px 0;

            border-bottom: 1px solid #f0f0f0;
        }


        .info-item:last-child {

            border-bottom: none;
        }


        .info-label {

            display: block;

            font-size: 13px;

            font-weight: bold;

            color: #6b7280;

            margin-bottom: 5px;
        }


        .info-value {

            font-size: 15px;

            color: #111827;

            line-height: 1.5;

            word-break: break-word;
        }


        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: bold;

            background: #dcfce7;

            color: #166534;
        }


        .back-button {

            display: inline-block;

            margin-top: 25px;

            padding: 11px 20px;

            border-radius: 10px;

            background: #111827;

            color: white;

            text-decoration: none;

            transition: 0.2s;
        }


        .back-button:hover {

            background: #374151;
        }


        @media (max-width: 768px) {

            .information-grid {

                grid-template-columns: 1fr;
            }


            .page-header h1 {

                font-size: 24px;
            }


            .details-page {

                margin-top: 25px;
            }

        }

    </style>

</head>


<body>


<div class="details-page">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>
            Found Item Details
        </h1>

        <p>
            Detailed information about this found item
        </p>

    </div>



    <!-- INFORMATION GRID -->

    <div class="information-grid">


        <!-- ================================================
             POSTER INFORMATION
        ================================================= -->

        <div class="info-card">

            <h2>
                Poster Information
            </h2>


            <!-- FULL NAME -->

            <div class="info-item">

                <span class="info-label">
                    Full Name
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['full_name'] ?? 'N/A'
                    );

                    ?>

                </div>

            </div>



            <!-- STUDENT ID -->

            <div class="info-item">

                <span class="info-label">
                    Student ID
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['student_id'] ?? 'N/A'
                    );

                    ?>

                </div>

            </div>



            <!-- EMAIL -->

            <div class="info-item">

                <span class="info-label">
                    Email Address
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['email'] ?? 'N/A'
                    );

                    ?>

                </div>

            </div>



            <!-- DEPARTMENT -->

            <div class="info-item">

                <span class="info-label">
                    Department
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['department'] ?? 'N/A'
                    );

                    ?>

                </div>

            </div>

        </div>



        <!-- ================================================
             FOUND ITEM INFORMATION
        ================================================= -->

        <div class="info-card">

            <h2>
                Found Item Information
            </h2>


            <!-- FOUND ITEM ID -->

            <div class="info-item">

                <span class="info-label">
                    Found Item ID
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['found_id']
                    );

                    ?>

                </div>

            </div>



            <!-- ITEM NAME -->

            <div class="info-item">

                <span class="info-label">
                    Item Name
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['item_name']
                    );

                    ?>

                </div>

            </div>



            <!-- CATEGORY -->

            <div class="info-item">

                <span class="info-label">
                    Category
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['category']
                    );

                    ?>

                </div>

            </div>



            <!-- DESCRIPTION -->

            <div class="info-item">

                <span class="info-label">
                    Description
                </span>

                <div class="info-value">

                    <?php

                    echo nl2br(
                        htmlspecialchars(
                            $item['description']
                        )
                    );

                    ?>

                </div>

            </div>



            <!-- FOUND DATE -->

            <div class="info-item">

                <span class="info-label">
                    Found Date
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['found_date']
                    );

                    ?>

                </div>

            </div>



            <!-- FOUND LOCATION -->

            <div class="info-item">

                <span class="info-label">
                    Found Location
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['found_location']
                    );

                    ?>

                </div>

            </div>



            <!-- FINDER CONTACT INFORMATION -->

            <div class="info-item">

                <span class="info-label">
                    Finder Contact Information
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['finder_contact_information'] ?? 'N/A'
                    );

                    ?>

                </div>

            </div>



            <!-- STATUS -->

            <div class="info-item">

                <span class="info-label">
                    Status
                </span>

                <div class="info-value">

                    <span class="status">

                        <?php

                        echo htmlspecialchars(
                            $item['status']
                        );

                        ?>

                    </span>

                </div>

            </div>



            <!-- POSTED DATE -->

            <div class="info-item">

                <span class="info-label">
                    Posted Date
                </span>

                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $item['created_at'] ?? 'N/A'
                    );

                    ?>

                </div>

            </div>

        </div>


    </div>



    <!-- BACK BUTTON -->

    <a
        href="view-found-items.php"
        class="back-button"
    >

        ← Back to Found Items

    </a>


</div>


</body>

</html>

<?php

    exit();
}


/* =====================================================
   ALL APPROVED FOUND ITEMS
===================================================== */

$sql = "
    SELECT *
    FROM found_items
    WHERE status = 'Approved'
    ORDER BY found_id DESC
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

    <title>View All Found Items</title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            background: #f5f7fb;

            font-family: Arial, sans-serif;
        }


        .found-page {

            max-width: 1250px;

            margin: 40px auto;

            padding: 0 20px 40px;
        }


        .page-header {

            margin-bottom: 25px;
        }


        .page-header h1 {

            margin: 0;

            font-size: 30px;

            color: #111827;
        }


        .page-header p {

            margin-top: 8px;

            color: #6b7280;
        }


        .table-card {

            background: white;

            border-radius: 16px;

            padding: 18px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.07);

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 1050px;
        }


        th {

            background: #f3f4f6;

            color: #374151;

            font-size: 14px;

            font-weight: bold;

            padding: 14px 12px;

            text-align: left;

            border-bottom: 2px solid #e5e7eb;
        }


        td {

            padding: 13px 12px;

            border-bottom: 1px solid #eee;

            color: #374151;

            font-size: 14px;

            vertical-align: middle;
        }


        .status-badge {

            display: inline-block;

            padding: 6px 11px;

            border-radius: 20px;

            background: #dcfce7;

            color: #166534;

            font-size: 12px;

            font-weight: bold;
        }


        /* ================================================
           VIEW DETAILS BUTTON
        ================================================= */

        .view-details-btn {

            display: inline-block;

            padding: 8px 14px;

            border-radius: 8px;

            background: #003844;

            color: white;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s;

            white-space: nowrap;
        }


        .view-details-btn:hover {

            background: #0fa4af;
        }


        .back-dashboard {

            display: inline-block;

            margin-top: 22px;

            padding: 11px 20px;

            border-radius: 10px;

            background: #111827;

            color: white;

            text-decoration: none;

            transition: 0.2s;
        }


        .back-dashboard:hover {

            background: #374151;
        }


        .empty-message {

            text-align: center;

            padding: 35px;

            color: #6b7280;
        }


        @media (max-width: 768px) {

            .found-page {

                margin-top: 25px;
            }


            .page-header h1 {

                font-size: 24px;
            }


            .table-card {

                padding: 10px;
            }

        }

    </style>

</head>


<body>


<div class="found-page">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>
            View All Found Items
        </h1>

        <p>
            All approved found items
        </p>

    </div>



    <!-- TABLE -->

    <div class="table-card">

        <table>

            <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        Item Name
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Description
                    </th>

                    <th>
                        Found Date
                    </th>

                    <th>
                        Location
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>


<?php

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $found_id = (int)$row['found_id'];

?>


                <tr>


                    <!-- ID -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row['found_id']
                        );

                        ?>

                    </td>



                    <!-- ITEM NAME -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row['item_name']
                        );

                        ?>

                    </td>



                    <!-- CATEGORY -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row['category']
                        );

                        ?>

                    </td>



                    <!-- DESCRIPTION -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row['description']
                        );

                        ?>

                    </td>



                    <!-- FOUND DATE -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row['found_date']
                        );

                        ?>

                    </td>



                    <!-- LOCATION -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row['found_location']
                        );

                        ?>

                    </td>



                    <!-- STATUS -->

                    <td>

                        <span class="status-badge">

                            <?php

                            echo htmlspecialchars(
                                $row['status']
                            );

                            ?>

                        </span>

                    </td>



                    <!-- ACTION -->

                    <td>

                        <a
                            href="view-found-items.php?id=<?php echo $found_id; ?>"
                            class="view-details-btn"
                        >

                            View Details

                        </a>

                    </td>


                </tr>


<?php

    }

} else {

?>


                <tr>

                    <td
                        colspan="8"
                        class="empty-message"
                    >

                        No found items available

                    </td>

                </tr>


<?php

}

?>


            </tbody>

        </table>

    </div>



    <!-- BACK TO DASHBOARD -->

    <a
        href="admin-dashboard.php"
        class="back-dashboard"
    >

        ← Back to Dashboard

    </a>


</div>


</body>

</html>