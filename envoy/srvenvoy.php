<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require '/var/www/mysite/common/database.php';
require '/var/www/mysite/common/helper.php';
$echoResponse=array();
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error)
{
  	die("Connection failed: " . $conn->connect_error);
    exit();
}
else
{
    $conn->autocommit(TRUE);
    $envoyQuery = "SELECT EnvoyDataTimeStamp, EnvoyData FROM Envoy ORDER BY EnvoyDataTimeStamp DESC LIMIT 0,1";
    $maxminStmt = $conn->prepare($envoyQuery);
    $maxminStmt->execute();
    $result = $maxminStmt->get_result();
    if (mysqli_num_rows($result) == 0)
        exit();
    
    while ($row = $result->fetch_assoc())
    {
        echo $row["EnvoyData"];
        closeConnection();
    }
}
?>
