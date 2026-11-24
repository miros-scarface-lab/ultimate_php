<?php

session_start();
include "db.php";

/* ==============================
   ADMIN LOGIN CHECK
============================== */

if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}

/* ==============================
   GET ID + TYPE
============================== */

requirePostWithCsrf();

if (!isset($_POST['id']) || !isset($_POST['type'])) {
    die("Invalid request.");
}

$id = intval($_POST['id']);
$type = $_POST['type'];


/* ==============================
   REJECT LOST POST
============================== */

if ($type == "lost") {

    /* GET LOST ITEM */

    $sql = "
        SELECT *
        FROM lost_items
        WHERE lost_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Select Error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $lost = $result->fetch_assoc();

    $stmt->close();

    if (!$lost) {
        die("Lost item not found.");
    }


    /* UPDATE STATUS */

    $sql = "
        UPDATE lost_items
        SET status = 'Rejected'
        WHERE lost_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Update Error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("Reject Error: " . $stmt->error);
    }

    $stmt->close();


    /* ==============================
       OWNER NOTIFICATION
    ============================== */

    $user_id = intval($lost['user_id']);

    $title = "Post Rejected";

    $message =
        "Your lost item post '" .
        $lost['item_name'] .
        "' has been rejected by the admin.";


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
            NULL,
            ?,
            ?,
            0,
            'user'
        )
    ";

    $notification_stmt = $conn->prepare($notification_sql);

    if (!$notification_stmt) {
        die("Notification Error: " . $conn->error);
    }

    $notification_stmt->bind_param(
        "iss",
        $user_id,
        $title,
        $message
    );

    if (!$notification_stmt->execute()) {
        die("Notification Error: " . $notification_stmt->error);
    }

    $notification_stmt->close();


    header("Location: pending-posts.php");
    exit();
}


/* ==============================
   REJECT FOUND POST
============================== */

elseif ($type == "found") {

    /* GET FOUND ITEM */

    $sql = "
        SELECT *
        FROM found_items
        WHERE found_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Select Error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $found = $result->fetch_assoc();

    $stmt->close();

    if (!$found) {
        die("Found item not found.");
    }


    /* UPDATE STATUS */

    $sql = "
        UPDATE found_items
        SET status = 'Rejected'
        WHERE found_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Update Error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("Reject Error: " . $stmt->error);
    }

    $stmt->close();


    /* ==============================
       FINDER NOTIFICATION
    ============================== */

    $user_id = intval($found['user_id']);

    $title = "Post Rejected";

    $message =
        "Your found item post '" .
        $found['item_name'] .
        "' has been rejected by the admin.";


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
            NULL,
            ?,
            ?,
            0,
            'user'
        )
    ";

    $notification_stmt = $conn->prepare($notification_sql);

    if (!$notification_stmt) {
        die("Notification Error: " . $conn->error);
    }

    $notification_stmt->bind_param(
        "iss",
        $user_id,
        $title,
        $message
    );

    if (!$notification_stmt->execute()) {
        die("Notification Error: " . $notification_stmt->error);
    }

    $notification_stmt->close();


    header("Location: pending-posts.php");
    exit();
}


/* ==============================
   INVALID TYPE
============================== */

else {

    die("Invalid post type.");
}

$conn->close();

?>