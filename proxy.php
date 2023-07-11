<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$url = "http://lamp.local/envoy/srvenvoy.php";
$data = file_get_contents($url);
header("Content-Type: application/json");
echo $data;
?>
