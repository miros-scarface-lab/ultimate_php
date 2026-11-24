<?php

session_start();
include "db.php";
requireAdmin();

/*
    Existing Approved Lost Items
*/

$lost_sql = "
    SELECT *
    FROM lost_items
    WHERE status = 'Approved'
";

$lost_result = $conn->query($lost_sql);


/*
    Existing Approved / Available Found Items
*/

$found_sql = "
    SELECT *
    FROM found_items
    WHERE status IN ('Approved', 'Available')
";


if (!$lost_result) {
    die("Lost Items Error: " . $conn->error);
}


// Load found items once instead of re-querying for every lost item
$found_result = $conn->query($found_sql);

if (!$found_result) {
    die("Found Items Error: " . $conn->error);
}

$found_rows = $found_result->fetch_all(MYSQLI_ASSOC);


while ($lost = $lost_result->fetch_assoc()) {

    /*
        প্রতিটি Lost Item-এর জন্য
        Found Items নতুন করে query করা হবে
    */

    foreach ($found_rows as $found) {


        /* =========================
           ITEM NAME = 40
           ========================= */

        $lost_name = strtolower(trim($lost['item_name']));
        $found_name = strtolower(trim($found['item_name']));

        similar_text(
            $lost_name,
            $found_name,
            $name_similarity
        );

        $item_name_score = round($name_similarity);


        /* =========================
           CATEGORY = 25
           ========================= */

        if (
            strtolower(trim($lost['category'])) ==
            strtolower(trim($found['category']))
        ) {

            $category_score = 100;

        } else {

            $category_score = 0;

        }


        /* =========================
           LOCATION = 25
           ========================= */

        $lost_location =
            strtolower(trim($lost['lost_location']));

        $found_location =
            strtolower(trim($found['found_location']));


        similar_text(
            $lost_location,
            $found_location,
            $location_similarity
        );


        $location_score =
            round($location_similarity);


        /* =========================
           DATE = 10
           ========================= */

        $lost_date =
            strtotime($lost['lost_date']);

        $found_date =
            strtotime($found['found_date']);


        $difference =
            abs($lost_date - $found_date)
            / (60 * 60 * 24);


        if ($difference == 0) {

            $date_score = 100;

        } elseif ($difference <= 1) {

            $date_score = 80;

        } elseif ($difference <= 3) {

            $date_score = 60;

        } elseif ($difference <= 7) {

            $date_score = 40;

        } else {

            $date_score = 0;

        }


        /* =========================
           FINAL 100 MARKS
           ========================= */

        $match_percentage =
            ($item_name_score * 40 / 100) +
            ($category_score * 25 / 100) +
            ($location_score * 25 / 100) +
            ($date_score * 10 / 100);


        $match_percentage =
            round($match_percentage, 2);


        /*
            50% বা তার বেশি হলে Match
        */

        if ($match_percentage >= 50) {


            /*
                Duplicate check
            */

            $check_sql = "
                SELECT match_id
                FROM matches
                WHERE lost_id = '{$lost['lost_id']}'
                AND found_id = '{$found['found_id']}'
            ";

            $check_result =
                $conn->query($check_sql);


            if ($check_result->num_rows == 0) {


                /*
                    MATCH INSERT
                */

                $insert_sql = "
                    INSERT INTO matches
                    (
                        lost_id,
                        found_id,
                        item_name_score,
                        category_score,
                        location_score,
                        date_score,
                        match_percentage,
                        status,
                        finder_status,
                        owner_status
                    )
                    VALUES
                    (
                        '{$lost['lost_id']}',
                        '{$found['found_id']}',
                        '$item_name_score',
                        '$category_score',
                        '$location_score',
                        '$date_score',
                        '$match_percentage',
                        'Pending',
                        'Pending',
                        'Pending'
                    )
                ";


                if (!$conn->query($insert_sql)) {

                    echo "Match Insert Error: "
                         . $conn->error
                         . "<br>";

                }

            }

        }

    }

}


/*
    Done
*/

echo "
<script>

alert('Existing Lost and Found items matched successfully!');

window.location.href='my-matches.php';

</script>
";


$conn->close();

?>