<?php

session_start();
include "db.php";


/* ==========================================
   USER LOGIN CHECK
========================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.html");
    exit();

}

$user_id = (int) $_SESSION['user_id'];


/* ==========================================
   GET NOTIFICATIONS
========================================== */

$sql = "
    SELECT
        n.notification_id,
        n.match_id,
        n.title,
        n.message,
        n.is_read,
        n.created_at,
        m.claim_status

    FROM notifications n

    LEFT JOIN matches m
        ON n.match_id = m.match_id

    WHERE n.user_id = ?
      AND n.receiver = 'user'

    ORDER BY n.created_at DESC,
    n.notification_id DESC

    LIMIT 20
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();


/* ==========================================
   STORE NOTIFICATIONS
========================================== */

$notifications = [];

while ($row = $result->fetch_assoc()) {

    $notifications[] = $row;

}

$stmt->close();


/* ==========================================
   UNREAD COUNT
========================================== */

$unread_sql = "
    SELECT COUNT(*) AS unread_count
    FROM notifications
    WHERE user_id = ?
      AND receiver = 'user'
      AND is_read = 0
";

$stmt = $conn->prepare($unread_sql);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$unread_result = $stmt->get_result();

$unread_data = $unread_result->fetch_assoc();

$unread_count = (int) $unread_data['unread_count'];

$stmt->close();

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Notifications</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>

</head>


<body class="bg-gray-100 min-h-screen">


<div class="max-w-4xl mx-auto p-6">


    <!-- HEADER -->

    <div class="flex
                flex-wrap
                justify-between
                items-center
                gap-4
                mb-6">


        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-3xl
                           font-bold
                           text-gray-800">

                    Notifications

                </h1>


                <?php if ($unread_count > 0): ?>

                    <span class="px-3 py-1
                                 bg-red-500
                                 text-white
                                 text-sm
                                 font-bold
                                 rounded-full">

                        <?= $unread_count ?> New

                    </span>

                <?php endif; ?>

            </div>


            <p class="text-gray-500 mt-1">

                Your latest notifications

            </p>

        </div>


        <a href="dashboard.php"
           class="px-4 py-2
                  bg-gray-800
                  text-white
                  rounded-lg
                  hover:bg-gray-700">

            ← Dashboard

        </a>

    </div>



    <?php if (empty($notifications)): ?>


        <!-- EMPTY -->

        <div class="bg-white
                    rounded-2xl
                    shadow
                    p-10
                    text-center">


            <i data-lucide="bell-off"
               class="mx-auto
                      w-12
                      h-12
                      text-gray-400">
            </i>


            <h2 class="text-xl
                       font-bold
                       text-gray-700
                       mt-4">

                No Notifications

            </h2>


            <p class="text-gray-500 mt-2">

                You don't have any notifications yet.

            </p>

        </div>


    <?php else: ?>


        <!-- NOTIFICATION LIST -->

        <div class="space-y-4">


            <?php foreach ($notifications as $n): ?>


                <?php

                $notification_id =
                    (int) $n['notification_id'];

                $match_id =
                    !empty($n['match_id'])
                    ? (int) $n['match_id']
                    : 0;

                $title =
                    $n['title'] ?? '';

                $message =
                    $n['message'] ?? '';

                $is_read =
                    (int) $n['is_read'];

                $claim_status =
                    $n['claim_status'] ?? '';

                $notification_title =
                    strtolower(
                        trim($title)
                    );

                $notification_message =
                    strtolower(
                        trim($message)
                    );

                $has_match_id =
                    $match_id > 0;

                $is_claim_notification = (

                    strpos(
                        $notification_title,
                        'claim'
                    ) !== false

                    ||

                    strpos(
                        $notification_message,
                        'claim'
                    ) !== false

                );

                ?>


                <div class="bg-white
                            rounded-2xl
                            shadow
                            p-5
                            border-l-4
                            <?= $is_read
                                ? 'border-gray-300'
                                : 'border-blue-500' ?>">


                    <div class="flex
                                items-start
                                justify-between
                                gap-4">


                        <!-- CONTENT -->

                        <div class="flex-1">


                            <div class="flex
                                        items-center
                                        gap-2">


                                <?php if (!$is_read): ?>

                                    <span class="w-2
                                                 h-2
                                                 bg-blue-500
                                                 rounded-full">
                                    </span>

                                <?php endif; ?>


                                <h3 class="font-bold
                                           text-lg
                                           text-gray-800">

                                    <?= htmlspecialchars(
                                        $title
                                    ) ?>

                                </h3>

                            </div>


                            <p class="text-gray-600 mt-2">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $message
                                    )
                                ) ?>

                            </p>


                            <p class="text-sm
                                      text-gray-400
                                      mt-3">

                                <?= htmlspecialchars(
                                    $n['created_at']
                                ) ?>

                            </p>


                            <!-- =================================
                                 CLAIM BUTTONS
                            ================================= -->

                            <?php if (
                                $has_match_id &&
                                $is_claim_notification
                            ): ?>


                                <div class="mt-4">


                                    <?php if (
                                        $claim_status === 'Pending'
                                    ): ?>


                                        <!-- PENDING -->

                                        <a href="view-claim.php?match_id=<?= $match_id ?>"
                                           class="inline-flex
                                                  items-center
                                                  gap-2
                                                  px-4
                                                  py-2
                                                  bg-blue-600
                                                  text-white
                                                  rounded-lg
                                                  hover:bg-blue-700">


                                            <i data-lucide="eye"
                                               class="w-4 h-4">
                                            </i>


                                            View Claim Request


                                        </a>


                                    <?php elseif (
                                        $claim_status === 'Approved'
                                    ): ?>


                                        <!-- APPROVED -->

                                        <a href="approved-claim-details.php?match_id=<?= $match_id ?>"
                                           class="inline-flex
                                                  items-center
                                                  gap-2
                                                  px-4
                                                  py-2
                                                  bg-green-600
                                                  text-white
                                                  rounded-lg
                                                  hover:bg-green-700">


                                            <i data-lucide="badge-check"
                                               class="w-4 h-4">
                                            </i>


                                            View Approved Details


                                        </a>


                                    <?php elseif (
                                        $claim_status === 'Rejected'
                                    ): ?>


                                        <!-- REJECTED -->

                                        <span class="inline-flex
                                                     items-center
                                                     gap-2
                                                     px-4
                                                     py-2
                                                     bg-red-100
                                                     text-red-700
                                                     rounded-lg">

                                            <i data-lucide="x-circle"
                                               class="w-4 h-4">
                                            </i>

                                            Claim Rejected

                                        </span>


                                    <?php endif; ?>


                                </div>


                            <?php endif; ?>


                        </div>



                        <!-- ACTION BUTTONS -->

                        <div class="flex
                                    flex-col
                                    gap-2">


                            <?php if (!$is_read): ?>

                                <button
                                    onclick="markAsRead(<?= $notification_id ?>)"
                                    class="p-2
                                           text-blue-600
                                           hover:bg-blue-50
                                           rounded-lg"
                                    title="Mark as read">

                                    <i data-lucide="check"
                                       class="w-5 h-5">
                                    </i>

                                </button>

                            <?php endif; ?>


                            <button
                                onclick="deleteNotification(<?= $notification_id ?>)"
                                class="p-2
                                       text-red-500
                                       hover:bg-red-50
                                       rounded-lg"
                                title="Delete">

                                <i data-lucide="trash-2"
                                   class="w-5 h-5">
                                </i>

                            </button>


                        </div>


                    </div>

                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>



<script>

lucide.createIcons();


/* ==========================================
   MARK AS READ
========================================== */

function markAsRead(notificationId) {

    fetch("mark-notification-read.php", {

        method: "POST",

        headers: {
            "Content-Type":
                "application/x-www-form-urlencoded"
        },

        body:
            "notification_id=" +
            encodeURIComponent(notificationId)

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            location.reload();

        } else {

            alert(
                data.message ||
                "Failed to mark notification as read."
            );

        }

    })

    .catch(error => {

        console.error(error);

        alert(
            "Something went wrong."
        );

    });

}


/* ==========================================
   DELETE NOTIFICATION
========================================== */

function deleteNotification(notificationId) {

    if (!confirm(
        "Are you sure you want to delete this notification?"
    )) {

        return;

    }


    fetch("delete-notification.php", {

        method: "POST",

        headers: {
            "Content-Type":
                "application/x-www-form-urlencoded"
        },

        body:
            "notification_id=" +
            encodeURIComponent(notificationId)

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            location.reload();

        } else {

            alert(
                data.message ||
                "Failed to delete notification."
            );

        }

    })

    .catch(error => {

        console.error(error);

        alert(
            "Something went wrong."
        );

    });

}

</script>


</body>
</html>