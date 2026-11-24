
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
    $lost_date = $_POST['lost_date'];
    $lost_location = $_POST['lost_location'];
    $contact_information = $_POST['contact_information'];
    $secret_information = $_POST['secret_information'] ?? '';

    // DESCRIPTION VALIDATION

    if ($description === '') {
        echo "<script>
        alert('Description is required!');
        window.history.back();
        </script>";
        exit();
    }

    // ==============================
    // OPTIONAL IMAGE UPLOAD
    // ==============================

    $item_image = NULL;

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {

        $upload_folder = "uploads/";

        // Create uploads folder if it doesn't exist
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0755, true);
        }

        $tmp_name = $_FILES['item_image']['tmp_name'];

        // Max 5 MB
        if ($_FILES['item_image']['size'] > 5 * 1024 * 1024) {
            die("Image must be 5 MB or smaller.");
        }

        // Check the real file type, not just the file name
        $allowed_types = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($tmp_name);

        if (!isset($allowed_types[$mime_type]) || @getimagesize($tmp_name) === false) {
            die("Only JPG, JPEG, PNG and WEBP images are allowed.");
        }

        // Unique file name; extension comes from the detected type
        $new_file_name = time() . "_" . bin2hex(random_bytes(8)) . "." . $allowed_types[$mime_type];

        $destination = $upload_folder . $new_file_name;

        if (move_uploaded_file($tmp_name, $destination)) {
            $item_image = $destination;
        }
    }


    // ==============================
    // INSERT LOST ITEM
    // ==============================

    $sql = "INSERT INTO lost_items
    (user_id, item_name, category, lost_date, lost_location,
     contact_information, secret_information, description, item_image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database Error.");
    }

    $stmt->bind_param(
        "issssssss",
        $user_id, $item_name, $category, $lost_date, $lost_location,
        $contact_information, $secret_information, $description, $item_image
    );

    if ($stmt->execute()) {

        // ==============================
        // ADMIN NOTIFICATION
        // ==============================

        $title = "New Lost Item Post";

        $message = "A new lost item is waiting for approval.";

        $notification_sql = "INSERT INTO notifications
        (user_id, title, message, receiver, is_read)
        VALUES (?, ?, ?, 'admin', 0)";

        $n_stmt = $conn->prepare($notification_sql);
        $n_stmt->bind_param("iss", $user_id, $title, $message);
        $n_stmt->execute();
        $n_stmt->close();

        echo "<script>
        alert('Lost Item Added Successfully!');
        window.location.href='dashboard.php';
        </script>";

        exit();

    } else {

        error_log("Add lost item failed: " . $stmt->error);
        echo "Could not save the item. Please try again.";

    }
}

?>


<!DOCTYPE html>
<html>

<head>

    <title>Add Lost Item</title>

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

            textarea[name="secret_information"] {
                height: 45px;
                min-height: 45px;
            }

            label {
                font-size: 13px;
                margin-bottom: 0;
            }

            small {
                font-size: 11px;
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

<h1>Add Lost Item</h1>


<form action="" method="POST" enctype="multipart/form-data">


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


<textarea name="description"
rows="5"
placeholder="Description"
required></textarea>

<br><br>


<label>Lost Date</label><br>

<input type="date" name="lost_date" required>

<br><br>


<input type="text"
name="lost_location"
placeholder="Lost Location"
required>

<br><br>


<input type="text"
name="contact_information"
placeholder="Contact Information"
required>

<br><br>


<textarea name="secret_information"
rows="5"
placeholder="Secret Information"></textarea>

<br><br>


<!-- ==============================
     OPTIONAL IMAGE
============================== -->

<label>Item Picture (Optional)</label><br>

<input type="file"
       name="item_image"
       accept="image/jpeg,image/png,image/webp">

<br>

<small>You can upload a picture if you want.</small>

<br><br>


<button type="submit">Submit</button>

<br><br>


<a href="dashboard.php">Back to Dashboard</a>


</form>

</div>

</body>

</html>
