<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}


/* =====================================================
   VIEW PENDING LOST DETAILS
===================================================== */

if (isset($_GET['type']) && $_GET['type'] == 'lost' && isset($_GET['id'])) {

    $lost_id = intval($_GET['id']);

    $sql = "SELECT l.*, 
                   u.full_name,
                   u.student_id,
                   u.email,
                   u.department
            FROM lost_items l
            LEFT JOIN users u ON l.user_id = u.user_id
            WHERE l.lost_id = ?
            AND l.status = 'Pending'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lost_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Pending lost item not found.");
    }

    $item = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pending Lost Item Details</title>

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
    box-shadow: 0 5px 20px rgba(0,0,0,.07);
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
    background: #fef3c7;
    color: #92400e;
}

.image-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,.07);
    text-align: center;
}

.image-card h2 {
    margin-top: 0;
    margin-bottom: 18px;
    font-size: 20px;
    color: #111827;
}

.item-image {
    max-width: 350px;
    max-height: 350px;
    border-radius: 12px;
    object-fit: contain;
    border: 1px solid #e5e7eb;
}

.action-buttons {
    margin-top: 25px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.approve-button {
    display: inline-block;
    padding: 11px 20px;
    border-radius: 10px;
    background: #166534;
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.reject-button {
    display: inline-block;
    padding: 11px 20px;
    border-radius: 10px;
    background: #b91c1c;
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.back-button {
    display: inline-block;
    margin-top: 25px;
    padding: 11px 20px;
    border-radius: 10px;
    background: #111827;
    color: white;
    text-decoration: none;
}

@media(max-width:768px) {

    .information-grid {
        grid-template-columns: 1fr;
    }

    .page-header h1 {
        font-size: 24px;
    }

}

</style>

</head>

<body>

<div class="details-page">

    <div class="page-header">

        <h1>Pending Lost Item Details</h1>

        <p>This post is waiting for admin approval.</p>

    </div>


    <?php if (!empty($item['item_image'])) { ?>

    <div class="image-card">

        <h2>Item Image</h2>

        <img
            src="<?php echo htmlspecialchars($item['item_image']); ?>"
            alt="Lost Item Image"
            class="item-image"
        >

    </div>

    <?php } ?>


    <div class="information-grid">


        <!-- Poster Information -->

        <div class="info-card">

            <h2>Poster Information</h2>

            <div class="info-item">

                <span class="info-label">Full Name</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['full_name'] ?? 'N/A'); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Student ID</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['student_id'] ?? 'N/A'); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Email Address</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['email'] ?? 'N/A'); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Department</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['department'] ?? 'N/A'); ?>
                </div>

            </div>

        </div>


        <!-- Lost Item Information -->

        <div class="info-card">

            <h2>Lost Item Information</h2>

            <div class="info-item">

                <span class="info-label">Lost Item ID</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['lost_id']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Item Name</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['item_name']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Category</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['category']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Description</span>

                <div class="info-value">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Lost Date</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['lost_date']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Lost Location</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['lost_location']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Contact Information</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['contact_information']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Secret Information</span>

                <div class="info-value">
                    <?php echo nl2br(htmlspecialchars($item['secret_information'])); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Status</span>

                <div class="info-value">

                    <span class="status">
                        <?php echo htmlspecialchars($item['status']); ?>
                    </span>

                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Posted Date</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['created_at']); ?>
                </div>

            </div>

        </div>

    </div>


    <div class="action-buttons">

        <a
            href="approve-post.php?id=<?php echo $item['lost_id']; ?>&type=lost"
            data-post="1"
            class="approve-button"
        >
            Approve
        </a>

        <a
            href="reject-post.php?id=<?php echo $item['lost_id']; ?>&type=lost"
            data-post="1"
            class="reject-button"
        >
            Reject
        </a>

    </div>


    <a href="admin-pending-posts.php" class="back-button">
        Back to Pending Posts
    </a>

</div>

<?php echo csrfPostScript(); ?>
</body>

</html>

<?php
exit();
}


/* =====================================================
   VIEW PENDING FOUND DETAILS
===================================================== */

if (isset($_GET['type']) && $_GET['type'] == 'found' && isset($_GET['id'])) {

    $found_id = intval($_GET['id']);

    $sql = "SELECT f.*,
                   u.full_name,
                   u.student_id,
                   u.email,
                   u.department
            FROM found_items f
            LEFT JOIN users u ON f.user_id = u.user_id
            WHERE f.found_id = ?
            AND f.status = 'Pending'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $found_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Pending found item not found.");
    }

    $item = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pending Found Item Details</title>

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
    box-shadow: 0 5px 20px rgba(0,0,0,.07);
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
    background: #fef3c7;
    color: #92400e;
}

.action-buttons {
    margin-top: 25px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.approve-button {
    display: inline-block;
    padding: 11px 20px;
    border-radius: 10px;
    background: #166534;
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.reject-button {
    display: inline-block;
    padding: 11px 20px;
    border-radius: 10px;
    background: #b91c1c;
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.back-button {
    display: inline-block;
    margin-top: 25px;
    padding: 11px 20px;
    border-radius: 10px;
    background: #111827;
    color: white;
    text-decoration: none;
}

@media(max-width:768px) {

    .information-grid {
        grid-template-columns: 1fr;
    }

    .page-header h1 {
        font-size: 24px;
    }

}

</style>

</head>

<body>

<div class="details-page">

    <div class="page-header">

        <h1>Pending Found Item Details</h1>

        <p>This post is waiting for admin approval.</p>

    </div>


    <div class="information-grid">


        <!-- Poster Information -->

        <div class="info-card">

            <h2>Poster Information</h2>

            <div class="info-item">

                <span class="info-label">Full Name</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['full_name'] ?? 'N/A'); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Student ID</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['student_id'] ?? 'N/A'); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Email Address</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['email'] ?? 'N/A'); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Department</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['department'] ?? 'N/A'); ?>
                </div>

            </div>

        </div>


        <!-- Found Item Information -->

        <div class="info-card">

            <h2>Found Item Information</h2>

            <div class="info-item">

                <span class="info-label">Found Item ID</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['found_id']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Item Name</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['item_name']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Category</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['category']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Description</span>

                <div class="info-value">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Found Date</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['found_date']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Found Location</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['found_location']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Finder Contact Information
                </span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['finder_contact_information']); ?>
                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Status</span>

                <div class="info-value">

                    <span class="status">
                        <?php echo htmlspecialchars($item['status']); ?>
                    </span>

                </div>

            </div>


            <div class="info-item">

                <span class="info-label">Posted Date</span>

                <div class="info-value">
                    <?php echo htmlspecialchars($item['created_at']); ?>
                </div>

            </div>

        </div>

    </div>


    <div class="action-buttons">

        <a
            href="approve-post.php?id=<?php echo $item['found_id']; ?>&type=found"
            data-post="1"
            class="approve-button"
        >
            Approve
        </a>

        <a
            href="reject-post.php?id=<?php echo $item['found_id']; ?>&type=found"
            data-post="1"
            class="reject-button"
        >
            Reject
        </a>

    </div>


    <a href="pending-posts.php" class="back-button">
        Back to Pending Posts
    </a>

</div>

<?php echo csrfPostScript(); ?>
</body>

</html>

<?php
exit();
}


/* =====================================================
   PENDING LOST ITEMS
===================================================== */

$lost_sql = "SELECT *
             FROM lost_items
             WHERE status='Pending'
             ORDER BY lost_id DESC";

$lost_result = $conn->query($lost_sql);


/* =====================================================
   PENDING FOUND ITEMS
===================================================== */

$found_sql = "SELECT *
              FROM found_items
              WHERE status='Pending'
              ORDER BY found_id DESC";

$found_result = $conn->query($found_sql);

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pending Posts</title>

<link rel="stylesheet" href="css/style.css">

<style>

body {
    background: #f5f7fb;
    color: #1f2937;
}

.container {
    max-width: 1250px;
    margin: 40px auto;
    padding: 0 20px 40px;
}

h1 {
    color: #111827;
    margin-bottom: 8px;
}

.container > p {
    color: #6b7280;
    margin-bottom: 30px;
}

h2 {
    color: #111827;
    margin-top: 30px;
    margin-bottom: 15px;
}

.table-card {
    background: white;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 5px 20px rgba(0,0,0,.07);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px;
}

th {
    background: #f3f4f6;
    color: #374151 !important;
    font-size: 14px;
    font-weight: bold;
    padding: 14px 12px;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
}

td {
    padding: 13px 12px;
    border-bottom: 1px solid #eee;
    color: #374151 !important;
    font-size: 14px;
    vertical-align: middle;
}

.status-badge {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    background: #fef3c7;
    color: #92400e !important;
    font-size: 12px;
    font-weight: bold;
}

.view-details-btn {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 8px;
    background: #003844;
    color: white !important;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    margin-right: 5px;
    margin-bottom: 5px;
}

.view-details-btn:hover {
    background: #0fa4af;
}

.approve-btn {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 8px;
    background: #166534;
    color: white !important;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    margin-right: 5px;
    margin-bottom: 5px;
}

.reject-btn {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 8px;
    background: #b91c1c;
    color: white !important;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 5px;
}

.back-dashboard {
    display: inline-block;
    margin-top: 22px;
    padding: 11px 20px;
    border-radius: 10px;
    background: #111827;
    color: white !important;
    text-decoration: none;
}

.empty-message {
    text-align: center;
    padding: 35px;
    color: #6b7280 !important;
}

</style>

</head>


<body>


<div class="container">


<h1>Pending Posts</h1>

<p>Posts waiting for admin approval.</p>


<!-- Pending Lost Items -->

<h2>Pending Lost Items</h2>


<div class="table-card">

<table>

<tr>

<th>ID</th>
<th>Item Name</th>
<th>Category</th>
<th>Description</th>
<th>Posted By</th>
<th>Date</th>
<th>Secret Information</th>
<th>Status</th>
<th>Action</th>

</tr>


<?php

if ($lost_result->num_rows > 0) {

    while($row = $lost_result->fetch_assoc()) {

?>

<tr>

<td>
<?php echo htmlspecialchars($row['lost_id']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['item_name']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['category']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['description']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['user_id']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['lost_date']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['secret_information']); ?>
</td>

<td>

<span class="status-badge">
<?php echo htmlspecialchars($row['status']); ?>
</span>

</td>

<td>

<a
    href="pending-posts.php?type=lost&id=<?php echo $row['lost_id']; ?>"
    class="view-details-btn"
>
    View Details
</a>

<a
    href="approve-post.php?id=<?php echo $row['lost_id']; ?>&type=lost"
            data-post="1"
    class="approve-btn"
>
    Approve
</a>

<a
    href="reject-post.php?id=<?php echo $row['lost_id']; ?>&type=lost"
            data-post="1"
    class="reject-btn"
>
    Reject
</a>

</td>

</tr>

<?php

    }

} else {

?>

<tr>

<td colspan="9" class="empty-message">
No pending lost items found.
</td>

</tr>

<?php

}

?>

</table>

</div>


<!-- Pending Found Items -->

<h2>Pending Found Items</h2>


<div class="table-card">

<table>

<tr>

<th>ID</th>
<th>Item Name</th>
<th>Category</th>
<th>Description</th>
<th>Posted By</th>
<th>Date</th>
<th>Status</th>
<th>Action</th>

</tr>


<?php

if ($found_result->num_rows > 0) {

    while($row = $found_result->fetch_assoc()) {

?>

<tr>

<td>
<?php echo htmlspecialchars($row['found_id']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['item_name']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['category']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['description']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['user_id']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['found_date']); ?>
</td>

<td>

<span class="status-badge">
<?php echo htmlspecialchars($row['status']); ?>
</span>

</td>

<td>

<a
    href="pending-posts.php?type=found&id=<?php echo $row['found_id']; ?>"
    class="view-details-btn"
>
    View Details
</a>

<a
    href="approve-post.php?id=<?php echo $row['found_id']; ?>&type=found"
            data-post="1"
    class="approve-btn"
>
    Approve
</a>

<a
    href="reject-post.php?id=<?php echo $row['found_id']; ?>&type=found"
            data-post="1"
    class="reject-btn"
>
    Reject
</a>

</td>

</tr>

<?php

    }

} else {

?>

<tr>

<td colspan="8" class="empty-message">
No pending found items found.
</td>

</tr>

<?php

}

?>

</table>

</div>


<a href="admin-dashboard.php" class="back-dashboard">
    Back to Dashboard
</a>


</div>

<?php echo csrfPostScript(); ?>
</body>

</html>