<?php 
$dbhost = 'localhost';
$dbname = 'realestate';
$dbuser = 'root';
$dbpassword = '';

// create DB connection
$conn = mysqli_connect($dbhost, $dbuser, $dbpassword, database: $dbname);


// check DB connection
if (mysqli_connect_errno()) {
    die('Sorry your database was dropped'. mysqli_connect_error());
} else{
    echo'Database successful';
}

?>