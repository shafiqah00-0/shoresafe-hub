<?php
//Initialise the session
session_start();
include("connect.php");
$sql = "SELECT * FROM user WHERE
username='".$_SESSION['username']."'";
$result = pg_query($conn,$sql);
if (pg_num_rows($result) > 0) {
// output data of each row
//Fetches a result row as an associative array
while ($row = pg_fetch_assoc($result)){
echo $row['name']." ".$row['ic']." ".$row['address'];
}
}
else {
 echo "0 results";
}
//Freeing all memory associated with it
pg_free_result($result);
//Closes specified connection
pg_close($conn);
?>
<html>
<body>
<p><a href="menu.php">Back to Menu</a></p>
</body> </html>