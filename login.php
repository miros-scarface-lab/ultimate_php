<?php

include "db.php";
configureSecureSession();
session_start();

/* =========================
   LOGIN FORM CHECK
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit();
}


/* =========================
   GET FORM DATA
========================= */

$email_or_id = trim($_POST["email"] ?? "");
$password    = $_POST["password"] ?? "";


/* =========================
   EMPTY CHECK
========================= */

if ($email_or_id === "" || $password === "") {

    echo "<script>
        alert('Please enter Student ID/Email and Password.');
        window.location.href='login.html';
    </script>";

    exit();
}


/* =========================
   FIND USER
========================= */

$sql = "
    SELECT
        user_id,
        full_name,
        student_id,
        email,
        department,
        password,
        role
    FROM users
    WHERE student_id = ?
       OR email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("ss", $email_or_id, $email_or_id);

$stmt->execute();

$result = $stmt->get_result();


/* =========================
   USER FOUND
========================= */

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();


    /* =========================
       PASSWORD CHECK
    ========================= */

    if (verifyPasswordAndUpgrade(
        $password,
        $user["password"],
        $conn,
        'users',
        'user_id',
        (int) $user["user_id"]
    )) {

        /* =========================
           SAVE LOGIN SESSION
        ========================= */

        $_SESSION["user_id"]    = $user["user_id"];
        $_SESSION["full_name"]  = $user["full_name"];
        $_SESSION["student_id"] = $user["student_id"];
        $_SESSION["email"]      = $user["email"];
        $_SESSION["department"] = $user["department"];
        $_SESSION["role"]       = $user["role"];
        session_regenerate_id(true);


        /* =========================
           GO TO DASHBOARD
        ========================= */

        header("Location: dashboard.php");
        exit();

    } else {

        echo "<script>
            alert('Wrong password.');
            window.location.href='login.html';
        </script>";

        exit();
    }

} else {

    echo "<script>
        alert('Student ID or Email not found.');
        window.location.href='login.html';
    </script>";

    exit();
}


/* =========================
   CLOSE
========================= */

$stmt->close();
$conn->close();

?>