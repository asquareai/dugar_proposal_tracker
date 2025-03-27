<?php
session_start();
include 'config.php';

if (isset($_SESSION['user'])) {
    $username = $_SESSION['user'];
    $current_session_token = $_SESSION['session_token'];

    // Check session token in DB
    $query = "SELECT session_token FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $db_session_token);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($db_session_token !== $current_session_token) {
        $_SESSION['multi_login_detected'] = true; // Store message
    }
}


// Redirect to login page
header("Location: login.php?error=multiple");
exit();
?>
