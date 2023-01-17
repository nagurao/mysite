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
$action = "INS";
$src = "SCRIPT";
$scriptVersion = "1.0";

$envoyLocalDateTime = "";
$envoyLocalProduction = 0;
$envoyLocalConsumption = 0;
$envoyLocalNet = 0;
$envoyLocalProductionDay = 0;
$envoyLocalConsumptionDay = 0;

$echoResponse["version"] = $scriptVersion;
if (isset($_GET['action']))
    $action = testinput($_GET['action']);

if (isset($_GET['src']))
    $src = testinput($_GET['src']);

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
    $insertEnvoyLocalQuery = "INSERT INTO EnvoyLocalReadings (EnvoyLocalReadingTime, EnvoyLocalConsRaw, EnvoyLocalCons, EnvoyLocalProdRaw, EnvoyLocalProd,EnvoyLocalNetRaw,EnvoyLocalNet,EnvoyLocalProdDayRaw,EnvoyLocalProdDay,EnvoyLocalConsDayRaw,EnvoyLocalConsDay) VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertEnvoyLocalStmt = $conn->prepare($insertEnvoyLocalQuery);
    $lastEnvoyLocalSelQuery = "SELECT EnvoyLocalReadingTime, EnvoyLocalCons, EnvoyLocalProd, EnvoyLocalNet, EnvoyLocalProdDay, EnvoyLocalConsDay  FROM EnvoyLocalReadings ORDER BY EnvoyLocalReadingTime DESC LIMIT 0,1";
    $lastEnvoyLocalSelStmt = $conn->prepare($lastEnvoyLocalSelQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

if($action == "INS")
{

    insertLocalEnvoyData();
    $envoyLocalDateTimeFormatted = new DateTime(date('r', $envoyLocalDateTime));
    $envoyLocalDateTime = $envoyLocalDateTimeFormatted->format("dMY H:i");
    $echoResponse["envoyLocalReadingDateTime"] = $envoyLocalDateTime;
    $echoResponse["envoyLocalProduction"] = sprintf("%07.2f",$envoyLocalProduction);
    $echoResponse["envoyLocalConsumption"] = sprintf("%07.2f",$envoyLocalConsumption);
    $echoResponse["envoyLocalNet"] = sprintf("%07.2f",$envoyLocalNet);
    $echoResponse["envoyLocalProductionDay"] = sprintf("%07.2f",$envoyLocalProductionDay);
    $echoResponse["envoyLocalConsumptionDay"] = sprintf("%07.2f",$envoyLocalConsumptionDay);
    $echoResponse["result"] = "OK";
    $echoResponse["message"] = $responseArray["6"];
}
elseif ($action == "REP")
{
    fetchEnvoyLocalData();
    $echoResponse["result"] = "OK";  
    $echoResponse["message"] = $responseArray["7"];  
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