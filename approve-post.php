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


/* =========================================================
   FUNCTION: TEXT SIMILARITY
========================================================= */

function getSimilarity($text1, $text2)
{
    $text1 = strtolower(trim($text1 ?? ''));
    $text2 = strtolower(trim($text2 ?? ''));

    if ($text1 === '' || $text2 === '') {
        return 0;
    }

    similar_text($text1, $text2, $percent);

    return round($percent, 2);
}


/* =========================================================
   FUNCTION: CREATE MATCH
========================================================= */

function createMatch(
    $conn,
    $lost_id,
    $found_id,
    $item_name_score,
    $description_score,
    $category_score,
    $location_score,
    $date_score,
    $match_percentage
) {

    /* ==============================
       CHECK DUPLICATE MATCH
    ============================== */

    $check_sql = "
        SELECT match_id
        FROM matches
        WHERE lost_id = ?
        AND found_id = ?
        LIMIT 1
    ";

    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        die("Match Check Error: " . $conn->error);
    }

    $check_stmt->bind_param(
        "ii",
        $lost_id,
        $found_id
    );

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();

    $check_stmt->close();


    /* Already exists */

    if ($check_result->num_rows > 0) {
        return false;
    }


    /* ==============================
       INSERT MATCH
    ============================== */

    $insert_sql = "
        INSERT INTO matches
        (
            lost_id,
            found_id,
            item_name_score,
            description_score,
            category_score,
            location_score,
            date_score,
            match_percentage,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'Pending'
        )
    ";

    $insert_stmt = $conn->prepare($insert_sql);

    if (!$insert_stmt) {
        die("Match Insert Prepare Error: " . $conn->error);
    }


    /*
       2 integer
       6 decimal
    */

    $insert_stmt->bind_param(
        "iidddddd",
        $lost_id,
        $found_id,
        $item_name_score,
        $description_score,
        $category_score,
        $location_score,
        $date_score,
        $match_percentage
    );


    if (!$insert_stmt->execute()) {
        die("Match Insert Error: " . $insert_stmt->error);
    }

    $insert_stmt->close();

    return true;
}


/* =========================================================
   FUNCTION: CREATE MATCH NOTIFICATION
========================================================= */

function createMatchNotification(
    $conn,
    $user_id,
    $match_id,
    $title,
    $message
) {

    /* ==============================
       CHECK DUPLICATE NOTIFICATION
    ============================== */

    $check_sql = "
        SELECT notification_id
        FROM notifications
        WHERE user_id = ?
        AND match_id = ?
        AND title = ?
        LIMIT 1
    ";

    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        die("Notification Check Error: " . $conn->error);
    }

    $check_stmt->bind_param(
        "iis",
        $user_id,
        $match_id,
        $title
    );

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();

    $check_stmt->close();


    /* Already exists */

    if ($check_result->num_rows > 0) {
        return;
    }


    /* ==============================
       INSERT NOTIFICATION
    ============================== */

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
        die("Notification Error: " . $conn->error);
    }


    $notification_stmt->bind_param(
        "iiss",
        $user_id,
        $match_id,
        $title,
        $message
    );


    if (!$notification_stmt->execute()) {
        die(
            "Notification Insert Error: " .
            $notification_stmt->error
        );
    }

    $notification_stmt->close();
}


/* =========================================================
   APPROVE LOST POST
========================================================= */

if ($type == "lost") {

    /* ==============================
       GET LOST ITEM
    ============================== */

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


    /* ==============================
       UPDATE STATUS
    ============================== */

    $sql = "
        UPDATE lost_items
        SET status = 'Approved'
        WHERE lost_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Update Error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("Approve Error: " . $stmt->error);
    }

    $stmt->close();


    /* =====================================================
       OWNER: POST APPROVED NOTIFICATION
    ===================================================== */

    $user_id = intval($lost['user_id']);

    $title = "Post Approved";

    $message =
        "Your lost item post '" .
        $lost['item_name'] .
        "' has been approved by the admin.";


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

    $notification_stmt =
        $conn->prepare($notification_sql);

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
        die(
            "Notification Insert Error: " .
            $notification_stmt->error
        );
    }

    $notification_stmt->close();


    /* =====================================================
       FIND APPROVED FOUND ITEMS
    ===================================================== */

    $found_sql = "
        SELECT *
        FROM found_items
        WHERE status = 'Approved'
    ";

    $found_result = $conn->query($found_sql);

    if (!$found_result) {
        die(
            "Found Items Query Error: " .
            $conn->error
        );
    }


    /* =====================================================
       COMPARE LOST WITH EVERY APPROVED FOUND ITEM
    ===================================================== */

    while ($found = $found_result->fetch_assoc()) {


        /* ==============================
           ITEM NAME SCORE
        ============================== */

        $item_name_score = getSimilarity(
            $lost['item_name'],
            $found['item_name']
        );


        /* ==============================
           DESCRIPTION SCORE
        ============================== */

        $description_score = getSimilarity(
            $lost['description'],
            $found['description']
        );


        /* ==============================
           CATEGORY SCORE
        ============================== */

        $category_score = getSimilarity(
            $lost['category'],
            $found['category']
        );


        /* ==============================
           LOCATION SCORE
        ============================== */

        $location_score = getSimilarity(
            $lost['lost_location'],
            $found['found_location']
        );


        /* ==============================
           DATE SCORE
        ============================== */

        $date_score = getSimilarity(
            $lost['lost_date'],
            $found['found_date']
        );


        /* =================================================
           WEIGHTED MATCH PERCENTAGE

           Item Name       = 20%
           Description     = 40%
           Category        = 10%
           Location        = 20%
           Date            = 10%

           TOTAL           = 100%
        ================================================= */

        $match_percentage =
            ($item_name_score * 0.20) +
            ($description_score * 0.40) +
            ($category_score * 0.10) +
            ($location_score * 0.20) +
            ($date_score * 0.10);


        $match_percentage =
            round($match_percentage, 2);


        /* =================================================
           ONLY 80% OR ABOVE
        ================================================= */

        if ($match_percentage >= 80) {


            /* ==============================
               CREATE MATCH
            ============================== */

            $new_match = createMatch(
                $conn,
                $lost['lost_id'],
                $found['found_id'],
                $item_name_score,
                $description_score,
                $category_score,
                $location_score,
                $date_score,
                $match_percentage
            );


            /* ==============================
               GET MATCH ID
            ============================== */

            $match_sql = "
                SELECT match_id
                FROM matches
                WHERE lost_id = ?
                AND found_id = ?
                LIMIT 1
            ";

            $match_stmt =
                $conn->prepare($match_sql);

            if (!$match_stmt) {
                die(
                    "Match ID Error: " .
                    $conn->error
                );
            }

            $match_stmt->bind_param(
                "ii",
                $lost['lost_id'],
                $found['found_id']
            );

            $match_stmt->execute();

            $match_result =
                $match_stmt->get_result();

            $match_row =
                $match_result->fetch_assoc();

            $match_stmt->close();


            /* ==============================
               SEND NOTIFICATIONS
            ============================== */

            if ($match_row) {

                $match_id =
                    intval($match_row['match_id']);


                /* OWNER */

                createMatchNotification(
                    $conn,
                    intval($lost['user_id']),
                    $match_id,
                    "Possible Match Found",
                    "A possible match was found for your lost item '" .
                    $lost['item_name'] .
                    "'. Match percentage: " .
                    number_format(
                        $match_percentage,
                        2
                    ) .
                    "%."
                );


                /* FINDER */

                createMatchNotification(
                    $conn,
                    intval($found['user_id']),
                    $match_id,
                    "Possible Match Found",
                    "A possible match was found for the item you found '" .
                    $found['item_name'] .
                    "'. Match percentage: " .
                    number_format(
                        $match_percentage,
                        2
                    ) .
                    "%."
                );
            }
        }
    }


    /* ==============================
       BACK TO PENDING POSTS
    ============================== */

    header("Location: pending-posts.php");
    exit();
}


/* =========================================================
   APPROVE FOUND POST
========================================================= */

elseif ($type == "found") {

    /* ==============================
       GET FOUND ITEM
    ============================== */

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


    /* ==============================
       UPDATE STATUS
    ============================== */

    $sql = "
        UPDATE found_items
        SET status = 'Approved'
        WHERE found_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Update Error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("Approve Error: " . $stmt->error);
    }

    $stmt->close();


    /* =====================================================
       FINDER: POST APPROVED NOTIFICATION
    ===================================================== */

    $user_id = intval($found['user_id']);

    $title = "Post Approved";

    $message =
        "Your found item post '" .
        $found['item_name'] .
        "' has been approved by the admin.";


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

    $notification_stmt =
        $conn->prepare($notification_sql);

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
        die(
            "Notification Insert Error: " .
            $notification_stmt->error
        );
    }

    $notification_stmt->close();


    /* =====================================================
       FIND APPROVED LOST ITEMS
    ===================================================== */

    $lost_sql = "
        SELECT *
        FROM lost_items
        WHERE status = 'Approved'
    ";

    $lost_result = $conn->query($lost_sql);

    if (!$lost_result) {
        die(
            "Lost Items Query Error: " .
            $conn->error
        );
    }


    /* =====================================================
       COMPARE FOUND WITH EVERY APPROVED LOST ITEM
    ===================================================== */

    while ($lost = $lost_result->fetch_assoc()) {


        /* ==============================
           ITEM NAME SCORE
        ============================== */

        $item_name_score = getSimilarity(
            $lost['item_name'],
            $found['item_name']
        );


        /* ==============================
           DESCRIPTION SCORE
        ============================== */

        $description_score = getSimilarity(
            $lost['description'],
            $found['description']
        );


        /* ==============================
           CATEGORY SCORE
        ============================== */

        $category_score = getSimilarity(
            $lost['category'],
            $found['category']
        );


        /* ==============================
           LOCATION SCORE
        ============================== */

        $location_score = getSimilarity(
            $lost['lost_location'],
            $found['found_location']
        );


        /* ==============================
           DATE SCORE
        ============================== */

        $date_score = getSimilarity(
            $lost['lost_date'],
            $found['found_date']
        );


        /* =================================================
           WEIGHTED MATCH PERCENTAGE
        ================================================= */

        $match_percentage =
            ($item_name_score * 0.20) +
            ($description_score * 0.40) +
            ($category_score * 0.10) +
            ($location_score * 0.20) +
            ($date_score * 0.10);


        $match_percentage =
            round($match_percentage, 2);


        /* =================================================
           ONLY 80% OR ABOVE
        ================================================= */

        if ($match_percentage >= 80) {


            /* ==============================
               CREATE MATCH
            ============================== */

            $new_match = createMatch(
                $conn,
                $lost['lost_id'],
                $found['found_id'],
                $item_name_score,
                $description_score,
                $category_score,
                $location_score,
                $date_score,
                $match_percentage
            );


            /* ==============================
               GET MATCH ID
            ============================== */

            $match_sql = "
                SELECT match_id
                FROM matches
                WHERE lost_id = ?
                AND found_id = ?
                LIMIT 1
            ";

            $match_stmt =
                $conn->prepare($match_sql);

            if (!$match_stmt) {
                die(
                    "Match ID Error: " .
                    $conn->error
                );
            }

            $match_stmt->bind_param(
                "ii",
                $lost['lost_id'],
                $found['found_id']
            );

            $match_stmt->execute();

            $match_result =
                $match_stmt->get_result();

            $match_row =
                $match_result->fetch_assoc();

            $match_stmt->close();


            /* ==============================
               SEND NOTIFICATIONS
            ============================== */

            if ($match_row) {

                $match_id =
                    intval($match_row['match_id']);


                /* OWNER */

                createMatchNotification(
                    $conn,
                    intval($lost['user_id']),
                    $match_id,
                    "Possible Match Found",
                    "A possible match was found for your lost item '" .
                    $lost['item_name'] .
                    "'. Match percentage: " .
                    number_format(
                        $match_percentage,
                        2
                    ) .
                    "%."
                );


                /* FINDER */

                createMatchNotification(
                    $conn,
                    intval($found['user_id']),
                    $match_id,
                    "Possible Match Found",
                    "A possible match was found for the item you found '" .
                    $found['item_name'] .
                    "'. Match percentage: " .
                    number_format(
                        $match_percentage,
                        2
                    ) .
                    "%."
                );
            }
        }
    }


    /* ==============================
       BACK TO PENDING POSTS
    ============================== */

    header("Location: pending-posts.php");
    exit();
}


/* =========================================================
   INVALID TYPE
========================================================= */

else {

    die("Invalid post type.");
}


$conn->close();

?>