<?php

include "db.php";
configureSecureSession();
session_start();

/* ==========================================
   ALREADY LOGGED IN CHECK
========================================== */

if (isset($_SESSION['admin_username'])) {

    header("Location: admin-dashboard.php");
    exit();

}


/* ==========================================
   LOGIN PROCESS
========================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';


    /* ==========================================
       CHECK USERNAME
    ========================================== */

    $sql = "SELECT * FROM admins WHERE username = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database Error: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();


    /* ==========================================
       CHECK LOGIN
    ========================================== */

    if ($result->num_rows == 1) {

        $admin = $result->fetch_assoc();


        if (verifyPasswordAndUpgrade(
            $password,
            $admin['password'],
            $conn,
            'admins',
            'username',
            $admin['username']
        )) {

            session_regenerate_id(true);
            $_SESSION['admin_username'] = $admin['username'];

            header("Location: admin-dashboard.php");
            exit();

        } else {

            $error = "Invalid Username or Password!";

        }

    } else {

        $error = "Invalid Username or Password!";

    }

    $stmt->close();
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Login</title>

    <link rel="stylesheet" href="css/style.css">

</head>


<body>

<div class="container">

    <h1>Admin Login</h1>


    <?php if (isset($error)) { ?>

        <p style="color:red;">
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php } ?>


    <form method="POST">

        <input
            type="text"
            name="username"
            placeholder="Username"
            required
        >

        <br><br>


        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <br><br>


        <button type="submit">
            Login
        </button>

    </form>

</div>

</body>

</html>