<?php
session_start();

$_SESSION = [];
session_destroy();

header("Location: /interface/login.html"); 
exit;