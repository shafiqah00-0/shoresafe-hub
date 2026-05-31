<?php
include("connect.php");
$sql = "SELECT * FROM user";
$result = mysqli_query($conn,$sql);
if (mysqli_num_rows($result) > 0) {
// output data of each row
//Fetches a result row as an associative array
echo "<table border='1'>";
echo "<tr><th>Username</th><th>Name</th>";
echo "<th>IC</th><th>Address</th></tr>";
while($row = mysqli_fetch_assoc($result))
{
echo "<tr>"; echo "<td>".$row['username']."</td>";
echo "<td>".$row['name']."</td>";
echo "<td>".$row['ic']."</td>";
echo "<td>".$row['address']."</td>";
echo "</tr>";
}
echo "</table>";
}
else {
 echo "0 results";
}
//Freeing all memory associated with it
mysqli_free_result($result);
//Closes specified connection
mysqli_close($conn);
?>