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
   GET MATCH ID
========================================== */

if (!isset($_GET['match_id'])) {
    die("Invalid Match ID");
}

$match_id = (int) $_GET['match_id'];


/* ==========================================
   GET CLAIM / MATCH INFORMATION
========================================== */

$sql = "
    SELECT

        /* MATCH */
        m.match_id,
        m.lost_id,
        m.found_id,
        m.match_percentage,
        m.status,
        m.claim_status,
        m.finder_status,
        m.owner_status,

        /* LOST ITEM / OWNER */
        l.user_id AS owner_id,
        l.item_name AS lost_item_name,
        l.category AS lost_category,
        l.description AS lost_description,
        l.lost_date,
        l.lost_location,
        l.contact_information AS lost_contact_information,
        l.secret_information AS lost_secret_information,
        l.item_image AS lost_item_image,

        /* FOUND ITEM / FINDER */
        f.user_id AS finder_id,
        f.item_name AS found_item_name,
        f.category AS found_category,
        f.description AS found_description,
        f.found_date,
        f.found_location,
        f.finder_contact_information,

        /* OWNER */
        owner.full_name AS owner_name,
        owner.email AS owner_email,

        /* FINDER */
        finder.full_name AS finder_name,
        finder.email AS finder_email

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
$stmt->bind_param("i", $match_id);
$stmt->execute();

$result = $stmt->get_result();


/* ==========================================
   CHECK MATCH EXISTS
========================================== */

if ($result->num_rows === 0) {
    die("Claim Request not found.");
}

$match = $result->fetch_assoc();


/* ==========================================
   ONLY FINDER CAN VIEW CLAIM REQUEST
========================================== */

if ((int)$match['finder_id'] !== $user_id) {
    die("You are not authorized to view this claim request.");
}


/* ==========================================
   LOST ITEM IMAGE PATH
========================================== */

$lost_image = trim($match['lost_item_image'] ?? '');

$image_file = '';

if ($lost_image !== '') {
    $image_file = __DIR__ . '/' . ltrim($lost_image, '/');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Claim Request Review</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="bg-gray-100 min-h-screen">


<!-- ==========================================
     MAIN CONTAINER
========================================== -->

<div class="max-w-5xl mx-auto px-4 py-8">


    <!-- ======================================
         BACK BUTTON
    ======================================= -->

    <a href="my-matches.php"
       class="inline-flex items-center gap-2
              mb-6
              bg-white
              px-4 py-2
              rounded-xl
              shadow
              text-gray-700
              font-medium
              hover:bg-gray-50">

        ← Back to My Matches

    </a>



    <!-- ======================================
         PAGE TITLE
    ======================================= -->

    <div class="mb-6">

        <h1 class="text-3xl font-bold text-gray-800">
            Claim Request Review
        </h1>

        <p class="text-gray-500 mt-1">
            Review the lost and found item information before responding.
        </p>

    </div>



    <!-- ======================================
         MATCH INFORMATION
    ======================================= -->

    <div class="bg-white rounded-2xl shadow p-6 mb-6">

        <div class="flex flex-wrap items-center justify-between gap-4">

            <div>

                <p class="text-gray-500 text-sm">
                    Match Percentage
                </p>

                <p class="text-3xl font-bold text-green-600">
                    <?= htmlspecialchars($match['match_percentage']) ?>%
                </p>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Claim Status
                </p>

                <p class="font-bold text-lg text-gray-800">
                    <?= htmlspecialchars($match['claim_status'] ?? 'Pending') ?>
                </p>

            </div>

        </div>

    </div>



    <!-- ======================================
         LOST ITEM INFORMATION
    ======================================= -->

    <div class="bg-white rounded-2xl shadow p-6 mb-6">

        <h2 class="text-xl font-bold mb-5 text-red-600">
            Lost Item Information
        </h2>


        <!-- ==================================
             LOST ITEM IMAGE
        =================================== -->

        <?php if ($lost_image !== '' && file_exists($image_file)): ?>

            <div class="mb-6">

                <p class="text-gray-500 text-sm font-medium mb-2">
                    Lost Item Image
                </p>


                <div class="w-52 h-40
                            bg-gray-50
                            border border-gray-200
                            rounded-xl
                            overflow-hidden
                            flex items-center justify-center">

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



        <!-- ==================================
             LOST ITEM DETAILS
        =================================== -->

        <div class="grid md:grid-cols-2 gap-5">


            <!-- ITEM NAME -->

            <div>

                <p class="text-gray-500 text-sm">
                    Item Name
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['lost_item_name']) ?>
                </p>

            </div>



            <!-- CATEGORY -->

            <div>

                <p class="text-gray-500 text-sm">
                    Category
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['lost_category']) ?>
                </p>

            </div>



            <!-- LOST DATE -->

            <div>

                <p class="text-gray-500 text-sm">
                    Lost Date
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['lost_date']) ?>
                </p>

            </div>



            <!-- LOST LOCATION -->

            <div>

                <p class="text-gray-500 text-sm">
                    Lost Location
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['lost_location']) ?>
                </p>

            </div>



            <!-- DESCRIPTION -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Description
                </p>

                <p class="font-semibold">
                    <?= nl2br(htmlspecialchars($match['lost_description'])) ?>
                </p>

            </div>



            <!-- OWNER CONTACT -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Owner Contact
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['lost_contact_information']) ?>
                </p>

            </div>



            <!-- SECRET INFORMATION -->

            <div class="md:col-span-2
                        bg-yellow-50
                        border border-yellow-300
                        p-4
                        rounded-xl">

                <p class="text-yellow-700 font-bold mb-1">
                    🔐 Secret Information
                </p>

                <p class="text-gray-800">
                    <?= nl2br(htmlspecialchars($match['lost_secret_information'])) ?>
                </p>

            </div>

        </div>

    </div>



    <!-- ======================================
         FOUND ITEM INFORMATION
    ======================================= -->

    <div class="bg-white rounded-2xl shadow p-6 mb-6">

        <h2 class="text-xl font-bold mb-5 text-green-600">
            Found Item Information
        </h2>


        <div class="grid md:grid-cols-2 gap-5">


            <!-- ITEM NAME -->

            <div>

                <p class="text-gray-500 text-sm">
                    Item Name
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['found_item_name']) ?>
                </p>

            </div>



            <!-- CATEGORY -->

            <div>

                <p class="text-gray-500 text-sm">
                    Category
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['found_category']) ?>
                </p>

            </div>



            <!-- FOUND DATE -->

            <div>

                <p class="text-gray-500 text-sm">
                    Found Date
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['found_date']) ?>
                </p>

            </div>



            <!-- FOUND LOCATION -->

            <div>

                <p class="text-gray-500 text-sm">
                    Found Location
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['found_location']) ?>
                </p>

            </div>



            <!-- DESCRIPTION -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Description
                </p>

                <p class="font-semibold">
                    <?= nl2br(htmlspecialchars($match['found_description'])) ?>
                </p>

            </div>



            <!-- FINDER CONTACT -->

            <div class="md:col-span-2">

                <p class="text-gray-500 text-sm">
                    Finder Contact
                </p>

                <p class="font-semibold">
                    <?= htmlspecialchars($match['finder_contact_information']) ?>
                </p>

            </div>

        </div>

    </div>



    <!-- ======================================
         CLAIM ACTION
    ======================================= -->

    <?php if (($match['claim_status'] ?? 'Pending') === 'Pending'): ?>

        <div class="bg-white rounded-2xl shadow p-6">

            <h2 class="text-xl font-bold text-gray-800 mb-2">
                Claim Request
            </h2>

            <p class="text-gray-500 mb-5">
                Do you want to accept or reject this claim request?
            </p>


            <div class="flex flex-wrap gap-3">


                <!-- APPROVE CLAIM -->

                <form action="approve-claim.php"
                      method="POST">

                    <input
                        type="hidden"
                        name="match_id"
                        value="<?= $match_id ?>"
                    >

                    <button
                        type="submit"
                        class="bg-green-600
                               text-white
                               px-6
                               py-3
                               rounded-xl
                               font-semibold
                               hover:bg-green-700">

                        ✓ Approve Claim

                    </button>

                </form>



                <!-- REJECT CLAIM -->

                <form action="reject-claim.php"
                      method="POST">

                    <input
                        type="hidden"
                        name="match_id"
                        value="<?= $match_id ?>"
                    >

                    <button
                        type="submit"
                        class="bg-red-600
                               text-white
                               px-6
                               py-3
                               rounded-xl
                               font-semibold
                               hover:bg-red-700">

                        ✕ Reject Claim

                    </button>

                </form>

            </div>

        </div>

    <?php endif; ?>



    <!-- ======================================
         APPROVED CLAIM
    ======================================= -->

    <?php if (($match['claim_status'] ?? '') === 'Approved'): ?>

        <div class="bg-green-50
                    border border-green-200
                    rounded-2xl
                    p-6">

            <h2 class="text-xl font-bold text-green-700 mb-2">
                ✓ Claim Approved
            </h2>

            <p class="text-gray-700 mb-4">
                This claim request has been approved.
            </p>


            <a href="approved-claim-details.php?match_id=<?= $match_id ?>"
               class="inline-block
                      bg-green-600
                      text-white
                      px-6
                      py-3
                      rounded-xl
                      font-semibold
                      hover:bg-green-700">

                View Approved Claim Details

            </a>

        </div>

    <?php endif; ?>



    <!-- ======================================
         REJECTED CLAIM
    ======================================= -->

    <?php if (($match['claim_status'] ?? '') === 'Rejected'): ?>

        <div class="bg-red-50
                    border border-red-200
                    rounded-2xl
                    p-6">

            <h2 class="text-xl font-bold text-red-700 mb-2">
                ✕ Claim Rejected
            </h2>

            <p class="text-gray-700">
                This claim request has been rejected.
            </p>

        </div>

    <?php endif; ?>


</div>

</body>
</html>