<?php

session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        'success' => false,
        'message' => 'User not logged in.'
    ]);

    exit();
}

$user_id = (int) $_SESSION['user_id'];

$notification_id = isset($_POST['notification_id'])
    ? (int) $_POST['notification_id']
    : 0;

if ($notification_id <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID.'
    ]);

    exit();
}

$sql = "
    UPDATE notifications
    SET is_read = 1
    WHERE notification_id = ?
    AND user_id = ?
    AND receiver = 'user'
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);

    exit();
}

$stmt->bind_param(
    "ii",
    $notification_id,
    $user_id
);

$stmt->execute();

if ($stmt->affected_rows > 0) {

    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read.'
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'No notification was updated. Check notification ID, user ID or receiver.'
    ]);
}

$stmt->close();
$conn->close();

?>