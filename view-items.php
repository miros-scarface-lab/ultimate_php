
<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Get selected item type
$type = $_GET['type'] ?? 'all';

if (!in_array($type, ['lost', 'found', 'all'])) {
    $type = 'all';
}

$lost_result = null;
$found_result = null;

if ($type === 'lost' || $type === 'all') {

    $lost_sql = "
        SELECT *
        FROM lost_items
        ORDER BY lost_id DESC
    ";

    $lost_result = $conn->query($lost_sql);
}

if ($type === 'found' || $type === 'all') {

    $found_sql = "
        SELECT *
        FROM found_items
        ORDER BY found_id DESC
    ";

    $found_result = $conn->query($found_sql);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>

<?php

if ($type === 'lost') {
    echo "Lost Items";
} elseif ($type === 'found') {
    echo "Found Items";
} else {
    echo "View All Items";
}

?>

</title>

<link rel="stylesheet" href="css/style.css">

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f4f7f9;
}

.container {
    width: 95%;
    max-width: 1600px;
    margin: 25px auto;
    padding: 25px;
    background: #ffffff;
    border-radius: 14px;
}

/* BACK BUTTON */

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    background: #003844;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 22px;
    transition: 0.3s;
}

.back-button:hover {
    background: #0fa4af;
    color: white;
}

/* PAGE HEADING */

.page-title {
    color: #003844;
    font-size: 28px;
    margin: 0 0 25px;
}

/* TWO PARTITIONS */

.items-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
    align-items: start;
}

.section-card {
    min-width: 0;
    background: #ffffff;
    border: 1px solid #e5e9ed;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

/* Center single section */

.items-grid.single-section {
    grid-template-columns: minmax(0, 1fr);
}

.items-grid.single-section .section-card {
    width: 100%;
    max-width: 100%;
}

.section-title {
    color: #003844;
    font-size: 21px;
    margin: 0 0 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #0fa4af;
}

/* TABLE DESIGN */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: white;
}

.items-table th {
    background: #003844;
    color: white;
    padding: 12px 10px;
    text-align: left;
    white-space: nowrap;
}

.items-table td {
    padding: 11px 10px;
    border-bottom: 1px solid #e8edf0;
    color: #334155;
    vertical-align: middle;
}

.clickable-row {
    cursor: pointer;
}

.clickable-row:hover {
    background-color: #edf8f9;
}

/* ITEM IMAGE */

.item-picture {
    width: 75px;
    height: 75px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
}

/* IMAGE POPUP */

#imageModal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
    justify-content: center;
    align-items: center;
}

#largeImage {
    max-width: 90%;
    max-height: 85%;
    border-radius: 10px;
}

.close-button {
    position: absolute;
    top: 15px;
    right: 25px;
    color: white;
    font-size: 40px;
    cursor: pointer;
}

/* MOBILE RESPONSIVE */

@media (max-width: 768px) {

    .container {
        width: 100%;
        margin: 0;
        padding: 15px 12px;
        border-radius: 0;
    }

    .back-button {
        padding: 9px 14px;
        font-size: 13px;
        margin-bottom: 18px;
    }

    .page-title {
        font-size: 23px;
        margin-bottom: 20px;
    }

    .items-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .section-card {
        padding: 13px;
        border-radius: 10px;
    }

    .section-title {
        font-size: 19px;
        margin-bottom: 12px;
    }

    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .items-table {
        min-width: 750px;
        font-size: 12px;
    }

    .items-table th,
    .items-table td {
        padding: 9px 8px;
    }

    .item-picture {
        width: 65px;
        height: 65px;
    }

}

</style>

</head>


<body>

<div class="container">


<!-- BACK TO DASHBOARD -->

<a href="dashboard.php" class="back-button">
    &#8592; Back to Dashboard
</a>


<h1 class="page-title">

<?php

if ($type === 'lost') {
    echo "Lost Items";
} elseif ($type === 'found') {
    echo "Found Items";
} else {
    echo "View All Items";
}

?>

</h1>


<div class="items-grid <?php echo ($type !== 'all') ? 'single-section' : ''; ?>">


<!-- LOST ITEMS SECTION -->

<?php if ($type === 'lost' || $type === 'all'): ?>

<div class="section-card">

<h2 class="section-title">Lost Items</h2>

<div class="table-wrapper">

<table class="items-table">

<tr>

    <th>ID</th>
    <th>Item Name</th>
    <th>Category</th>
    <th>Description</th>
    <th>Lost Date</th>
    <th>Lost Location</th>
    <th>Contact</th>
    <th>Picture</th>
    <th>Status</th>

</tr>


<?php

if ($lost_result && $lost_result->num_rows > 0) {

    while ($row = $lost_result->fetch_assoc()) {

        $lost_id = (int)$row['lost_id'];

        echo "<tr
            class='clickable-row'
            onclick=\"window.location='item-details.php?id=$lost_id&type=lost&from=items'\"
        >";

        echo "<td>" .
            htmlspecialchars($row['lost_id']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['item_name']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['category']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['description']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['lost_date']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['lost_location']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['contact_information']) .
            "</td>";

        echo "<td>";

        if (
            !empty($row['item_image']) &&
            file_exists($row['item_image'])
        ) {

            echo "<img
                src='" . htmlspecialchars($row['item_image']) . "'
                class='item-picture'
                onclick='event.stopPropagation(); showImage(this.src);'
                alt='Item Picture'
            >";

        } else {

            echo "No Image";

        }

        echo "</td>";

        echo "<td>" .
            htmlspecialchars($row['status']) .
            "</td>";

        echo "</tr>";

    }

} else {

    echo "<tr>
        <td colspan='9'>No Lost Items Found</td>
    </tr>";

}

?>

</table>

</div>

</div>

<?php endif; ?>


<!-- FOUND ITEMS SECTION -->

<?php if ($type === 'found' || $type === 'all'): ?>

<div class="section-card">

<h2 class="section-title">Found Items</h2>

<div class="table-wrapper">

<table class="items-table">

<tr>

    <th>ID</th>
    <th>Item Name</th>
    <th>Category</th>
    <th>Description</th>
    <th>Found Date</th>
    <th>Found Location</th>
    <th>Finder Contact</th>
    <th>Status</th>

</tr>


<?php

if ($found_result && $found_result->num_rows > 0) {

    while ($row = $found_result->fetch_assoc()) {

        $found_id = (int)$row['found_id'];

        echo "<tr
            class='clickable-row'
            onclick=\"window.location='item-details.php?id=$found_id&type=found&from=items'\"
        >";

        echo "<td>" .
            htmlspecialchars($row['found_id']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['item_name']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['category']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['description']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['found_date']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['found_location']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['finder_contact_information']) .
            "</td>";

        echo "<td>" .
            htmlspecialchars($row['status']) .
            "</td>";

        echo "</tr>";

    }

} else {

    echo "<tr>
        <td colspan='8'>No Found Items Found</td>
    </tr>";

}

?>

</table>

</div>

</div>

<?php endif; ?>


</div>


</div>


<!-- IMAGE POPUP -->

<div id="imageModal" onclick="closeImage()">

    <span
        class="close-button"
        onclick="closeImage()">
        &times;
    </span>

    <img
        id="largeImage"
        src=""
        alt="Large Image"
    >

</div>


<script>

function showImage(src) {

    document.getElementById("largeImage").src = src;

    document.getElementById("imageModal").style.display = "flex";

}

function closeImage() {

    document.getElementById("imageModal").style.display = "none";

}

</script>


</body>

</html>


<?php

$conn->close();

?>
