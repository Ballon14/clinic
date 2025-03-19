<?php 

    $hostname = "localhost";
    $username = "root";
    $password = "";
    $database = "klinik-bima";
    

    $conn = mysqli_connect($hostname, $username, $password, $database);

    if ($conn-> connect_error){
        echo "error connecting to database";
        die("error");
    }

?>
