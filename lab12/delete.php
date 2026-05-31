<?php
//Initialise the session
session_start();
include("connect.php");
$sql = "DELETE FROM user WHERE
username='".$_SESSION['username']."'";
if (mysqli_query($conn, $sql)) {
 echo "Record updated successfully";
} else {
 echo "Error updating record: " . mysqli_error($conn);
}
//Closes specified connection
mysqli_close($conn);
?>
<html>
<body>
<p><a href="login.php">Thank you.</a></p>
</body>
</html> 