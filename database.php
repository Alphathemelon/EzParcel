<?php
 
$servername = "lrgs.ftsm.ukm.my";
$username = "a203590";
$password = "largeredfox";
$dbname = "a203590";
 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
	die('Connection failed: ' . $conn->connect_error);
}

?>