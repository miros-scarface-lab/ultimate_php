
<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$full_name  = $_SESSION['full_name'] ?? 'Samiul Islam';
$department = $_SESSION['department'] ?? 'CSE, 2nd Year';


/* ==========================================
   NOTIFICATIONS
========================================== */

$notifications = [];
$unread_count = 0;

if (isset($conn)) {

    $count_sql = "
        SELECT COUNT(*) AS unread_count
        FROM notifications
        WHERE user_id = ?
        AND receiver = 'user'
        AND is_read = 0
    ";

    $count_stmt = $conn->prepare($count_sql);

    if ($count_stmt) {

        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();

        $count_result = $count_stmt->get_result();

        if ($count_result) {

            $count_data = $count_result->fetch_assoc();

            $unread_count = (int)($count_data['unread_count'] ?? 0);
        }

        $count_stmt->close();
    }


    $notif_sql = "
        SELECT
            notification_id,
            match_id,
            title,
            message,
            is_read,
            created_at
        FROM notifications
        WHERE user_id = ?
        AND receiver = 'user'
        ORDER BY created_at DESC
        LIMIT 10
    ";

    $notif_stmt = $conn->prepare($notif_sql);

    if ($notif_stmt) {

        $notif_stmt->bind_param("i", $user_id);
        $notif_stmt->execute();

        $notif_result = $notif_stmt->get_result();

        if ($notif_result) {

            while ($row = $notif_result->fetch_assoc()) {

                $notifications[] = $row;
            }
        }

        $notif_stmt->close();
    }
}


/* ==========================================
   RECENT LOST POSTS
   ONLY APPROVED LOST POSTS
========================================== */

$lost_items = [];

if (isset($conn)) {

    $lost_sql = "
        SELECT *
        FROM lost_items
        WHERE status = 'Approved'
        ORDER BY lost_date DESC, lost_id DESC
        LIMIT 5
    ";

    $lost_result = $conn->query($lost_sql);

    if ($lost_result && $lost_result->num_rows > 0) {

        while ($row = $lost_result->fetch_assoc()) {

            $lost_items[] = $row;
        }
    }
}


/* ==========================================
   RECENT FOUND POSTS
   ONLY APPROVED FOUND POSTS
========================================== */

$found_items = [];

if (isset($conn)) {

    $found_sql = "
        SELECT *
        FROM found_items
        WHERE status = 'Approved'
        ORDER BY found_date DESC, found_id DESC
        LIMIT 5
    ";

    $found_result = $conn->query($found_sql);

    if ($found_result && $found_result->num_rows > 0) {

        while ($row = $found_result->fetch_assoc()) {

            $found_items[] = $row;
        }
    }
}


/* ==========================================
   RECENT ALL POSTS
   LOST + FOUND TOGETHER
   SORTED BY DATE
========================================== */

$recent_all_items = [];

if (isset($conn)) {

    $recent_all_sql = "
        SELECT *
        FROM
        (
            SELECT
                'lost' AS item_type,
                lost_id AS id,
                item_name,
                category,
                lost_location AS location,
                lost_date AS post_date,
                item_image
            FROM lost_items
            WHERE status = 'Approved'

            UNION ALL

            SELECT
                'found' AS item_type,
                found_id AS id,
                item_name,
                category,
                found_location AS location,
                found_date AS post_date,
                NULL AS item_image
            FROM found_items
            WHERE status = 'Approved'
        ) AS recent_posts

        ORDER BY post_date DESC, id DESC
        LIMIT 5
    ";

    $recent_all_result = $conn->query($recent_all_sql);

    if ($recent_all_result && $recent_all_result->num_rows > 0) {

        while ($row = $recent_all_result->fetch_assoc()) {

            $recent_all_items[] = $row;
        }
    }
}


/* ==========================================
   FALLBACK LOST ITEMS
========================================== */

if (empty($lost_items)) {

    $lost_items = [

        [
            'lost_id' => 1,
            'item_name' => 'Black Wallet',
            'lost_location' => 'Library, Building 2',
            'lost_date' => '2025-05-30',
            'item_image' => null
        ],

        [
            'lost_id' => 2,
            'item_name' => 'University ID Card',
            'lost_location' => 'Main Gate',
            'lost_date' => '2025-05-28',
            'item_image' => null
        ],

        [
            'lost_id' => 3,
            'item_name' => 'Headphones',
            'lost_location' => 'Cafeteria',
            'lost_date' => '2025-05-27',
            'item_image' => null
        ],

        [
            'lost_id' => 4,
            'item_name' => 'Math Textbook',
            'lost_location' => 'Room 301',
            'lost_date' => '2025-05-26',
            'item_image' => null
        ],

        [
            'lost_id' => 5,
            'item_name' => 'Silver Watch',
            'lost_location' => 'Playground',
            'lost_date' => '2025-05-25',
            'item_image' => null
        ]
    ];
}


/* ==========================================
   FALLBACK RECENT ITEMS
========================================== */

if (empty($recent_all_items)) {

    $recent_all_items = [

        [
            'id' => 1,
            'item_type' => 'found',
            'item_name' => 'Water Bottle',
            'category' => 'Others',
            'location' => 'Cafeteria',
            'post_date' => '2025-05-29',
            'item_image' => null
        ],

        [
            'id' => 2,
            'item_type' => 'found',
            'item_name' => 'Key with Keychain',
            'category' => 'Accessories',
            'location' => 'Parking Area',
            'post_date' => '2025-05-28',
            'item_image' => null
        ],

        [
            'id' => 3,
            'item_type' => 'found',
            'item_name' => 'Black Umbrella',
            'category' => 'Others',
            'location' => 'Library, Floor 1',
            'post_date' => '2025-05-27',
            'item_image' => null
        ],

        [
            'id' => 4,
            'item_type' => 'found',
            'item_name' => 'Power Bank',
            'category' => 'Electronics',
            'location' => 'Lab 404',
            'post_date' => '2025-05-26',
            'item_image' => null
        ],

        [
            'id' => 5,
            'item_type' => 'found',
            'item_name' => 'Blue Notebook',
            'category' => 'Books/Stationery',
            'location' => 'Seminar Room',
            'post_date' => '2025-05-25',
            'item_image' => null
        ]
    ];
}


/* ==========================================
   FALLBACK FOUND ITEMS
========================================== */

if (empty($found_items)) {

    $found_items = [

        [
            'found_id' => 1,
            'item_name' => 'Water Bottle',
            'found_location' => 'Cafeteria',
            'found_date' => '2025-05-29'
        ],

        [
            'found_id' => 2,
            'item_name' => 'Key with Keychain',
            'found_location' => 'Parking Area',
            'found_date' => '2025-05-28'
        ],

        [
            'found_id' => 3,
            'item_name' => 'Black Umbrella',
            'found_location' => 'Library, Floor 1',
            'found_date' => '2025-05-27'
        ],

        [
            'found_id' => 4,
            'item_name' => 'Power Bank',
            'found_location' => 'Lab 404',
            'found_date' => '2025-05-26'
        ],

        [
            'found_id' => 5,
            'item_name' => 'Blue Notebook',
            'found_location' => 'Seminar Room',
            'found_date' => '2025-05-25'
        ]
    ];
}


/* ==========================================
   ITEM GRAPHIC
========================================== */

function getItemGraphic($name)
{
    $name = strtolower($name);


    /* Wallet */

    if (strpos($name, 'wallet') !== false) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <rect x="3" y="8" width="30" height="20" rx="4"
                  fill="#1e293b"
                  stroke="#0f172a"
                  stroke-width="1.5"/>

            <path d="M3 13 L33 13"
                  stroke="#334155"
                  stroke-width="1.5"/>

            <rect x="20" y="14" width="13" height="8" rx="2"
                  fill="#334155"
                  stroke="#475569"
                  stroke-width="1"/>

            <circle cx="23.5" cy="18" r="1.5"
                    fill="#fbbf24"/>

        </svg>';
    }


    /* ID Card */

    if (
        strpos($name, 'id') !== false ||
        strpos($name, 'card') !== false
    ) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <rect x="3" y="7" width="30" height="22" rx="3"
                  fill="#ffffff"
                  stroke="#93c5fd"
                  stroke-width="1.5"/>

            <rect x="3" y="7" width="30" height="6"
                  fill="#0284c7"/>

            <rect x="6" y="16" width="7" height="9"
                  rx="1.5"
                  fill="#93c5fd"/>

            <circle cx="9.5" cy="19.5" r="2"
                    fill="#1e3a8a"/>

            <rect x="16" y="16" width="14" height="2"
                  rx="1"
                  fill="#475569"/>

            <rect x="16" y="20" width="10" height="2"
                  rx="1"
                  fill="#94a3b8"/>

            <rect x="16" y="24" width="7" height="1.5"
                  rx="0.75"
                  fill="#cbd5e1"/>

        </svg>';
    }


    /* Headphones */

    if (
        strpos($name, 'headphone') !== false ||
        strpos($name, 'earphone') !== false
    ) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <path d="M7 19 C7 11 11.5 6 18 6
                     C24.5 6 29 11 29 19"
                  stroke="#0f172a"
                  stroke-width="3"
                  stroke-linecap="round"/>

            <rect x="4.5" y="17" width="6" height="12"
                  rx="3"
                  fill="#1e293b"
                  stroke="#0f172a"
                  stroke-width="1.5"/>

            <rect x="25.5" y="17" width="6" height="12"
                  rx="3"
                  fill="#1e293b"
                  stroke="#0f172a"
                  stroke-width="1.5"/>

        </svg>';
    }


    /* Book */

    if (
        strpos($name, 'book') !== false ||
        strpos($name, 'math') !== false ||
        strpos($name, 'textbook') !== false
    ) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <rect x="7" y="5" width="22" height="26"
                  rx="3"
                  fill="#d97706"
                  stroke="#b45309"
                  stroke-width="1.5"/>

            <rect x="7" y="5" width="4.5" height="26"
                  rx="1"
                  fill="#92400e"/>

            <rect x="14" y="10" width="12" height="5"
                  rx="1"
                  fill="#fef3c7"/>

            <path d="M17 18 L23 18 M20 15 L20 21"
                  stroke="#78350f"
                  stroke-width="1.5"
                  stroke-linecap="round"/>

        </svg>';
    }


    /* Watch */

    if (
        strpos($name, 'watch') !== false ||
        strpos($name, 'clock') !== false
    ) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <rect x="14.5" y="2" width="7" height="32"
                  rx="2"
                  fill="#64748b"/>

            <circle cx="18" cy="18" r="9"
                    fill="#e2e8f0"
                    stroke="#334155"
                    stroke-width="2"/>

            <circle cx="18" cy="18" r="6.5"
                    fill="#ffffff"/>

            <path d="M18 13.5 L18 18 L21.5 18"
                  stroke="#0f172a"
                  stroke-width="1.5"
                  stroke-linecap="round"/>

        </svg>';
    }


    /* Water Bottle */

    if (
        strpos($name, 'bottle') !== false ||
        strpos($name, 'water') !== false
    ) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <rect x="15" y="3" width="6" height="5"
                  rx="1.5"
                  fill="#0f766e"/>

            <rect x="12" y="8" width="12" height="24"
                  rx="4"
                  fill="#06b6d4"
                  stroke="#0891b2"
                  stroke-width="1.5"/>

            <rect x="12" y="15" width="12" height="8"
                  fill="#0891b2"
                  opacity="0.3"/>

            <path d="M14 11 L22 11"
                  stroke="#ffffff"
                  stroke-width="1.5"
                  stroke-linecap="round"/>

        </svg>';
    }


    /* Key */

    if (strpos($name, 'key') !== false) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <circle cx="14" cy="13" r="5.5"
                    fill="#f59e0b"
                    stroke="#b45309"
                    stroke-width="1.5"/>

            <circle cx="14" cy="13" r="2.5"
                    fill="#ffffff"/>

            <path d="M17.5 16.5 L27 26 L24.5 28.5
                     M22 23 L25.5 22
                     M24.5 25.5 L28 24.5"
                  stroke="#b45309"
                  stroke-width="2"
                  stroke-linecap="round"/>

        </svg>';
    }


    /* Umbrella */

    if (strpos($name, 'umbrella') !== false) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <path d="M4 19 C4 11 10 6 18 6
                     C26 6 32 11 32 19 Z"
                  fill="#1e293b"
                  stroke="#0f172a"
                  stroke-width="1.5"/>

            <line x1="18" y1="6" x2="18" y2="27"
                  stroke="#475569"
                  stroke-width="2"/>

            <path d="M18 27 C18 29.5 16 31 14 29"
                  stroke="#475569"
                  stroke-width="2"
                  stroke-linecap="round"
                  fill="none"/>

        </svg>';
    }


    /* Power Bank */

    if (
        strpos($name, 'power') !== false ||
        strpos($name, 'battery') !== false ||
        strpos($name, 'phone') !== false
    ) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <rect x="9" y="5" width="18" height="26"
                  rx="3.5"
                  fill="#1e293b"
                  stroke="#0f172a"
                  stroke-width="1.5"/>

            <circle cx="13" cy="10" r="1.2"
                    fill="#22c55e"/>

            <circle cx="16.5" cy="10" r="1.2"
                    fill="#22c55e"/>

            <circle cx="20" cy="10" r="1.2"
                    fill="#22c55e"/>

            <circle cx="23.5" cy="10" r="1.2"
                    fill="#64748b"/>

        </svg>';
    }


    /* Notebook */

    if (
        strpos($name, 'notebook') !== false ||
        strpos($name, 'khata') !== false
    ) {

        return '
        <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

            <rect x="7" y="5" width="22" height="26"
                  rx="3.5"
                  fill="#2563eb"
                  stroke="#1d4ed8"
                  stroke-width="1.5"/>

            <circle cx="8.5" cy="8.5" r="1.5"
                    fill="#f8fafc"/>

            <circle cx="8.5" cy="14" r="1.5"
                    fill="#f8fafc"/>

            <circle cx="8.5" cy="19.5" r="1.5"
                    fill="#f8fafc"/>

            <circle cx="8.5" cy="25" r="1.5"
                    fill="#f8fafc"/>

            <rect x="13" y="10" width="11" height="3"
                  rx="1"
                  fill="#ffffff"
                  opacity="0.85"/>

        </svg>';
    }


    /* Default */

    return '
    <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none">

        <polygon points="18,4 32,11 18,18 4,11"
                 fill="#0d9488"/>

        <polygon points="4,11 18,18 18,32 4,25"
                 fill="#0f766e"/>

        <polygon points="32,11 18,18 18,32 32,25"
                 fill="#115e59"/>

    </svg>';
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Campus Lost & Found Management System
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

    <script src="https://unpkg.com/lucide@latest"></script>

</head>


<body
    class="flex h-screen overflow-hidden
           text-slate-800 antialiased
           selection:bg-teal-500
           selection:text-white"
>


<!-- ==========================================
     SIDEBAR
========================================== -->

<aside
    id="mobileSidebar"
    class="fixed lg:static inset-y-0 left-0 z-50 lg:z-auto
           w-64 bg-[#003844] text-white
           flex flex-col justify-between
           p-5 shrink-0 hidden lg:flex
           select-none"
>

    <div>

        <div
            class="flex items-center gap-3.5
                   mb-8 px-1"
        >

            <img
                src="images/new-logo.jpeg"
                alt="CAMPUS LOST & FOUND"
                class="h-10 w-auto object-contain
                       rounded-xl"
            >

        </div>


        <nav
            class="space-y-1.5
                   font-semibold text-sm"
        >

            <a
                href="dashboard.php"
                class="flex items-center gap-3.5
                       px-4 py-3
                       bg-[#0fa4af] text-white
                       rounded-xl shadow-md
                       transition"
            >

                <i
                    data-lucide="layout-dashboard"
                    class="w-4 h-4 text-white"
                ></i>

                <span>Dashboard</span>

            </a>


            <a
                href="add-lost-item.php"
                class="flex items-center gap-3.5
                       px-4 py-3
                       text-teal-100/70
                       hover:text-white
                       hover:bg-white/10
                       rounded-xl transition"
            >

                <i
                    data-lucide="plus-circle"
                    class="w-4 h-4"
                ></i>

                <span>Add Lost Item</span>

            </a>


            <a
                href="add-found-item.php"
                class="flex items-center gap-3.5
                       px-4 py-3
                       text-teal-100/70
                       hover:text-white
                       hover:bg-white/10
                       rounded-xl transition"
            >

                <i
                    data-lucide="plus-square"
                    class="w-4 h-4"
                ></i>

                <span>Add Found Item</span>

            </a>


            <a
                href="search-item.html"
                class="flex items-center gap-3.5
                       px-4 py-3
                       text-teal-100/70
                       hover:text-white
                       hover:bg-white/10
                       rounded-xl transition"
            >

                <i
                    data-lucide="search"
                    class="w-4 h-4"
                ></i>

                <span>Search Item</span>

            </a>


            <a
                href="view-items.php"
                class="flex items-center gap-3.5
                       px-4 py-3
                       text-teal-100/70
                       hover:text-white
                       hover:bg-white/10
                       rounded-xl transition"
            >

                <i
                    data-lucide="clipboard-list"
                    class="w-4 h-4"
                ></i>

                <span>View All Items</span>

            </a>


            <a
                href="my-matches.php"
                class="flex items-center gap-3.5
                       px-4 py-3
                       text-teal-100/70
                       hover:text-white
                       hover:bg-white/10
                       rounded-xl transition"
            >

                <i
                    data-lucide="heart"
                    class="w-4 h-4"
                ></i>

                <span>My Matches</span>

            </a>

        </nav>

    </div>


    <div class="pt-4 text-center">

        <div
            class="relative mx-auto flex
                   items-center justify-center mb-1"
        >

            <img
                src="images/box.jpeg"
                alt="Lost Box Artwork"
                class="w-36 h-auto object-contain
                       rounded-2xl drop-shadow-xl
                       mx-auto"
            >

        </div>


        <p
            class="text-xs text-teal-200/90
                   font-normal leading-relaxed
                   italic mt-2"
        >

            "Let's help return
            <br>
            what truly matters."

        </p>


        <div class="mt-3">

            <a
                href="logout.php"
                class="text-xs text-rose-300
                       hover:text-rose-200
                       bg-rose-950/40
                       border border-rose-800/40
                       px-3 py-1 rounded-lg
                       transition inline-flex
                       items-center gap-1.5
                       font-semibold"
            >

                <i
                    data-lucide="log-out"
                    class="w-3.5 h-3.5"
                ></i>

                Logout

            </a>

        </div>

    </div>

</aside>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div
    class="flex-1 flex flex-col
           h-screen overflow-y-auto"
>


<!-- ==========================================
     HEADER
========================================== -->

<header
    class="bg-white border-b
           border-slate-200/80
           px-7 py-3.5 flex
           justify-between items-center
           sticky top-0 z-20
           shadow-[0_1px_2px_rgba(0,0,0,0.03)]"
>

    <div class="flex items-center gap-4">

        <button
            type="button"
            id="mobileMenuButton"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            class="lg:hidden text-slate-600
                   hover:text-slate-900
                   focus:outline-none"
        >

            <i
                data-lucide="menu"
                class="w-5 h-5"
            ></i>

        </button>


        <h2
            class="font-bold text-slate-800
                   text-base md:text-[17px]"
        >

            Campus Lost & Found Management System

        </h2>

    </div>


    <div class="flex items-center gap-6">

        <a
            href="notifications.php"
            id="notificationButton"
            class="relative cursor-pointer
                   p-1.5 hover:bg-slate-100
                   rounded-full transition
                   block"
        >

            <i
                data-lucide="bell"
                class="w-5 h-5 text-slate-700"
            ></i>


            <?php if ($unread_count > 0): ?>

                <span
                    class="absolute top-0 right-0
                           bg-[#22c55e] text-white
                           text-[10px] w-4 h-4
                           rounded-full flex
                           items-center
                           justify-center
                           font-bold
                           ring-2 ring-white"
                >

                    <?php echo $unread_count; ?>

                </span>

            <?php endif; ?>

        </a>


        <div
            class="flex items-center gap-2.5
                   cursor-pointer group"
        >

            <div
                class="w-9 h-9 rounded-full
                       bg-teal-50
                       border border-teal-100
                       flex items-center
                       justify-center
                       shadow-sm"
            >

                <i
                    data-lucide="user"
                    class="w-5 h-5 text-teal-700"
                ></i>

            </div>


            <p
                class="text-xs font-bold
                       text-slate-800
                       leading-snug
                       group-hover:text-teal-700
                       transition"
            >

                <?php
                echo htmlspecialchars($full_name);
                ?>

            </p>

        </div>

    </div>

</header>


<!-- ==========================================
     MAIN
========================================== -->

<main
    class="p-6 md:p-8 space-y-6
           flex-1 max-w-7xl
           w-full mx-auto"
>


<!-- ==========================================
     WELCOME BANNER
========================================== -->

<div
    class="relative overflow-hidden
           bg-gradient-to-r
           from-[#eafaf1]
           via-[#ebfaef]
           to-[#d8f3dc]
           border border-emerald-200/80
           rounded-3xl p-6 sm:p-8
           flex flex-col md:flex-row
           md:items-center
           justify-between
           shadow-[0_2px_10px_rgba(16,185,129,0.06)]"
>

    <div
        class="relative z-10
               space-y-1 max-w-md"
    >

        <h1
            class="text-2xl sm:text-[25px]
                   font-extrabold
                   text-slate-900
                   tracking-tight"
        >

            Welcome back,

            <?php
            echo htmlspecialchars($full_name);
            ?>

            ! 👋

        </h1>


        <p
            class="text-xs sm:text-sm
                   text-slate-600
                   font-medium
                   leading-relaxed"
        >

            Let's help each other by reuniting
            lost items with their owners.

        </p>

    </div>


    <div
        class="flex items-center justify-center relative z-10 shrink-0 mt-4 md:mt-0"
    >

        <img
            src="images/banner.jpeg"
            alt="Lost & Found Items"
            class="h-28 md:h-32
                   w-auto object-contain
                   rounded-2xl
                   drop-shadow-md"
        >

    </div>

</div>


<!-- ==========================================
     QUICK ACTION CARDS
========================================== -->

<div
    class="grid grid-cols-2
           sm:grid-cols-3
           lg:grid-cols-5 gap-3.5"
>


    <!-- LOST -->

    <a
        href="add-lost-item.php"
        class="bg-[#eff6ff]
               hover:bg-[#e0f0fe]
               border border-[#bfdbfe]/80
               hover:border-blue-300
               p-4 rounded-2xl
               text-center transition
               duration-200 shadow-sm
               hover:shadow-md
               flex flex-col
               items-center group"
    >

        <div
            class="w-12 h-12
                   rounded-xl bg-blue-100
                   flex items-center
                   justify-center mb-2
                   group-hover:scale-110
                   transition duration-200
                   relative"
        >

            <svg
                class="w-7 h-7 text-blue-600"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >

                <path
                    d="M6 2 3 6v14a2 2 0 0 0 2 2h14
                       a2 2 0 0 0 2-2V6l-3-4Z"
                />

                <path d="M3 6h18"/>

                <path d="M16 10a4 4 0 0 1-8 0"/>

            </svg>


            <span
                class="absolute -bottom-1
                       -right-1 bg-blue-600
                       text-white text-[10px]
                       w-4 h-4 rounded-full
                       flex items-center
                       justify-center
                       font-bold ring-2 ring-white"
            >

                ?

            </span>

        </div>


        <h3
            class="font-bold text-xs text-slate-800"
        >
            Add Lost Item
        </h3>


        <p
            class="text-[10.5px]
                   text-slate-500
                   font-medium mt-0.5"
        >

            Report your lost item

        </p>

    </a>


    <!-- FOUND -->

    <a
        href="add-found-item.php"
        class="bg-[#ecfdf5]
               hover:bg-[#d1fae5]
               border border-[#a7f3d0]/80
               hover:border-emerald-300
               p-4 rounded-2xl
               text-center transition
               duration-200 shadow-sm
               hover:shadow-md
               flex flex-col
               items-center group"
    >

        <div
            class="w-12 h-12
                   rounded-xl bg-emerald-100
                   flex items-center
                   justify-center mb-2
                   group-hover:scale-110
                   transition duration-200
                   relative"
        >

            <svg
                class="w-7 h-7 text-emerald-600"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >

                <path
                    d="M6 2 3 6v14a2 2 0 0 0 2 2h14
                       a2 2 0 0 0 2-2V6l-3-4Z"
                />

                <path d="M3 6h18"/>

                <path d="M16 10a4 4 0 0 1-8 0"/>

            </svg>


            <span
                class="absolute -bottom-1
                       -right-1
                       bg-emerald-600
                       text-white text-[10px]
                       w-4 h-4 rounded-full
                       flex items-center
                       justify-center
                       font-bold ring-2 ring-white"
            >

                ✓

            </span>

        </div>


        <h3
            class="font-bold text-xs text-slate-800"
        >
            Add Found Item
        </h3>


        <p
            class="text-[10.5px]
                   text-slate-500
                   font-medium mt-0.5"
        >

            Report found item

        </p>

    </a>


    <!-- SEARCH -->

    <a
        href="search-item.html"
        class="bg-[#fffbeb]
               hover:bg-[#fef3c7]
               border border-[#fde68a]/80
               hover:border-amber-300
               p-4 rounded-2xl
               text-center transition
               duration-200 shadow-sm
               hover:shadow-md
               flex flex-col
               items-center group"
    >

        <div
            class="w-12 h-12
                   rounded-xl bg-amber-100
                   flex items-center
                   justify-center mb-2
                   group-hover:scale-110
                   transition duration-200"
        >

            <svg
                class="w-7 h-7 text-amber-600"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
            >

                <circle
                    cx="11"
                    cy="11"
                    r="8"
                />

                <path d="m21 21-4.3-4.3"/>

            </svg>

        </div>


        <h3
            class="font-bold text-xs text-slate-800"
        >
            Search Item
        </h3>


        <p
            class="text-[10.5px]
                   text-slate-500
                   font-medium mt-0.5"
        >

            Search lost & found items

        </p>

    </a>


    <!-- VIEW ALL -->

    <a
        href="view-items.php"
        class="bg-[#f5f3ff]
               hover:bg-[#ede9fe]
               border border-[#ddd6fe]/80
               hover:border-purple-300
               p-4 rounded-2xl
               text-center transition
               duration-200 shadow-sm
               hover:shadow-md
               flex flex-col
               items-center group"
    >

        <div
            class="w-12 h-12
                   rounded-xl bg-purple-100
                   flex items-center
                   justify-center mb-2
                   group-hover:scale-110
                   transition duration-200"
        >

            <svg
                class="w-7 h-7 text-purple-600"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >

                <path
                    d="M16 4h2a2 2 0 0 1 2 2v14
                       a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6
                       a2 2 0 0 1 2-2h2"
                />

                <rect
                    x="8"
                    y="2"
                    width="8"
                    height="4"
                    rx="1"
                />

            </svg>

        </div>


        <h3
            class="font-bold text-xs text-slate-800"
        >
            View All Items
        </h3>


        <p
            class="text-[10.5px]
                   text-slate-500
                   font-medium mt-0.5"
        >

            View all lost & found items

        </p>

    </a>


    <!-- MATCHES -->

    <a
        href="my-matches.php"
        class="bg-[#fff1f2]
               hover:bg-[#ffe4e6]
               border border-[#fecdd3]/80
               hover:border-rose-300
               p-4 rounded-2xl
               text-center transition
               duration-200 shadow-sm
               hover:shadow-md
               flex flex-col
               items-center group
               col-span-2
               sm:col-span-1"
    >

        <div
            class="w-12 h-12
                   rounded-xl bg-rose-100
                   flex items-center
                   justify-center mb-2
                   group-hover:scale-110
                   transition duration-200"
        >

            <svg
                class="w-7 h-7
                       text-rose-500
                       fill-rose-500"
                viewBox="0 0 24 24"
            >

                <path
                    d="M19 14c1.49-1.46 3-3.21
                       3-5.5A5.5 5.5 0 0 0 16.5 3
                       c-1.76 0-3 .5-4.5 2
                       -1.5-1.5-2.74-2-4.5-2
                       A5.5 5.5 0 0 0 2 8.5
                       c0 2.3 1.5 4.05 3 5.5l7 7Z"
                />

            </svg>

        </div>


        <h3
            class="font-bold text-xs text-slate-800"
        >
            My Matches
        </h3>


        <p
            class="text-[10.5px]
                   text-slate-500
                   font-medium mt-0.5"
        >

            Items that might match

        </p>

    </a>

</div>


<!-- ==========================================
     RECENT POSTS
========================================== -->

<div
    class="grid grid-cols-1 md:grid-cols-3
           gap-6"
>


<!-- ==========================================
     RECENT LOST
========================================== -->

<div
    class="bg-white
           border border-slate-200/80
           rounded-2xl p-5 shadow-sm
           flex flex-col
           justify-between
           hover:shadow-md transition"
>

    <div>

        <div
            class="flex justify-between
                   items-center mb-4 pb-3
                   border-b border-slate-100"
        >

            <div
                class="flex items-center
                       gap-2 text-slate-800
                       font-bold text-sm"
            >

                <svg
                    class="w-5 h-5 text-rose-500"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <path
                        d="M6 2 3 6v14a2 2 0 0 0 2 2h14
                           a2 2 0 0 0 2-2V6l-3-4Z"
                    />

                    <path d="M3 6h18"/>

                    <path d="M16 10a4 4 0 0 1-8 0"/>

                </svg>

                <span>Recent Lost Posts</span>

            </div>


            <a
                href="view-items.php?type=lost"
                class="text-xs font-bold
                       text-rose-600
                       hover:underline"
            >

                View All

            </a>

        </div>


        <div class="space-y-3.5">

            <?php foreach ($lost_items as $item): ?>

                <div
                    class="flex items-center
                           justify-between
                           p-1.5 rounded-xl
                           hover:bg-slate-50
                           transition"
                >

                    <div
                        class="flex items-center
                               gap-3 min-w-0"
                    >

                        <div
                            class="w-10 h-10
                                   rounded-xl
                                   bg-slate-50
                                   border
                                   border-slate-200/80
                                   flex items-center
                                   justify-center
                                   shrink-0
                                   shadow-inner
                                   overflow-hidden"
                        >

                            <?php if (
                                !empty($item['item_image'])
                            ): ?>

                                <img
                                    src="<?php echo htmlspecialchars($item['item_image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                    class="w-full h-full object-cover rounded-xl"
                                >

                            <?php else: ?>

                                <?php
                                echo getItemGraphic(
                                    $item['item_name']
                                );
                                ?>

                            <?php endif; ?>

                        </div>


                        <div class="truncate">

                            <h4
                                class="text-xs
                                       font-bold
                                       text-slate-800
                                       truncate"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['item_name']
                                );
                                ?>

                            </h4>


                            <p
                                class="text-[10.5px]
                                       text-slate-400
                                       font-medium
                                       truncate mt-0.5"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['lost_location']
                                );
                                ?>

                                •

                                <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $item['lost_date']
                                    )
                                );
                                ?>

                            </p>

                        </div>

                    </div>


                    <a
                        href="item-details.php?type=lost&id=<?php echo (int)$item['lost_id']; ?>"
                        class="shrink-0
                               text-xs font-bold
                               px-3 py-1
                               text-rose-600
                               border
                               border-rose-200
                               rounded-lg
                               hover:bg-rose-50
                               transition"
                    >

                        View

                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </div>


    <div
        class="mt-5 pt-3
               border-t border-slate-100
               text-center"
    >

        <a
            href="view-items.php?type=lost"
            class="inline-flex
                   items-center gap-1.5
                   text-xs font-bold
                   text-rose-600
                   hover:text-rose-700
                   transition"
        >

            View All Lost Posts

            <i
                data-lucide="arrow-right"
                class="w-3.5 h-3.5"
            ></i>

        </a>

    </div>

</div>


<!-- ==========================================
     RECENT ALL POSTS
========================================== -->

<div
    class="bg-white
           border border-slate-200/80
           rounded-2xl p-5 shadow-sm
           flex flex-col
           justify-between
           hover:shadow-md transition"
>

    <div>

        <div
            class="flex justify-between
                   items-center mb-4 pb-3
                   border-b border-slate-100"
        >

            <div
                class="flex items-center
                       gap-2 text-slate-800
                       font-bold text-sm"
            >

                <svg
                    class="w-5 h-5 text-amber-500"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                >

                    <path
                        d="M18 8A6 6 0 0 0 6 8
                           c0 7-3 9-3 9h18s-3-2-3-9"
                    />

                    <path
                        d="M13.73 21a2 2 0 0 1-3.46 0"
                    />

                </svg>

                <span>Recent Posts</span>

            </div>


            <a
                href="view-items.php"
                class="text-xs font-bold
                       text-amber-600
                       hover:underline"
            >

                View All

            </a>

        </div>


        <div class="space-y-3.5">

            <?php foreach ($recent_all_items as $item): ?>

                <div
                    class="flex items-center
                           justify-between
                           p-1.5 rounded-xl
                           hover:bg-slate-50
                           transition"
                >

                    <div
                        class="flex items-center
                               gap-3 min-w-0"
                    >

                        <div
                            class="w-10 h-10
                                   rounded-xl
                                   bg-slate-50
                                   border
                                   border-slate-200/80
                                   flex items-center
                                   justify-center
                                   shrink-0
                                   shadow-inner
                                   overflow-hidden"
                        >

                            <?php if (
                                $item['item_type'] === 'lost' &&
                                !empty($item['item_image'])
                            ): ?>

                                <img
                                    src="<?php echo htmlspecialchars($item['item_image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                    class="w-full h-full object-cover rounded-xl"
                                >

                            <?php else: ?>

                                <?php
                                echo getItemGraphic(
                                    $item['item_name']
                                );
                                ?>

                            <?php endif; ?>

                        </div>


                        <div class="truncate">

                            <h4
                                class="text-xs
                                       font-bold
                                       text-slate-800
                                       truncate"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['item_name']
                                );
                                ?>

                            </h4>


                            <p
                                class="text-[10.5px]
                                       text-slate-400
                                       font-medium
                                       truncate mt-0.5"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['location']
                                );
                                ?>

                                •

                                <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $item['post_date']
                                    )
                                );
                                ?>

                            </p>

                        </div>

                    </div>


                    <a
                        href="item-details.php?type=<?php echo htmlspecialchars($item['item_type']); ?>&id=<?php echo (int)$item['id']; ?>"
                        class="shrink-0
                               text-xs font-bold
                               px-3 py-1
                               text-amber-600
                               border
                               border-amber-200
                               rounded-lg
                               hover:bg-amber-50
                               transition"
                    >

                        View

                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </div>


    <div
        class="mt-5 pt-3
               border-t border-slate-100
               text-center"
    >

        <a
            href="view-items.php"
            class="inline-flex
                   items-center gap-1.5
                   text-xs font-bold
                   text-amber-600
                   hover:text-amber-700
                   transition"
        >

            View All Recent Posts

            <i
                data-lucide="arrow-right"
                class="w-3.5 h-3.5"
            ></i>

        </a>

    </div>

</div>


<!-- ==========================================
     RECENT FOUND
========================================== -->

<div
    class="bg-white
           border border-slate-200/80
           rounded-2xl p-5 shadow-sm
           flex flex-col
           justify-between
           hover:shadow-md transition"
>

    <div>

        <div
            class="flex justify-between
                   items-center mb-4 pb-3
                   border-b border-slate-100"
        >

            <div
                class="flex items-center
                       gap-2 text-slate-800
                       font-bold text-sm"
            >

                <svg
                    class="w-5 h-5 text-emerald-500"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <polyline
                        points="20 12 20 22 4 22 4 12"
                    />

                    <rect
                        x="2"
                        y="7"
                        width="20"
                        height="5"
                    />

                    <line
                        x1="12"
                        y1="22"
                        x2="12"
                        y2="7"
                    />

                    <path
                        d="M12 7H7.5a2.5 2.5 0 0 1 0-5
                           C11 2 12 7 12 7z"
                    />

                    <path
                        d="M12 7h4.5a2.5 2.5 0 0 0 0-5
                           C13 2 12 7 12 7z"
                    />

                </svg>

                <span>Recent Found Posts</span>

            </div>


            <a
                href="view-items.php?type=found"
                class="text-xs font-bold
                       text-emerald-600
                       hover:underline"
            >

                View All

            </a>

        </div>


        <div class="space-y-3.5">

            <?php foreach ($found_items as $item): ?>

                <div
                    class="flex items-center
                           justify-between
                           p-1.5 rounded-xl
                           hover:bg-slate-50
                           transition"
                >

                    <div
                        class="flex items-center
                               gap-3 min-w-0"
                    >

                        <div
                            class="w-10 h-10
                                   rounded-xl
                                   bg-slate-50
                                   border
                                   border-slate-200/80
                                   flex items-center
                                   justify-center
                                   shrink-0
                                   shadow-inner"
                        >

                            <?php
                            echo getItemGraphic(
                                $item['item_name']
                            );
                            ?>

                        </div>


                        <div class="truncate">

                            <h4
                                class="text-xs
                                       font-bold
                                       text-slate-800
                                       truncate"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['item_name']
                                );
                                ?>

                            </h4>


                            <p
                                class="text-[10.5px]
                                       text-slate-400
                                       font-medium
                                       truncate mt-0.5"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['found_location']
                                );
                                ?>

                                •

                                <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $item['found_date']
                                    )
                                );
                                ?>

                            </p>

                        </div>

                    </div>


                    <a
                        href="item-details.php?type=found&id=<?php echo (int)$item['found_id']; ?>"
                        class="shrink-0
                               text-xs font-bold
                               px-3 py-1
                               text-emerald-600
                               border
                               border-emerald-200
                               rounded-lg
                               hover:bg-emerald-50
                               transition"
                    >

                        View

                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </div>


    <div
        class="mt-5 pt-3
               border-t border-slate-100
               text-center"
    >

        <a
            href="view-items.php?type=found"
            class="inline-flex
                   items-center gap-1.5
                   text-xs font-bold
                   text-emerald-600
                   hover:text-emerald-700
                   transition"
        >

            View All Found Posts

            <i
                data-lucide="arrow-right"
                class="w-3.5 h-3.5"
            ></i>

        </a>

    </div>

</div>


</div>


<!-- ==========================================
     FOOTER
========================================== -->

<footer
    class="text-center
           text-xs text-slate-400
           py-6 font-medium"
>

    © 2026 Campus Lost & Found Management System.
    All rights reserved.

</footer>


</main>

</div>


<!-- ==========================================
     MOBILE SIDEBAR SCRIPT
========================================== -->

<script>

    lucide.createIcons();

    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileSidebar = document.getElementById('mobileSidebar');

    if (mobileMenuButton && mobileSidebar) {

        mobileMenuButton.addEventListener('click', function () {

            if (window.innerWidth < 1024) {

                mobileSidebar.classList.toggle('hidden');

                mobileMenuButton.setAttribute(
                    'aria-expanded',
                    !mobileSidebar.classList.contains('hidden')
                );

            }

        });


        // Close sidebar after clicking any sidebar link

        const sidebarLinks = mobileSidebar.querySelectorAll('a');

        sidebarLinks.forEach(function (link) {

            link.addEventListener('click', function () {

                if (window.innerWidth < 1024) {

                    mobileSidebar.classList.add('hidden');

                    mobileMenuButton.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                }

            });

        });


        // Reset sidebar when switching to desktop screen

        window.addEventListener('resize', function () {

            if (window.innerWidth >= 1024) {

                mobileSidebar.classList.remove('hidden');

                mobileMenuButton.setAttribute(
                    'aria-expanded',
                    'false'
                );

            } else {

                mobileSidebar.classList.add('hidden');

                mobileMenuButton.setAttribute(
                    'aria-expanded',
                    'false'
                );

            }

        });

    }

</script>


<?php

if (isset($conn)) {

    $conn->close();

}

?>

</body>

</html>
