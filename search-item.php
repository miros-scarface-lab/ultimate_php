<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$result_lost = null;
$result_found = null;

$status = "";

if(isset($_POST['search'])){

    $item_name = trim($_POST['item_name'] ?? '');
    $category  = $_POST['category'] ?? '';
    $status    = $_POST['status'] ?? '';

    $like = '%' . $item_name . '%';


    // Lost Search
    if($status == "" || $status == "All" || $status == "Lost"){

        $stmt = $conn->prepare(
            "SELECT * FROM lost_items
             WHERE item_name LIKE ?
             AND (category = ? OR ? = '')"
        );
        $stmt->bind_param("sss", $like, $category, $category);
        $stmt->execute();
        $result_lost = $stmt->get_result();
    }


    // Found Search
    if($status == "" || $status == "All" || $status == "Found"){

        $stmt = $conn->prepare(
            "SELECT * FROM found_items
             WHERE item_name LIKE ?
             AND (category = ? OR ? = '')"
        );
        $stmt->bind_param("sss", $like, $category, $category);
        $stmt->execute();
        $result_found = $stmt->get_result();
    }

}

?>


<!DOCTYPE html>
<html>

<head>
    <title>Search Result</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

<h1>Search Result</h1>


<!-- Lost Items -->

<?php if($status == "All" || $status == "Lost"){ ?>

<h2>Lost Items</h2>

<table border="1" cellpadding="10" cellspacing="0">

<tr>
    <th>ID</th>
    <th>Item Name</th>
    <th>Category</th>
    <th>Description</th>
    <th>Lost Date</th>
    <th>Lost Location</th>
    <th>Status</th>
</tr>


<?php

if($result_lost && $result_lost->num_rows > 0){

    while($row = $result_lost->fetch_assoc()){

        echo "<tr>
        <td>".escapeHtml($row['lost_id'])."</td>
        <td>".escapeHtml($row['item_name'])."</td>
        <td>".escapeHtml($row['category'])."</td>
        <td>".escapeHtml($row['description'])."</td>
        <td>".escapeHtml($row['lost_date'])."</td>
        <td>".escapeHtml($row['lost_location'])."</td>
        <td>".escapeHtml($row['status'])."</td>
        </tr>";

    }

}
else if($status != "All"){

    echo "<tr>
    <td colspan='7'>No Lost Item Found</td>
    </tr>";

}

?>

</table>

<br>

<?php } ?>




<!-- Found Items -->

<?php if($status == "All" || $status == "Found"){ ?>

<h2>Found Items</h2>

<table border="1" cellpadding="10" cellspacing="0">

<tr>
    <th>ID</th>
    <th>Item Name</th>
    <th>Category</th>
    <th>Description</th>
    <th>Found Date</th>
    <th>Found Location</th>
    <th>Status</th>
</tr>


<?php

if($result_found && $result_found->num_rows > 0){

    while($row = $result_found->fetch_assoc()){

        echo "<tr>
        <td>".escapeHtml($row['found_id'])."</td>
        <td>".escapeHtml($row['item_name'])."</td>
        <td>".escapeHtml($row['category'])."</td>
        <td>".escapeHtml($row['description'])."</td>
        <td>".escapeHtml($row['found_date'])."</td>
        <td>".escapeHtml($row['found_location'])."</td>
        <td>".escapeHtml($row['status'])."</td>
        </tr>";

    }

}
else if($status != "All"){

    echo "<tr>
    <td colspan='7'>No Found Item Found</td>
    </tr>";

}

?>

</table>

<?php } ?>


<br><br>

<a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>