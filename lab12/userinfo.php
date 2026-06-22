<?php
//Initialise the session
session_start();
include("connect.php");
$sql = "SELECT * FROM user WHERE
username='".$_SESSION['username']."'";
$result = pg_query($conn, $sql);
if (pg_num_rows($result) > 0) {
 // output data of each row
 $row = pg_fetch_assoc($result);
 echo $row['name']." ".$row['ic']." ".$row['address'];

}
else {
 echo "0 results: Error running MySQL query ";
}
?>
<html>
<head>
<title>Update User Info</title>
</head>
<body>
<b>Update User Information</b>
<hr>
<form method="post" action="update.php">
Name: <input type="text" name="fname" value="
<?php echo
$row['name']; ?>"><br />
I/C: <input type="text" name="ic" value="
<?php echo
$row['ic']; ?>"><br />
Address: <input type="text" name="address" value="
<?php echo
$row['address']; ?>"><br />
<br />
<input type="submit" value="Update">
<input type="reset" value="Reset">
</form>
<p><a href="menu.php">Back to Menu</a></p>
</body>
</html>
<?
pg_free_result($result);
//Closes specified connection mysqli_close($conn);
?>