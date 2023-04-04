<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require 'common/database.php';
require 'common/helper.php';
require 'dbinsert/insertdata.php';
require 'dbread/reportdata.php';

$echoResponse=array();
$traceMessage = "";
$resultData = "";
$responseArray = array();
fillResponseArray();
$debugMessage = "";
$fatalFlag = false;
$action = "REP";
$src = "WEB";
$scriptVersion = "1.0";
$dateYYYYMMDD = "";
$prodData = array();
$consData = array();
$netData = array();
$rowCount = 0;
$echoResponse["version"] = $scriptVersion;

if (isset($_GET['action']))
    $action = testinput($_GET['action']);

if (isset($_GET['date']))
    $dateYYYYMMDD = testinput($_GET['date']);
else
    $dateYYYYMMDD = "";

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error)
{
  	die("Connection failed: " . $conn->connect_error);
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["0"];
    echo json_encode($echoResponse);
    exit();
}
else
{
    $conn->autocommit(TRUE);
    $dailyUsageSelQuery = "SELECT EnvoyLocalReadingDate, EnvoyLocalReadingTime, EnvoyLocalCons, EnvoyLocalProd, EnvoyLocalNet FROM EnvoyLocalReadings WHERE EnvoyLocalReadingDate >= ?";
    $dailyUsageSelStmt = $conn->prepare($dailyUsageSelQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

if ($action == "REP" && $dateYYYYMMDD == "")
    $dateYYYYMMDD = YYYYMMDDFromEpoch(time());

if ($action == "REP")
{
    if($dailyUsageSelStmt->bind_param("s",$dateYYYYMMDD))
    {
        $echoResponse["EnvoyDate"] = dateinDMY($dateYYYYMMDD);
        $dailyUsageSelStmt->execute();
        $result = $dailyUsageSelStmt->get_result();
        $rowCount = mysqli_num_rows($result);
        if($rowCount > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $readingTimeEpoch = $row["EnvoyLocalReadingTime"];
                $tempProdArray = array();
                $tempConsArray = array();
                $tempNetArray = array();
                $tempProdArray["time"] = $tempConsArray["time"] = $tempNetArray["time"] = timeinHHMMSSFromEpoch($readingTimeEpoch);
                $tempProdArray["value"] = $row["EnvoyLocalProd"];
                $tempConsArray["value"] = $row["EnvoyLocalCons"];
                $tempNetArray["value"] = $row["EnvoyLocalNet"];
                array_push($prodData,$tempProdArray);
                array_push($consData,$tempConsArray);
                array_push($netData,$tempNetArray);
            }
            $echoResponse["result"] = "OK";
            $echoResponse["ProdData"] = $prodData;
            $echoResponse["ConsData"] = $consData;
            $echoResponse["NetData"] = $netData;
        }
        else
        {
            $echoResponse["result"] = "NoData";
        }

    }
}
else
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    echo json_encode($echoResponse);
    exit();
}
echo json_encode($echoResponse);
closeConnection();
?>