<?php

session_start();
include "db.php";

/* ==========================================
   LOGIN CHECK
========================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* ==========================================
   GET MATCH ID
========================================== */

if (!isset($_POST['match_id']) || !is_numeric($_POST['match_id'])) {
    die("Invalid request.");
}

$match_id = (int) $_POST['match_id'];


/* ==========================================
   GET MATCH INFORMATION
========================================== */

$sql = "
    SELECT
        match_id,
        lost_id,
        found_id,
        claim_status,
        finder_status,
        owner_status
    FROM matches
    WHERE match_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param("i", $match_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Match not found.");
}

$match = $result->fetch_assoc();

$stmt->close();


/* ==========================================
   FINDER CHECK
========================================== */

$found_id = (int) $match['found_id'];

$finder_sql = "
    SELECT user_id
    FROM found_items
    WHERE found_id = ?
";

$stmt = $conn->prepare($finder_sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param("i", $found_id);
$stmt->execute();

$finder_result = $stmt->get_result();

if ($finder_result->num_rows === 0) {
    die("Finder information not found.");
}

$finder = $finder_result->fetch_assoc();

$stmt->close();

$finder_id = (int) $finder['user_id'];


/* ==========================================
   ONLY FINDER CAN APPROVE
========================================== */

if ($user_id !== $finder_id) {
    die("You are not authorized to approve this claim.");
}


/* ==========================================
   CHECK CLAIM STATUS
========================================== */

if ($match['claim_status'] !== 'Pending') {
    die("This claim request has already been processed.");
}


/* ==========================================
   APPROVE CLAIM
========================================== */

$update_sql = "
    UPDATE matches
    SET claim_status = 'Approved'
    WHERE match_id = ?
";

$stmt = $conn->prepare($update_sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param("i", $match_id);

if (!$stmt->execute()) {
    die("Failed to approve claim.");
}

$stmt->close();


/* ==========================================
   GET OWNER INFORMATION
========================================== */

$lost_id = (int) $match['lost_id'];

$owner_sql = "
    SELECT
        user_id,
        item_name
    FROM lost_items
    WHERE lost_id = ?
";

$stmt = $conn->prepare($owner_sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param("i", $lost_id);
$stmt->execute();

$owner_result = $stmt->get_result();

if ($owner_result->num_rows > 0) {

    $owner = $owner_result->fetch_assoc();

    $owner_id = (int) $owner['user_id'];
    $item_name = $owner['item_name'];

    /* ==========================================
       OWNER NOTIFICATION
    ========================================== */

    $title = "Claim Request Approved";

    $message = "Your claim request for \"$item_name\" has been approved by the finder. You can now view the approved claim details.";

    $notif_sql = "
        INSERT INTO notifications
        (
            user_id,
            match_id,
            title,
            message,
            receiver,
            is_read,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'user',
            0,
            NOW()
        )
    ";

    $notif_stmt = $conn->prepare($notif_sql);

    if ($notif_stmt) {

        $notif_stmt->bind_param(
            "iiss",
            $owner_id,
            $match_id,
            $title,
            $message
        );

        $notif_stmt->execute();

        $notif_stmt->close();
    }
}


/* ==========================================
   REDIRECT
========================================== */

header(
    "Location: view-claim.php?match_id=" .
    $match_id .
    "&success=approved"
);

exit();

?>