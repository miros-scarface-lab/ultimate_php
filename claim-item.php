<?php

session_start();
include "db.php";


/* =====================================
   USER LOGIN CHECK
===================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.html");
    exit();

}

$user_id = (int) $_SESSION['user_id'];


/* =====================================
   GET MATCH ID
===================================== */

if (!isset($_POST['match_id']) || empty($_POST['match_id'])) {

    header("Location: my-matches.php");
    exit();

}

$match_id = (int) $_POST['match_id'];


/* =====================================
   GET MATCH INFORMATION
===================================== */

$sql = "
    SELECT
        m.match_id,
        m.match_percentage,
        m.claim_status,

        l.user_id AS lost_user_id,
        l.item_name AS lost_item_name,

        f.user_id AS found_user_id,
        f.item_name AS found_item_name

    FROM matches m

    INNER JOIN lost_items l
        ON m.lost_id = l.lost_id

    INNER JOIN found_items f
        ON m.found_id = f.found_id

    WHERE m.match_id = ?
    LIMIT 1
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $match_id);

$stmt->execute();

$result = $stmt->get_result();


/* =====================================
   MATCH NOT FOUND
===================================== */

if (!$result || $result->num_rows === 0) {

    $stmt->close();
    die("Match not found.");

}

$row = $result->fetch_assoc();

$stmt->close();


/* =====================================
   ONLY LOST ITEM OWNER CAN CLAIM
===================================== */

if ((int)$row['lost_user_id'] !== $user_id) {

    die("You are not allowed to claim this item.");

}


/* =====================================
   CHECK ALREADY CLAIMED
===================================== */

if ($row['claim_status'] === 'Claimed') {

    header("Location: my-matches.php");
    exit();

}


/* =====================================
   FINDER ID
   Notification will go ONLY to finder
===================================== */

$finder_id = (int) $row['found_user_id'];


/* =====================================
   CHECK FINDER EXISTS
===================================== */

if ($finder_id <= 0) {

    die("Finder information not found.");

}


/* =====================================
   UPDATE CLAIM STATUS
===================================== */

$update_sql = "
    UPDATE matches
    SET claim_status = 'Claimed'
    WHERE match_id = ?
";


$update_stmt = $conn->prepare($update_sql);

if (!$update_stmt) {

    die("Unable to update claim status: " . $conn->error);

}

$update_stmt->bind_param("i", $match_id);

$update_success = $update_stmt->execute();

$update_stmt->close();


/* =====================================
   CLAIM STATUS UPDATE FAILED
===================================== */

if (!$update_success) {

    die("Claim request could not be submitted.");

}


/* =====================================
   MATCH PERCENTAGE
===================================== */

$percentage = number_format(
    (float)$row['match_percentage'],
    2
);


/* =====================================
   NOTIFICATION DATA
===================================== */

$title = "Claim Request";

$message =
    "Someone has claimed your found item \"" .
    $row['found_item_name'] .
    "\". Match percentage: " .
    $percentage .
    "%. Click View Claim Request to check the request.";


/* =====================================
   CHECK EXISTING NOTIFICATION
   Prevent duplicate notification
===================================== */

$check_notification_sql = "
    SELECT notification_id
    FROM notifications
    WHERE user_id = ?
      AND match_id = ?
      AND title = 'Claim Request'
    LIMIT 1
";


$check_notification_stmt =
    $conn->prepare($check_notification_sql);


if ($check_notification_stmt) {

    $check_notification_stmt->bind_param(
        "ii",
        $finder_id,
        $match_id
    );

    $check_notification_stmt->execute();

    $check_notification_result =
        $check_notification_stmt->get_result();

    $notification_exists =
        ($check_notification_result &&
         $check_notification_result->num_rows > 0);

    $check_notification_stmt->close();

} else {

    $notification_exists = false;

}


/* =====================================
   CREATE NOTIFICATION
   ONLY FINDER RECEIVES IT
===================================== */

if (!$notification_exists) {

    $notification_sql = "
        INSERT INTO notifications
        (
            user_id,
            match_id,
            title,
            message,
            is_read,
            receiver
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            0,
            'user'
        )
    ";


    $notification_stmt =
        $conn->prepare($notification_sql);


    if (!$notification_stmt) {

        die(
            "Claim submitted, but notification could not be created: "
            . $conn->error
        );

    }


    $notification_stmt->bind_param(
        "iiss",
        $finder_id,
        $match_id,
        $title,
        $message
    );


    if (!$notification_stmt->execute()) {

        $notification_stmt->close();

        die(
            "Claim submitted, but notification could not be created."
        );

    }


    $notification_stmt->close();

}


/* =====================================
   DONE
===================================== */

header("Location: my-matches.php");
exit();

?>