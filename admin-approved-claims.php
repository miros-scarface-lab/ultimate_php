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
   GET APPROVED CLAIMS
========================================== */

$sql = "
    SELECT
        m.match_id,
        m.lost_id,
        m.found_id,
        m.match_percentage,
        m.claim_status,
        m.created_at,

        /* OWNER = CLAIM REQUESTER */

        owner.user_id AS owner_id,
        owner.full_name AS owner_name,
        owner.student_id AS owner_student_id,
        owner.email AS owner_email,
        owner.department AS owner_department,

        /* LOST ITEM */

        l.item_name AS lost_item_name,

        /* FINDER = CLAIM APPROVER */

        finder.user_id AS finder_id,
        finder.full_name AS finder_name,
        finder.student_id AS finder_student_id,
        finder.email AS finder_email,
        finder.department AS finder_department,

        /* FOUND ITEM */

        f.item_name AS found_item_name

    FROM matches m

    INNER JOIN lost_items l
        ON m.lost_id = l.lost_id

    INNER JOIN found_items f
        ON m.found_id = f.found_id

    LEFT JOIN users owner
        ON l.user_id = owner.user_id

    LEFT JOIN users finder
        ON f.user_id = finder.user_id

    WHERE m.claim_status = 'Approved'

    ORDER BY m.match_id DESC
";


$result = $conn->query($sql);


if (!$result) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

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

    <title>Approved Claims</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<div class="container">


    <!-- ==================================
         PAGE HEADER
    ================================== -->

    <h1>Approved Claims</h1>


    <p>
        Claims approved by the finder are shown below.
    </p>


    <br>


    <?php if ($result->num_rows > 0) { ?>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Match ID</th>

                        <th>Lost Item</th>

                        <th>Claim Requested By</th>

                        <th>Found Item</th>

                        <th>Approved By</th>

                        <th>Match %</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php

                while ($row = $result->fetch_assoc()) {

                ?>

                    <tr>


                        <!-- MATCH ID -->

                        <td>

                            <?php
                            echo (int)$row['match_id'];
                            ?>

                        </td>


                        <!-- LOST ITEM -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['lost_item_name']
                            );
                            ?>

                        </td>


                        <!-- CLAIM REQUESTED BY / OWNER -->

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $row['owner_name'] ?? 'N/A'
                                );
                                ?>
                            </strong>

                            <br>

                            <small>
                                Student ID:
                                <?php
                                echo htmlspecialchars(
                                    $row['owner_student_id'] ?? 'N/A'
                                );
                                ?>
                            </small>

                            <br>

                            <small>
                                Email:
                                <?php
                                echo htmlspecialchars(
                                    $row['owner_email'] ?? 'N/A'
                                );
                                ?>
                            </small>

                        </td>


                        <!-- FOUND ITEM -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['found_item_name']
                            );
                            ?>

                        </td>


                        <!-- APPROVED BY / FINDER -->

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $row['finder_name'] ?? 'N/A'
                                );
                                ?>
                            </strong>

                            <br>

                            <small>
                                Student ID:
                                <?php
                                echo htmlspecialchars(
                                    $row['finder_student_id'] ?? 'N/A'
                                );
                                ?>
                            </small>

                            <br>

                            <small>
                                Email:
                                <?php
                                echo htmlspecialchars(
                                    $row['finder_email'] ?? 'N/A'
                                );
                                ?>
                            </small>

                        </td>


                        <!-- MATCH PERCENTAGE -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['match_percentage']
                            );
                            ?>%

                        </td>


                        <!-- STATUS -->

                        <td>

                            <strong>
                                Approved
                            </strong>

                        </td>


                        <!-- ACTION -->

                        <td>

                            <a
                                href="approved-claim-details.php?match_id=<?php echo (int)$row['match_id']; ?>"
                                class="btn"
                            >
                                View Claim
                            </a>

                        </td>


                    </tr>


                <?php

                }

                ?>


                </tbody>

            </table>

        </div>


    <?php } else { ?>


        <p>
            No approved claims found.
        </p>


    <?php } ?>


    <br>
    <br>


    <!-- ==================================
         BACK TO DASHBOARD
    ================================== -->

    <a
        href="admin-dashboard.php"
        class="btn"
    >
        Back to Dashboard
    </a>


</div>


<script src="js/script.js"></script>


</body>

</html>