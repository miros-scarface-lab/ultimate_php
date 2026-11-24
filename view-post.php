<?php

session_start();
include "db.php";


/* LOGIN CHECK */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.html");
    exit();

}


/* GET TYPE AND ID */

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;


/* INVALID REQUEST */

if ($id <= 0 || ($type !== 'lost' && $type !== 'found')) {

    die("Invalid post.");

}


/* ==========================================
   LOST ITEM
   ========================================== */

if ($type === 'lost') {

    $sql = "
        SELECT *
        FROM lost_items
        WHERE lost_id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows == 0) {

        die("Lost item not found.");

    }


    $item = $result->fetch_assoc();

    $page_title = "Lost Item Details";

}


/* ==========================================
   FOUND ITEM
   ========================================== */

else {

    $sql = "
        SELECT *
        FROM found_items
        WHERE found_id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows == 0) {

        die("Found item not found.");

    }


    $item = $result->fetch_assoc();

    $page_title = "Found Item Details";

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        <?php echo $page_title; ?>

    </title>


    <link rel="stylesheet"
          href="css/style.css">


    <style>

        .details-card {

            max-width: 650px;

            margin: 40px auto;

            padding: 30px;

            background: white;

            border-radius: 12px;

            box-shadow: 0 4px 12px rgba(0,0,0,0.1);

            text-align: left;

        }


        .details-card h1 {

            text-align: center;

            margin-bottom: 25px;

        }


        .detail {

            padding: 12px 0;

            border-bottom: 1px solid #eee;

        }


        .detail strong {

            display: inline-block;

            width: 160px;

            color: #333;

        }


        .back-button {

            display: block;

            width: 180px;

            margin: 25px auto 0;

            text-align: center;

        }


    </style>

</head>


<body>


<div class="container">


    <div class="details-card">


        <h1>

            <?php

            echo htmlspecialchars(
                $page_title
            );

            ?>

        </h1>


        <!-- ITEM NAME -->

        <div class="detail">

            <strong>
                Item Name:
            </strong>

            <?php

            echo htmlspecialchars(
                $item['item_name']
            );

            ?>

        </div>


        <!-- CATEGORY -->

        <div class="detail">

            <strong>
                Category:
            </strong>

            <?php

            echo htmlspecialchars(
                $item['category']
            );

            ?>

        </div>


        <?php if ($type === 'lost') { ?>


            <!-- LOST DATE -->

            <div class="detail">

                <strong>
                    Lost Date:
                </strong>

                <?php

                echo htmlspecialchars(
                    $item['lost_date']
                );

                ?>

            </div>


            <!-- LOST LOCATION -->

            <div class="detail">

                <strong>
                    Lost Location:
                </strong>

                <?php

                echo htmlspecialchars(
                    $item['lost_location']
                );

                ?>

            </div>


        <?php } else { ?>


            <!-- FOUND DATE -->

            <div class="detail">

                <strong>
                    Found Date:
                </strong>

                <?php

                echo htmlspecialchars(
                    $item['found_date']
                );

                ?>

            </div>


            <!-- FOUND LOCATION -->

            <div class="detail">

                <strong>
                    Found Location:
                </strong>

                <?php

                echo htmlspecialchars(
                    $item['found_location']
                );

                ?>

            </div>


        <?php } ?>


        <!-- DESCRIPTION -->

        <div class="detail">

            <strong>
                Description:
            </strong>

            <?php

            echo htmlspecialchars(
                $item['description']
            );

            ?>

        </div>


        <!-- CONTACT -->

        <div class="detail">

            <strong>
                Contact:
            </strong>

            <?php

            if ($type === 'lost') {

                echo htmlspecialchars(
                    $item['contact_information']
                );

            } else {

                echo htmlspecialchars(
                    $item['finder_contact_information']
                );

            }

            ?>

        </div>


        <!-- STATUS -->

        <div class="detail">

            <strong>
                Status:
            </strong>

            <?php

            echo htmlspecialchars(
                $item['status']
            );

            ?>

        </div>


        <!-- BACK BUTTON -->

        <a href="dashboard.php"
           class="btn back-button">

            ← Back to Dashboard

        </a>


    </div>


</div>


</body>

</html>


<?php

$stmt->close();

$conn->close();

?>