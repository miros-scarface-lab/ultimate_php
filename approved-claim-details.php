<?php

session_start();
include "db.php";


/* ==========================================
   GET MATCH ID
========================================== */

if (!isset($_GET['match_id']) || !is_numeric($_GET['match_id'])) {
    die("Invalid match ID.");
}

$match_id = (int) $_GET['match_id'];


/* ==========================================
   LOGIN STATUS
========================================== */

$is_admin = isset($_SESSION['admin_username']);

$is_user = isset($_SESSION['user_id']);

$user_id = $is_user
    ? (int) $_SESSION['user_id']
    : 0;


/* ==========================================
   LOGIN CHECK
========================================== */

if (!$is_admin && !$is_user) {

    header("Location: login.html");
    exit();

}


/* ==========================================
   GET COMPLETE APPROVED CLAIM
========================================== */

$sql = "
    SELECT

        /* MATCH */
        m.match_id,
        m.lost_id,
        m.found_id,
        m.match_percentage,
        m.status,
        m.finder_status,
        m.owner_status,
        m.claim_status,
        m.created_at,

        /* LOST ITEM */
        l.user_id AS owner_id,
        l.item_name AS lost_item_name,
        l.category AS lost_category,
        l.description AS lost_description,
        l.lost_date,
        l.lost_location,
        l.contact_information AS lost_contact_information,
        l.secret_information AS lost_secret_information,
        l.item_image AS lost_item_image,

        /* OWNER */
        owner.full_name AS owner_name,
        owner.student_id AS owner_student_id,
        owner.email AS owner_email,
        owner.department AS owner_department,

        /* FOUND ITEM */
        f.user_id AS finder_id,
        f.item_name AS found_item_name,
        f.category AS found_category,
        f.description AS found_description,
        f.found_date,
        f.found_location,
        f.finder_contact_information,

        /* FINDER */
        finder.full_name AS finder_name,
        finder.student_id AS finder_student_id,
        finder.email AS finder_email,
        finder.department AS finder_department

    FROM matches m

    INNER JOIN lost_items l
        ON m.lost_id = l.lost_id

    INNER JOIN found_items f
        ON m.found_id = f.found_id

    LEFT JOIN users owner
        ON l.user_id = owner.user_id

    LEFT JOIN users finder
        ON f.user_id = finder.user_id

    WHERE m.match_id = ?
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $match_id);

$stmt->execute();

$result = $stmt->get_result();


/* ==========================================
   MATCH CHECK
========================================== */

if ($result->num_rows === 0) {

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            margin-top:100px;
        '>

            <h2 style='color:red;'>
                Match Not Found
            </h2>

            <p>
                The requested match does not exist.
            </p>

        </div>
    ");

}


$match = $result->fetch_assoc();

$stmt->close();


/* ==========================================
   APPROVED STATUS CHECK
========================================== */

if ($match['claim_status'] !== 'Approved') {

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            margin-top:100px;
        '>

            <h2 style='color:#d97706;'>
                Not Available
            </h2>

            <p>
                This claim has not been approved yet.
            </p>

        </div>
    ");

}


/* ==========================================
   ROLE BASED ACCESS CONTROL
========================================== */

$owner_id = (int) $match['owner_id'];

$finder_id = (int) $match['finder_id'];


$is_owner = (
    $is_user &&
    $user_id === $owner_id
);


$is_finder = (
    $is_user &&
    $user_id === $finder_id
);


/*
   ONLY:
   1. ADMIN
   2. OWNER
   3. FINDER
*/

if (!$is_admin && !$is_owner && !$is_finder) {

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            margin-top:100px;
        '>

            <h2 style='color:red;'>
                Access Denied
            </h2>

            <p>
                You are not authorized to view these approved claim details.
            </p>

        </div>
    ");

}


/* ==========================================
   LOST ITEM IMAGE PATH
========================================== */

$lost_image = trim($match['lost_item_image'] ?? '');

$image_file = '';

if ($lost_image !== '') {

    $image_file = __DIR__ . '/' . ltrim($lost_image, '/');

}


/* ==========================================
   BACK LINK
========================================== */

if ($is_admin) {

    $back_link = "admin-dashboard.php";

} else {

    $back_link = "notifications.php";

}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Approved Claim Details</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>

</head>


<body class="bg-gray-100 min-h-screen">


<div class="max-w-6xl mx-auto p-6">


    <!-- HEADER -->

    <div class="flex flex-wrap
                justify-between
                items-center
                gap-4
                mb-6">


        <div>

            <div class="flex items-center gap-2">

                <i data-lucide="badge-check"
                   class="text-green-600">
                </i>

                <h1 class="text-3xl font-bold text-gray-800">

                    Approved Claim Details

                </h1>

            </div>


            <p class="text-gray-500 mt-1">

                Match #<?= $match_id ?>

            </p>

        </div>


        <a href="<?= $back_link ?>"
           class="px-4 py-2
                  bg-gray-800
                  text-white
                  rounded-lg
                  hover:bg-gray-700">

            ← Back

        </a>

    </div>



    <!-- APPROVED NOTICE -->

    <div class="bg-green-50
                border border-green-300
                rounded-2xl
                p-5
                mb-6">

        <div class="flex items-start gap-3">

            <i data-lucide="check-circle"
               class="text-green-600 mt-1">
            </i>

            <div>

                <h2 class="font-bold
                           text-green-700
                           text-lg">

                    Claim Approved

                </h2>

                <p class="text-gray-700 mt-1">

                    This claim has been approved by the finder.

                </p>

            </div>

        </div>

    </div>



    <!-- MATCH SUMMARY -->

    <div class="bg-white
                rounded-2xl
                shadow
                p-6
                mb-6">


        <h2 class="text-xl
                   font-bold
                   mb-5">

            Match Summary

        </h2>


        <div class="grid md:grid-cols-4 gap-4">


            <div class="bg-gray-50
                        rounded-xl
                        p-4">

                <p class="text-gray-500 text-sm">
                    Match ID
                </p>

                <p class="font-bold text-lg">
                    #<?= $match_id ?>
                </p>

            </div>


            <div class="bg-gray-50
                        rounded-xl
                        p-4">

                <p class="text-gray-500 text-sm">
                    Match Percentage
                </p>

                <p class="font-bold
                          text-lg
                          text-blue-600">

                    <?= htmlspecialchars(
                        $match['match_percentage']
                    ) ?>%

                </p>

            </div>


            <div class="bg-gray-50
                        rounded-xl
                        p-4">

                <p class="text-gray-500 text-sm">
                    Match Status
                </p>

                <p class="font-bold">
                    <?= htmlspecialchars(
                        $match['status']
                    ) ?>
                </p>

            </div>


            <div class="bg-gray-50
                        rounded-xl
                        p-4">

                <p class="text-gray-500 text-sm">
                    Claim Status
                </p>

                <span class="inline-block
                             mt-1
                             px-3
                             py-1
                             bg-green-100
                             text-green-700
                             rounded-full
                             font-semibold">

                    Approved

                </span>

            </div>

        </div>

    </div>



    <!-- ==========================================
         LOST ITEM
    =========================================== -->

    <div class="bg-white
                rounded-2xl
                shadow
                p-6
                mb-6">


        <h2 class="text-xl
                   font-bold
                   text-red-600
                   mb-5">

            Lost Item

        </h2>


        <!-- ======================================
             LOST ITEM IMAGE
        ======================================= -->

        <?php if ($lost_image !== '' && file_exists($image_file)): ?>

            <div class="mb-6">

                <p class="text-gray-500
                          text-sm
                          font-medium
                          mb-2">

                    Lost Item Image

                </p>


                <div class="w-52 h-40
                            bg-gray-50
                            border border-gray-200
                            rounded-xl
                            overflow-hidden
                            flex
                            items-center
                            justify-center">

                    <img
                        src="<?= htmlspecialchars($lost_image) ?>"
                        alt="<?= htmlspecialchars($match['lost_item_name']) ?>"
                        class="max-w-full
                               max-h-full
                               object-contain
                               p-2"
                    >

                </div>

            </div>

        <?php endif; ?>


        <!-- ======================================
             LOST ITEM INFORMATION
        ======================================= -->

        <div class="grid md:grid-cols-2 gap-5">


            <!-- ITEM NAME -->

            <div>

                <p class="text-gray-500 text-sm">
                    Item Name
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['lost_item_name']
                    ) ?>

                </p>

            </div>



            <!-- CATEGORY -->

            <div>

                <p class="text-gray-500 text-sm">
                    Category
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['lost_category']
                    ) ?>

                </p>

            </div>



            <!-- LOST DATE -->

            <div>

                <p class="text-gray-500 text-sm">
                    Lost Date
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['lost_date']
                    ) ?>

                </p>

            </div>



            <!-- LOST LOCATION -->

            <div>

                <p class="text-gray-500 text-sm">
                    Lost Location
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['lost_location']
                    ) ?>

                </p>

            </div>



            <!-- DESCRIPTION -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Description
                </p>

                <p class="font-semibold">

                    <?= nl2br(
                        htmlspecialchars(
                            $match['lost_description']
                        )
                    ) ?>

                </p>

            </div>



            <!-- OWNER CONTACT -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Owner Contact
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['lost_contact_information']
                    ) ?>

                </p>

            </div>



            <!-- SECRET INFORMATION -->

            <div class="md:col-span-2
                        bg-yellow-50
                        border border-yellow-300
                        rounded-xl
                        p-5">

                <div class="flex items-center gap-2 mb-2">

                    <i data-lucide="lock"
                       class="text-yellow-700">
                    </i>

                    <h3 class="font-bold
                               text-yellow-700">

                        Secret Information

                    </h3>

                </div>


                <p class="text-gray-800">

                    <?= nl2br(
                        htmlspecialchars(
                            $match['lost_secret_information']
                        )
                    ) ?>

                </p>

            </div>

        </div>

    </div>



    <!-- ==========================================
         OWNER
    =========================================== -->

    <div class="bg-white
                rounded-2xl
                shadow
                p-6
                mb-6">


        <h2 class="text-xl
                   font-bold
                   mb-5">

            Owner Information

        </h2>


        <div class="grid md:grid-cols-2 gap-5">


            <div>

                <p class="text-gray-500 text-sm">
                    Full Name
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['owner_name']
                    ) ?>

                </p>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Student ID
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['owner_student_id']
                    ) ?>

                </p>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Email
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['owner_email']
                    ) ?>

                </p>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Department
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['owner_department']
                    ) ?>

                </p>

            </div>

        </div>

    </div>



    <!-- ==========================================
         FOUND ITEM
    =========================================== -->

    <div class="bg-white
                rounded-2xl
                shadow
                p-6
                mb-6">


        <h2 class="text-xl
                   font-bold
                   text-green-600
                   mb-5">

            Found Item

        </h2>


        <div class="grid md:grid-cols-2 gap-5">


            <!-- ITEM NAME -->

            <div>

                <p class="text-gray-500 text-sm">
                    Item Name
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['found_item_name']
                    ) ?>

                </p>

            </div>



            <!-- CATEGORY -->

            <div>

                <p class="text-gray-500 text-sm">
                    Category
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['found_category']
                    ) ?>

                </p>

            </div>



            <!-- FOUND DATE -->

            <div>

                <p class="text-gray-500 text-sm">
                    Found Date
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['found_date']
                    ) ?>

                </p>

            </div>



            <!-- FOUND LOCATION -->

            <div>

                <p class="text-gray-500 text-sm">
                    Found Location
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['found_location']
                    ) ?>

                </p>

            </div>



            <!-- DESCRIPTION -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Description
                </p>

                <p class="font-semibold">

                    <?= nl2br(
                        htmlspecialchars(
                            $match['found_description']
                        )
                    ) ?>

                </p>

            </div>



            <!-- FINDER CONTACT -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Finder Contact
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['finder_contact_information']
                    ) ?>

                </p>

            </div>

        </div>

    </div>



    <!-- ==========================================
         FINDER
    =========================================== -->

    <div class="bg-white
                rounded-2xl
                shadow
                p-6">


        <h2 class="text-xl
                   font-bold
                   mb-5">

            Finder Information

        </h2>


        <div class="grid md:grid-cols-2 gap-5">


            <div>

                <p class="text-gray-500 text-sm">
                    Full Name
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['finder_name']
                    ) ?>

                </p>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Student ID
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['finder_student_id']
                    ) ?>

                </p>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Email
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['finder_email']
                    ) ?>

                </p>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Department
                </p>

                <p class="font-semibold">

                    <?= htmlspecialchars(
                        $match['finder_department']
                    ) ?>

                </p>

            </div>

        </div>

    </div>


</div>


<script>
    lucide.createIcons();
</script>


</body>
</html>