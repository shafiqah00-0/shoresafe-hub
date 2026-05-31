<?php
//Initialise the session
session_start();
include("connect.php");
$sql = "SELECT * FROM user WHERE
username='".$_SESSION['username']."'";
$result = mysqli_query($conn,$sql);
if (mysqli_num_rows($result) > 0) {
// output data of each row
//Fetches a result row as an associative array
while ($row = mysqli_fetch_assoc($result)){
echo $row['name']." ".$row['ic']." ".$row['address'];
}
}
else {
 echo "0 results";
}
//Freeing all memory associated with it
mysqli_free_result($result);
//Closes specified connection
mysqli_close($conn);
?>
<html>
<body>
<p><a href="menu.php">Back to Menu</a></p>
</body> </html>