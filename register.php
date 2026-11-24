<?php

include "db.php";
configureSecureSession();
session_start();

/* =========================
   REGISTER FORM CHECK
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit();
}


/* =========================
   GET FORM DATA
========================= */

$full_name        = trim($_POST["full_name"] ?? "");
$student_id       = trim($_POST["student_id"] ?? "");
$email            = trim($_POST["email"] ?? "");
$department       = trim($_POST["department"] ?? "");
$password         = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";


/* =========================
   EMPTY CHECK
========================= */

if (
    $full_name === "" ||
    $student_id === "" ||
    $email === "" ||
    $department === "" ||
    $password === "" ||
    $confirm_password === ""
) {

    echo "<script>
        alert('Please fill in all fields.');
        window.location.href='register.html';
    </script>";

    exit();
}


/* =========================
   PASSWORD MATCH CHECK
========================= */

if ($password !== $confirm_password) {

    echo "<script>
        alert('Passwords do not match.');
        window.location.href='register.html';
    </script>";

    exit();
}


/* =========================
   CHECK EXISTING USER
========================= */

$check_sql = "
    SELECT user_id
    FROM users
    WHERE student_id = ?
       OR email = ?
    LIMIT 1
";

$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    die("Database Error: " . $conn->error);
}

$check_stmt->bind_param(
    "ss",
    $student_id,
    $email
);

$check_stmt->execute();

$check_result = $check_stmt->get_result();


if ($check_result->num_rows > 0) {

    echo "<script>
        alert('Student ID or Email already exists.');
        window.location.href='register.html';
    </script>";

    $check_stmt->close();
    $conn->close();

    exit();
}

$check_stmt->close();


/* =========================
   DEFAULT ROLE
========================= */

$role = "user";


/* =========================
   INSERT USER
========================= */

$sql = "
    INSERT INTO users
    (
        full_name,
        student_id,
        email,
        department,
        password,
        role
    )
    VALUES (?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}


/* =========================
   BIND DATA
========================= */

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt->bind_param(
    "ssssss",
    $full_name,
    $student_id,
    $email,
    $department,
    $hashed_password,
    $role
);


/* =========================
   SAVE USER
========================= */

if ($stmt->execute()) {

    echo "<script>
        alert('Registration successful! Please login.');
        window.location.href='login.html';
    </script>";

} else {

    echo "<script>
        alert('Registration failed: " . addslashes($stmt->error) . "');
        window.location.href='register.html';
    </script>";
}


/* =========================
   CLOSE
========================= */

$stmt->close();
$conn->close();

?>