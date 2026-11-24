<?php

session_start();
include "db.php";

header('Content-Type: application/json');


/* ==========================================
   LOGIN CHECK
========================================== */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        'success' => false,
        'message' => 'User not logged in.'
    ]);

    exit();
}


$user_id = (int) $_SESSION['user_id'];


/* ==========================================
   GET NOTIFICATION ID
========================================== */

$notification_id =
    isset($_POST['notification_id'])
        ? (int) $_POST['notification_id']
        : 0;


if ($notification_id <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID.'
    ]);

    exit();
}


/* ==========================================
   DELETE NOTIFICATION
========================================== */

$sql = "
    DELETE FROM notifications
    WHERE notification_id = ?
    AND user_id = ?
    AND receiver = 'user'
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        'success' => false,
        'message' => 'Database error.'
    ]);

    exit();
}


$stmt->bind_param(
    "ii",
    $notification_id,
    $user_id
);


if ($stmt->execute()) {

    echo json_encode([
        'success' => true
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'Could not delete notification.'
    ]);

}


$stmt->close();
$conn->close();

?>