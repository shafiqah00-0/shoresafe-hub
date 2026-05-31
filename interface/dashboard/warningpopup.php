<?php
// dismiss_alert.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Sets the session flag so the popup stays hidden when clicking pages
$_SESSION['public_warning_dismissed'] = true;
http_response_code(200);
?>