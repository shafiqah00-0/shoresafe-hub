<?php
//Initialise the session
session_start();
?>
<?php
if (isset($_SESSION['username']))
{
//Destroy the whole session
unset($_SESSION['username']);
unset($_SESSION['password']);
echo 'You have cleaned session';
header('Refresh: 2; URL = login.php');
}
?>
<html>
<head>
<title>Login Here</title>
</head>
<body>
<b>Login Information</b>
<hr>
<form method="POST" action="menu.php">
Username: <input type="text" name="username"><br />
Password: <input type="password" name="password"><br />
<input type="submit" value="Login">
<input type="reset" value="Reset">
</form>
<p><b>New user? </b><a href="register.html">Sign-up
now!</a></p> </body>
</html> 