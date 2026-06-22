<?php
//Initialise the session
session_start();
include("connect.php");
$name = $_POST['fname'];
$ic = $_POST['ic'];
$addr = $_POST['address'];
$sql = "UPDATE user SET name='".$name."', ic='".$ic."',
address='".$addr."' WHERE
username='".$_SESSION['username']."'";
if (pg_query($conn, $sql)) {
 echo "Record updated successfully";
} else {
 echo "Error updating record: " . pg_last_error($conn);
}
//Closes specified connection
pg_close($conn);
?>
<html>
<body>
<p><a href="menu.php">Back to Menu</a></p>
</body>
</html>