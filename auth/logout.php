<?php
// Start session to access user info
session_start();

// Log activity before destroying session
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["id"])){
    require_once "../includes/session.php";
    $user_id = $_SESSION["id"];
    $username = $_SESSION["username"] ?? 'Unknown';
    
    // Log the logout
    if(tableExists('activity_logs')){
        $sql = "INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            $action = 'User Logout';
            $details = 'User ' . $username . ' logged out.';
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $details);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("location: login.php");
exit;
?>
