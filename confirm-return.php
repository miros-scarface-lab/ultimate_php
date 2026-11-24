<?php

session_start();
include "db.php";


/* ==============================
   LOGIN CHECK
   ============================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.html");
    exit();

}

$user_id = $_SESSION['user_id'];


/* ==============================
   POST DATA CHECK
   ============================== */

if (
    !isset($_POST['match_id']) ||
    !isset($_POST['action'])
) {

    header("Location: my-matches.php");
    exit();

}


$match_id = intval($_POST['match_id']);
$action = $_POST['action'];


/* ==============================
   GET MATCH INFORMATION
   ============================== */

$sql = "
    SELECT
        m.match_id,
        m.finder_status,
        m.owner_status,
        m.status,

        l.user_id AS lost_user_id,
        f.user_id AS found_user_id

    FROM matches m

    INNER JOIN lost_items l
        ON m.lost_id = l.lost_id

    INNER JOIN found_items f
        ON m.found_id = f.found_id

    WHERE m.match_id = ?
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $match_id
);

$stmt->execute();

$result = $stmt->get_result();


/* Match পাওয়া যায়নি */

if ($result->num_rows != 1) {

    $stmt->close();
    $conn->close();

    header("Location: my-matches.php");
    exit();

}


$row = $result->fetch_assoc();

$stmt->close();


/* ==============================
   ALREADY RETURNED
   ============================== */

if ($row['status'] == 'Returned') {

    $conn->close();

    header("Location: my-matches.php");
    exit();

}


/* ==============================
   FINDER → GIVE BACK
   ============================== */

if ($action == "given_back") {


    /* Login user অবশ্যই Finder হতে হবে */

    if ($row['found_user_id'] != $user_id) {

        die("Unauthorized action.");

    }


    /* Finder status update */

    $sql = "
        UPDATE matches
        SET finder_status = 'Give Back'
        WHERE match_id = ?
    ";


    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $match_id
    );

    $stmt->execute();

    $stmt->close();


}


/* ==============================
   OWNER → RECEIVED
   ============================== */

elseif ($action == "received") {


    /* Login user অবশ্যই Owner হতে হবে */

    if ($row['lost_user_id'] != $user_id) {

        die("Unauthorized action.");

    }


    /* Owner status update */

    $sql = "
        UPDATE matches
        SET owner_status = 'Received'
        WHERE match_id = ?
    ";


    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $match_id
    );

    $stmt->execute();

    $stmt->close();


}


/* ==============================
   INVALID ACTION
   ============================== */

else {

    $conn->close();

    header("Location: my-matches.php");
    exit();

}


/* ==============================
   CHECK BOTH STATUS
   ============================== */

$sql = "
    SELECT
        finder_status,
        owner_status
    FROM matches
    WHERE match_id = ?
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $match_id
);

$stmt->execute();

$result = $stmt->get_result();

$match = $result->fetch_assoc();

$stmt->close();


/* ==============================
   BOTH CONFIRMED
   ============================== */

if (
    $match['finder_status'] == 'Give Back' &&
    $match['owner_status'] == 'Received'
) {


    /*
        দুজনই confirm করেছে।
        এখন Match Returned হবে।
    */

    $sql = "
        UPDATE matches
        SET status = 'Returned'
        WHERE match_id = ?
    ";


    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $match_id
    );

    $stmt->execute();

    $stmt->close();

}


/* ==============================
   FINISH
   ============================== */

$conn->close();

header("Location: my-matches.php");
exit();

?>