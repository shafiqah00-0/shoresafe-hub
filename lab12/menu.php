<?php
//Initialise the session
session_start();
?>
<html>
<head>
<title>Menu Info</title>
</head>
<body>
<p></p>
<?php
// Set session variables
if (!empty($_POST['username']) &&
!empty($_POST['password']))
{
$_SESSION['username'] = $_POST['username'];
$_SESSION['password'] = $_POST['password'];
include("connect.php");
$sql = "SELECT * FROM user WHERE
username='".$_SESSION['username']."' AND
password='".$_SESSION['password']."'";
$result = mysqli_query($conn, $sql);
$row = mysqli_num_rows($result);
// Closes specified connection
mysqli_close($conn);
}
else{
echo "Login Fail";
session_unset();
header('Refresh: 2; URL = login.php');
}
?>
<b>Main Menu</b>
<hr>
<a href="view.php">View myself</a><br />
<a href="userinfo.php">Update myself</a><br
/>
<a href="delete.php">Delete myself</a><br
/>
<a href="login.php">Logout</a>
</body>
</html> 