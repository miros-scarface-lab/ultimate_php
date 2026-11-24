
<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = $_GET['type'] ?? '';
$from = $_GET['from'] ?? 'dashboard';

$item = null;

if ($id <= 0) {
    die("Invalid Item ID.");
}


/* ==============================
   GET LOST ITEM
============================== */

if ($type === 'lost') {

    $sql = "SELECT *
            FROM lost_items
            WHERE lost_id = $id
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
    }

}


/* ==============================
   GET FOUND ITEM
============================== */

elseif ($type === 'found') {

    $sql = "SELECT *
            FROM found_items
            WHERE found_id = $id
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
    }

}

else {
    die("Invalid Item Type.");
}


if (!$item) {
    die("Item not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Item Details</title>

<link rel="stylesheet" href="css/style.css">


<style>

/* =================================
   PAGE
================================= */

.details-page {

    max-width: 850px;

    margin: 40px auto;

    padding: 20px;

}


/* =================================
   MAIN CARD
================================= */

.details-card {

    background: #ffffff;

    border-radius: 16px;

    padding: 30px;

    box-shadow: 0 5px 20px rgba(0,0,0,0.10);

}


/* =================================
   TITLE
================================= */

.details-title {

    text-align: center;

    margin-bottom: 30px;

}

.details-title h1 {

    margin-bottom: 8px;

}

.details-title p {

    color: #777;

}


/* =================================
   IMAGE
================================= */

.image-section {

    text-align: center;

    margin-bottom: 30px;

}


.item-image {

    width: 250px;

    height: 250px;

    object-fit: cover;

    border-radius: 14px;

    cursor: pointer;

    border: 1px solid #ddd;

    transition: 0.3s;

}


.item-image:hover {

    transform: scale(1.03);

}


.no-image {

    display: inline-block;

    padding: 60px 80px;

    border: 1px dashed #aaa;

    border-radius: 12px;

    color: #777;

}


/* =================================
   DETAILS GRID
================================= */

.details-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 18px;

}


.detail-box {

    background: #f7f7f7;

    border-radius: 10px;

    padding: 16px;

}


.detail-label {

    font-size: 13px;

    color: #777;

    margin-bottom: 6px;

}


.detail-value {

    font-size: 16px;

    font-weight: 600;

    word-break: break-word;

}


/* Description full width */

.description-box {

    grid-column: 1 / -1;

}


/* =================================
   STATUS
================================= */

.status {

    display: inline-block;

    padding: 6px 14px;

    border-radius: 20px;

    font-size: 14px;

}


/* =================================
   BACK BUTTON
================================= */

.back-button {

    display: inline-block;

    margin-top: 25px;

    text-decoration: none;

}


/* =================================
   IMAGE MODAL
================================= */

#imageModal {

    display: none;

    position: fixed;

    z-index: 9999;

    left: 0;

    top: 0;

    width: 100%;

    height: 100%;

    background: rgba(0,0,0,0.88);

    justify-content: center;

    align-items: center;

}


#largeImage {

    max-width: 90%;

    max-height: 90%;

    border-radius: 12px;

}


.close-button {

    position: absolute;

    top: 20px;

    right: 30px;

    color: white;

    font-size: 42px;

    cursor: pointer;

}


/* =================================
   MOBILE
================================= */

@media (max-width: 650px) {

    .details-grid {

        grid-template-columns: 1fr;

    }

    .description-box {

        grid-column: auto;

    }

    .item-image {

        width: 200px;

        height: 200px;

    }

}

</style>

</head>


<body>


<div class="details-page">


<div class="details-card">


<!-- =================================
     TITLE
================================= -->

<div class="details-title">

<?php if ($type === 'lost'): ?>

    <h1>Lost Item Details</h1>

    <p>Details about the lost item</p>

<?php else: ?>

    <h1>Found Item Details</h1>

    <p>Details about the found item</p>

<?php endif; ?>

</div>


<!-- =================================
     IMAGE
================================= -->

<div class="image-section">

<?php

if (
    $type === 'lost' &&
    !empty($item['item_image']) &&
    file_exists($item['item_image'])
) {

?>

<img
    src="<?php echo htmlspecialchars($item['item_image']); ?>"
    class="item-image"
    onclick="showImage(this.src)"
    alt="Item Picture">

<?php

} else {

    echo "<div class='no-image'>No Image Available</div>";

}

?>

</div>


<!-- =================================
     DETAILS
================================= -->

<div class="details-grid">


<!-- ITEM NAME -->

<div class="detail-box">

<div class="detail-label">
    Item Name
</div>

<div class="detail-value">

<?php
echo htmlspecialchars($item['item_name']);
?>

</div>

</div>


<!-- CATEGORY -->

<div class="detail-box">

<div class="detail-label">
    Category
</div>

<div class="detail-value">

<?php
echo htmlspecialchars($item['category']);
?>

</div>

</div>


<!-- DATE -->

<div class="detail-box">

<div class="detail-label">

<?php

if ($type === 'lost') {
    echo "Lost Date";
} else {
    echo "Found Date";
}

?>

</div>

<div class="detail-value">

<?php

if ($type === 'lost') {

    echo htmlspecialchars($item['lost_date']);

} else {

    echo htmlspecialchars($item['found_date']);

}

?>

</div>

</div>


<!-- LOCATION -->

<div class="detail-box">

<div class="detail-label">
    Location
</div>

<div class="detail-value">

<?php

if ($type === 'lost') {

    echo htmlspecialchars($item['lost_location']);

} else {

    echo htmlspecialchars($item['found_location']);

}

?>

</div>

</div>


<!-- CONTACT -->

<div class="detail-box">

<div class="detail-label">

<?php

if ($type === 'lost') {
    echo "Contact Information";
} else {
    echo "Finder Contact";
}

?>

</div>

<div class="detail-value">

<?php

if ($type === 'lost') {

    echo htmlspecialchars($item['contact_information']);

} else {

    echo htmlspecialchars($item['finder_contact_information']);

}

?>

</div>

</div>


<!-- STATUS -->

<div class="detail-box">

<div class="detail-label">
    Status
</div>

<div class="detail-value">

<span class="status">

<?php
echo htmlspecialchars($item['status']);
?>

</span>

</div>

</div>


<!-- DESCRIPTION -->

<div class="detail-box description-box">

<div class="detail-label">
    Description
</div>

<div class="detail-value">

<?php

if (!empty($item['description'])) {

    echo nl2br(htmlspecialchars($item['description']));

} else {

    echo "No description provided.";

}

?>

</div>

</div>


</div>


<!-- =================================
     BACK BUTTON
================================= -->

<?php if ($from === 'items'): ?>

<a
    class="back-button"
    href="view-items.php"
>
    ← Back to Items
</a>

<?php else: ?>

<a
    class="back-button"
    href="dashboard.php"
>
    ← Back to Dashboard
</a>

<?php endif; ?>


</div>

</div>


<!-- =================================
     IMAGE POPUP
================================= -->

<div
    id="imageModal"
    onclick="closeImage()"
>

<span
    class="close-button"
    onclick="closeImage()"
>
    &times;
</span>


<img
    id="largeImage"
    src=""
    alt="Large Item Picture"
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
