<?php
/**
 * Created by PhpStorm.
 * User: Hiep Quach
 * Date: 4/6/2017
 * Time: 10:51 PM
 */

$entityBody = file_get_contents('php://input');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "l2d_points";
//// Create connection
//$conn = new mysqli($servername, $username, $password, $dbname);
//
//$object = json_decode($entityBody);
//$latlng = $object->coordinates;
//$lat = $latlng[1];
//$lng = $latlng[0];
//$country_code = $object->country_code;
//$sql = "INSERT INTO `points`(`lat`,`lng`,`country_code`) VALUES ($lat,$lng,'$country_code');";
//
//if ($conn->query($sql) === TRUE) {
//    $data = ["message" => "New record created successfully", "status" => true, "error_code" => 0] ;
//} else {
//    $data = ["message" => "Error: " . $sql . "<br>" . $conn->error, "status" => false, "error_code" => 1] ;
//}
//
//// Check connection
//if ($conn->connect_error) {
//    die("Connection failed: " . $conn->connect_error);
//}
//$conn->close();

// create curl resource
$ch = curl_init();

// set url
curl_setopt($ch, CURLOPT_URL, "http://haonguyen.me:5000/create");

//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch, CURLOPT_POSTFIELDS,$entityBody);
curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: application/json'));

// $output contains the output string
$output = curl_exec($ch);

// close curl resource to free up system resources
curl_close($ch);

header('application/json; charset=UTF-8');
echo $output;