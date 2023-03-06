<?php
session_start();

$host = "localhost";    /* Host name */ 
$user = "sheshu";         /* User */
$password = "Sheshu@123";         /* Password */
$dbname = "covid";   /* Database name */

// Create connection
$conn = mysqli_connect($host, $user, $password,$dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


