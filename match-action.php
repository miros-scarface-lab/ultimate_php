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
   CHECK POST DATA
===================================== */

if (
    !isset($_POST['match_id']) ||
    !isset($_POST['action'])
) {

    header("Location: my-matches.php");
    exit();

}

$match_id = (int) $_POST['match_id'];
$action = trim($_POST['action']);


/* =====================================
   ALLOWED ACTIONS
===================================== */

$allowed_actions = array(
    'claim',
    'given_back',
    'received'
);

if (!in_array($action, $allowed_actions, true)) {

    header("Location: my-matches.php");
    exit();

}


/* =====================================
   GET MATCH INFORMATION
===================================== */

$sql = "
    SELECT
        m.match_id,
        m.lost_id,
        m.found_id,
        m.status,
        m.claim_status,
        m.finder_status,
        m.owner_status,
        m.match_percentage,

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
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die("Database error: " . htmlspecialchars($conn->error));

}

$stmt->bind_param("i", $match_id);

$stmt->execute();

$result = $stmt->get_result();


/* =====================================
   MATCH NOT FOUND
===================================== */

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    die("Match not found.");

}

$match = $result->fetch_assoc();

$stmt->close();


/* =====================================
   BASIC STATUS
===================================== */

$claim_status = trim($match['claim_status'] ?? '');

if ($claim_status === '') {
    $claim_status = 'Not Claimed';
}


/* =====================================
   CLAIM
   OWNER → CLAIM
===================================== */

if ($action === 'claim') {


    /* ================================
       ONLY OWNER CAN CLAIM
    ================================= */

    if (
        (int) $match['lost_user_id']
        !== $user_id
    ) {

        $conn->close();

        die("Only the owner can claim this item.");

    }


    /* ================================
       CHECK CLAIM ALREADY EXISTS
    ================================= */

    if (
        $claim_status !== 'Not Claimed' &&
        $claim_status !== ''
    ) {

        $conn->close();

        die("This match has already been claimed.");

    }


    /* ================================
       UPDATE CLAIM STATUS
    ================================= */

    $sql = "
        UPDATE matches
        SET claim_status = 'Pending'
        WHERE match_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die("Database error: " . htmlspecialchars($conn->error));

    }

    $stmt->bind_param("i", $match_id);

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->close();

        die("Failed to submit claim request.");

    }

    $stmt->close();


    /* ================================
       FINDER NOTIFICATION
    ================================= */

    $finder_id =
        (int) $match['found_user_id'];

    $item_name =
        $match['lost_item_name'];

    $title =
        "New Claim Request";

    $message =
        "The owner has claimed \"$item_name\". Please review the claim request.";


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

    $notif_stmt =
        $conn->prepare($notif_sql);

    if ($notif_stmt) {

        $notif_stmt->bind_param(
            "iiss",
            $finder_id,
            $match_id,
            $title,
            $message
        );

        $notif_stmt->execute();

        $notif_stmt->close();

    }


    $conn->close();

    header("Location: my-matches.php");
    exit();

}


/* =====================================
   GIVE BACK / RECEIVE
===================================== */

/*
   Give Back / Receive
   শুধু Claim Approved হওয়ার পরে করা যাবে।
*/

if ($claim_status !== 'Approved') {

    $conn->close();

    die(
        "Give Back / Receive is available only after the claim is approved."
    );

}


/* =====================================
   CHECK ALREADY RETURNED
===================================== */

if ($match['status'] === 'Returned') {

    $conn->close();

    die("This item has already been returned.");

}


/* =====================================
   FINDER → GIVE BACK
===================================== */

if ($action === 'given_back') {


    /* ================================
       CHECK FINDER
    ================================= */

    if (
        (int) $match['found_user_id']
        !== $user_id
    ) {

        $conn->close();

        die("Unauthorized action.");

    }


    /* ================================
       CHECK ALREADY DONE
    ================================= */

    if (
        trim($match['finder_status'] ?? '')
        === 'Give Back'
    ) {

        $conn->close();

        header("Location: my-matches.php");
        exit();

    }


    /* ================================
       UPDATE FINDER STATUS
    ================================= */

    $sql = "
        UPDATE matches
        SET finder_status = 'Give Back'
        WHERE match_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die("Database error: " . htmlspecialchars($conn->error));

    }

    $stmt->bind_param("i", $match_id);

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->close();

        die("Failed to update Give Back status.");

    }

    $stmt->close();


    /* ================================
       OWNER NOTIFICATION
    ================================= */

    $owner_id =
        (int) $match['lost_user_id'];

    $item_name =
        $match['lost_item_name'];

    $title =
        "Finder Confirmed Give Back";

    $message =
        "The finder has confirmed Give Back for \"$item_name\". Please confirm receipt by clicking Receive.";


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

    $notif_stmt =
        $conn->prepare($notif_sql);

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


/* =====================================
   OWNER → RECEIVE
===================================== */

elseif ($action === 'received') {


    /* ================================
       CHECK OWNER
    ================================= */

    if (
        (int) $match['lost_user_id']
        !== $user_id
    ) {

        $conn->close();

        die("Unauthorized action.");

    }


    /* ================================
       CHECK FINDER GIVE BACK
    ================================= */

    if (
        trim($match['finder_status'] ?? '')
        !== 'Give Back'
    ) {

        $conn->close();

        die(
            "The finder must confirm Give Back before you can confirm Receive."
        );

    }


    /* ================================
       CHECK ALREADY RECEIVED
    ================================= */

    if (
        trim($match['owner_status'] ?? '')
        === 'Received'
    ) {

        $conn->close();

        header("Location: my-matches.php");
        exit();

    }


    /* ================================
       UPDATE OWNER STATUS
    ================================= */

    $sql = "
        UPDATE matches
        SET owner_status = 'Received'
        WHERE match_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die("Database error: " . htmlspecialchars($conn->error));

    }

    $stmt->bind_param("i", $match_id);

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->close();

        die("Failed to update Receive status.");

    }

    $stmt->close();


    /* ================================
       FINDER NOTIFICATION
    ================================= */

    $finder_id =
        (int) $match['found_user_id'];

    $item_name =
        $match['found_item_name'];

    $title =
        "Item Received";

    $message =
        "The owner has confirmed that \"$item_name\" has been received.";


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

    $notif_stmt =
        $conn->prepare($notif_sql);

    if ($notif_stmt) {

        $notif_stmt->bind_param(
            "iiss",
            $finder_id,
            $match_id,
            $title,
            $message
        );

        $notif_stmt->execute();

        $notif_stmt->close();

    }

}


/* =====================================
   GET UPDATED MATCH
===================================== */

$sql = "
    SELECT
        finder_status,
        owner_status,
        status,
        lost_id,
        found_id
    FROM matches
    WHERE match_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die("Database error: " . htmlspecialchars($conn->error));

}

$stmt->bind_param("i", $match_id);

$stmt->execute();

$result = $stmt->get_result();

$updated_match = $result->fetch_assoc();

$stmt->close();


/* =====================================
   BOTH CONFIRM
===================================== */

if (
    trim($updated_match['finder_status'] ?? '')
    === 'Give Back'

    &&

    trim($updated_match['owner_status'] ?? '')
    === 'Received'
) {


    /* ================================
       MATCH → RETURNED
    ================================= */

    $sql = "
        UPDATE matches
        SET status = 'Returned'
        WHERE match_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $stmt->close();

    }


    /* ================================
       LOST ITEM → RETURNED
    ================================= */

    $sql = "
        UPDATE lost_items
        SET status = 'Returned'
        WHERE lost_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $updated_match['lost_id']
        );

        $stmt->execute();
        $stmt->close();

    }


    /* ================================
       FOUND ITEM → RETURNED
    ================================= */

    $sql = "
        UPDATE found_items
        SET status = 'Returned'
        WHERE found_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $updated_match['found_id']
        );

        $stmt->execute();
        $stmt->close();

    }


    /* ================================
       FINAL NOTIFICATION
    ================================= */

    $owner_id =
        (int) $match['lost_user_id'];

    $finder_id =
        (int) $match['found_user_id'];

    $title =
        "Item Successfully Returned";

    $message =
        "The Give Back and Receive process has been completed. The item has been marked as Returned.";


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


    /* ================================
       OWNER NOTIFICATION
    ================================= */

    $notif_stmt =
        $conn->prepare($notif_sql);

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


    /* ================================
       FINDER NOTIFICATION
    ================================= */

    $notif_stmt =
        $conn->prepare($notif_sql);

    if ($notif_stmt) {

        $notif_stmt->bind_param(
            "iiss",
            $finder_id,
            $match_id,
            $title,
            $message
        );

        $notif_stmt->execute();
        $notif_stmt->close();

    }

}


/* =====================================
   CLOSE DATABASE
===================================== */

$conn->close();


/* =====================================
   BACK TO MY MATCHES
===================================== */

header("Location: my-matches.php");
exit();

?>