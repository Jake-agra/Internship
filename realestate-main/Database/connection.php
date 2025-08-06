<?php
$dbhost="localhost";
$dbname="real_estate";
$dbpassword="";
$dbuser="root";


// new connection
$conn=mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

if($conn->connect_error){
    die("Hello, db connection drop" .$conn->connect_error);
} 


?>