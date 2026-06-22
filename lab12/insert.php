<?php
include("connect.php");
$name = $_POST['fname'];
$ic = $_POST['ic'];
$addr = $_POST['address'];
$user = $_POST['username'];
$pswd = $_POST['password'];
$cpswd = $_POST['cpass'];
$sql = "INSERT INTO user (username, password, name, ic,
address) VALUES('$user', '$pswd', '$name', '$ic',
'$addr');";
if (pg_query($conn, $sql)) {
echo "New record created successfully <br>";
} else {
echo "Error: " . $sql . "<br>" . pg_last_error($conn);
}
//Closes specified connection
pg_close($conn);
?>
<html>
<body>
<p><a href="login.php">Login Now!</a></p>
</body>
</html> 