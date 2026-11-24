<?php

session_start();
include "db.php";
requireAdmin();

/*
    LOST ITEMS
    শুধু Approved Lost Items নেওয়া হবে
*/

$lost_sql = "
    SELECT *
    FROM lost_items
    WHERE status = 'Approved'
";

$lost_result = $conn->query($lost_sql);


/*
    FOUND ITEMS
    Approved অথবা Available Found Items নেওয়া হবে
*/

$found_sql = "
    SELECT *
    FROM found_items
    WHERE status IN ('Approved', 'Available')
";

$found_result = $conn->query($found_sql);

// Load found items once instead of re-querying for every lost item
$found_rows = $found_result ? $found_result->fetch_all(MYSQLI_ASSOC) : [];


if ($lost_result && $found_result) {

    while ($lost = $lost_result->fetch_assoc()) {

        /*
            প্রতিটি Lost Item-এর সাথে
            প্রতিটি Found Item compare করা হবে
        */

        foreach ($found_rows as $found) {


            /* =====================================
               1. ITEM NAME SCORE = 40
               ===================================== */

            $lost_name = strtolower(trim($lost['item_name']));
            $found_name = strtolower(trim($found['item_name']));

            if ($lost_name == $found_name) {

                $item_name_score = 100;

            } else {

                similar_text(
                    $lost_name,
                    $found_name,
                    $similarity
                );

                $item_name_score = $similarity;
            }


            /* =====================================
               2. CATEGORY SCORE = 25
               ===================================== */

            if (
                strtolower(trim($lost['category'])) ==
                strtolower(trim($found['category']))
            ) {

                $category_score = 100;

            } else {

                $category_score = 0;
            }


            /* =====================================
               3. LOCATION SCORE = 25
               ===================================== */

            $lost_location =
                strtolower(trim($lost['lost_location']));

            $found_location =
                strtolower(trim($found['found_location']));


            if ($lost_location == $found_location) {

                $location_score = 100;

            } else {

                similar_text(
                    $lost_location,
                    $found_location,
                    $location_similarity
                );

                $location_score = $location_similarity;
            }


            /* =====================================
               4. DATE SCORE = 10
               ===================================== */

            $lost_date = strtotime($lost['lost_date']);
            $found_date = strtotime($found['found_date']);

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


            /* =====================================
               FINAL MATCH PERCENTAGE = 100
               
               Item Name = 40
               Category  = 25
               Location  = 25
               Date      = 10
               ===================================== */

            $match_percentage =
                ($item_name_score * 40 / 100) +
                ($category_score * 25 / 100) +
                ($location_score * 25 / 100) +
                ($date_score * 10 / 100);


            /*
                দুই decimal পর্যন্ত রাখবে
            */

            $match_percentage =
                round($match_percentage, 2);


            /* =====================================
               MATCH THRESHOLD
               50 বা তার বেশি হলে Match
               ===================================== */

            if ($match_percentage >= 50) {


                /*
                    একই Lost + Found pair
                    আগে থেকেই matches table-এ আছে কিনা
                */

                $check_sql = "
                    SELECT match_id
                    FROM matches
                    WHERE lost_id = '{$lost['lost_id']}'
                    AND found_id = '{$found['found_id']}'
                ";

                $check_result =
                    $conn->query($check_sql);


                /*
                    আগে Match না থাকলে Insert করবে
                */

                if ($check_result->num_rows == 0) {


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


                    $conn->query($insert_sql);
                }
            }
        }
    }
}


/*
    Matching শেষ হলে My Matches page-এ যাবে
*/

echo "
<script>

alert('Matching process completed!');

window.location.href='my-matches.php';

</script>
";


$conn->close();

?>