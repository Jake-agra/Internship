<?php
// This will help us start our session
// Unsert all session variables
// remove all session from cache
// redirect to the index page




if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

if(ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(),'', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"],
);

session_destroy();


// Redirect to index.php after logout

header("Location: index.php");
exit();
}






?>