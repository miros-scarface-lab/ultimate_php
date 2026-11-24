
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $user_id = (int) $_SESSION['user_id'];

    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $description = trim($_POST['description'] ?? '');
    $found_date = $_POST['found_date'];
    $found_location = $_POST['found_location'];
    $finder_contact_information = $_POST['finder_contact_information'];


    // DESCRIPTION VALIDATION

    if ($description === '') {
        echo "<script>
        alert('Description is required!');
        window.history.back();
        </script>";
        exit();
    }



    $sql = "INSERT INTO found_items
    (user_id, item_name, category, found_date, found_location, finder_contact_information, description)
    VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database Error.");
    }

    $stmt->bind_param(
        "issssss",
        $user_id, $item_name, $category, $found_date, $found_location,
        $finder_contact_information, $description
    );

    if ($stmt->execute()) {



        // Admin Notification

        $title = "New Found Item Post";

        $message = "A new found item is waiting for approval.";


        $notification_sql = "INSERT INTO notifications
        (user_id, title, message, receiver, is_read)
        VALUES (?, ?, ?, 'admin', 0)";

        $n_stmt = $conn->prepare($notification_sql);
        $n_stmt->bind_param("iss", $user_id, $title, $message);
        $n_stmt->execute();
        $n_stmt->close();

        echo "<script>
        alert('Found Item Added Successfully!');
        window.location.href='dashboard.php';
        </script>";

        exit();



    } else {


        error_log("Add found item failed: " . $stmt->error);
        echo "Could not save the item. Please try again.";


    }


}

?>



<!DOCTYPE html>
<html>


<head>

    <title>Add Found Item</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* MOBILE RESPONSIVE DESIGN ONLY */

        @media (max-width: 600px) {

            body {
                margin: 0;
                padding: 0;
                min-height: 100vh;
            }

            .container {
                width: 100%;
                max-width: 100%;
                min-height: 100vh;
                margin: 0;
                padding: 12px 16px;
                border-radius: 0;
            }

            h1 {
                font-size: 22px;
                margin-top: 5px;
                margin-bottom: 12px;
            }

            form {
                display: flex;
                flex-direction: column;
                gap: 7px;
            }

            form br {
                display: none;
            }

            input,
            select,
            textarea {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                padding: 9px 10px;
                font-size: 14px;
                margin: 0;
            }

            textarea {
                resize: vertical;
            }

            textarea[name="description"] {
                height: 65px;
                min-height: 65px;
            }

            label {
                font-size: 13px;
                margin-bottom: 0;
            }

            button {
                width: 100%;
                padding: 10px;
                font-size: 14px;
                margin-top: 3px;
            }

            form a {
                text-align: center;
                font-size: 13px;
                margin-top: 2px;
            }

        }

    </style>

</head>



<body>



<div class="container">



<h1>Add Found Item</h1>




<form action="" method="POST">



<input type="text" name="item_name" placeholder="Item Name" required>


<br><br>




<select name="category" required>


    <option value="">Select Category</option>

    <option>Electronics</option>

    <option>Documents</option>

    <option>Accessories</option>

    <option>Books or Stationery</option>

    <option>Others</option>


</select>



<br><br>




<textarea name="description" rows="5" placeholder="Description" required></textarea>



<br><br>




<label>Found Date</label><br>

<input type="date" name="found_date" required>



<br><br>




<input type="text" name="found_location" placeholder="Found Location" required>



<br><br>




<input type="text" name="finder_contact_information" placeholder="Finder Contact Information" required>



<br><br>




<button type="submit">Submit</button>



<br><br>




<a href="dashboard.php">
Back to Dashboard
</a>



</form>



</div>




</body>


</html>
