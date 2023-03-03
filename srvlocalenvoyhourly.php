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

$envoyDateEpoch = 0;
$envoyProductionPrevHour = 0;
$envoyConsumptionPrevHour = 0;
$envoyProductionCurrHour = 0;
$envoyConsumptionCurrHour = 0;
$envoyProductionDay = 0;
$envoyConsumptionDay = 0;

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
    $insertEnvoyHourlyQuery = "INSERT INTO EnvoyHourlyReadings (EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay) VALUE (?, ?, ?, ?, ?, ?)";
    $insertEnvoyHourlyStmt = $conn->prepare($insertEnvoyHourlyQuery);
    $lastEnvoyHourlySelQuery = "SELECT EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay,EnvoyReadingTimestamp  FROM EnvoyHourlyReadings ORDER BY EnvoyReadingTimeEpoch DESC LIMIT 0,1";
    $lastEnvoyHourlySelStmt = $conn->prepare($lastEnvoyHourlySelQuery);
    $prevEnvoyHourlySelQuery = "SELECT EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay,EnvoyReadingTimestamp  FROM EnvoyHourlyReadings ORDER BY EnvoyReadingTimeEpoch DESC LIMIT 1,1";
    $prevEnvoyHourlySelStmt = $conn->prepare($prevEnvoyHourlySelQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

if($action != "INS" && $action != "REP")
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    echo json_encode($echoResponse);
    exit();
}

fetchEnvoyHourlyData();
if ($envoyDateEpoch == 0 && $action == "REP")
{
    $echoResponse["result"] = "NoData";
    $echoResponse["message"] = $responseArray["10"];
}
else
{
    if($action == "INS" && $src == "SCRIPT")
        insertLocalEnvoyHourlyData($envoyProductionPrevHour,$envoyConsumptionPrevHour);
    $echoResponse["envoyHourlyReadingDateTime"] = dMYHiFromEpoch($envoyDateEpoch);
    $echoResponse["envoyProductionPrevHour"] = sprintf("%05.2f",$envoyProductionPrevHour);
    $echoResponse["envoyConsumptionPrevHour"] = sprintf("%05.2f",$envoyConsumptionPrevHour);
    $echoResponse["envoyProductionDay"] = sprintf("%07.2f",$envoyProductionDay);
    $echoResponse["envoyConsumptionDay"] = sprintf("%07.2f",$envoyConsumptionDay);
    $echoResponse["result"] = "OK";
    if($action == "INS")
        $echoResponse["message"] = $responseArray["8"];
    else
        $echoResponse["message"] = $responseArray["9"];
}
echo json_encode($echoResponse);
closeConnection();
?>