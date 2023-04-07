<?php
$startTime = hrtime(true);
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require 'var/www/mysite/common/database.php';
require 'var/www/mysite/common/helper.php';
require 'var/www/mysite/dbinsert/insertdata.php';

$startDate=strtotime("2022-08-10");
$echoResponse=array();
$traceMessage = "";
$resultData = "";
$responseArray = array();
fillResponseArray();
$debugMessage = "";
$fatalFlag = false;
$source = "SCRIPT";

//$scriptVersion = "2.0";
if (isset($_POST['scriptVersion']))
    $scriptVersion = testinput($_POST['scriptVersion']);
else
    $scriptVersion = "1.0";

$echoResponse["version"] = $scriptVersion;

if (isset($_POST['action']))
    $action = testinput($_POST['action']);
else
    $fatalFlag = true;

if (isset($_POST['readingDate']))
    $readingDate = testinput($_POST['readingDate']);
else
    $fatalFlag = true;

if (isset($_POST['productionReading']))
    $productionReading = testinput($_POST['productionReading']);
else
    $fatalFlag = true;

if (isset($_POST['consumptionReading']))
    $consumptionReading = testinput($_POST['consumptionReading']);
else
    $fatalFlag = true;

if (isset($_POST['source']))
    $source = testinput($_POST['source']);

if ($fatalFlag && $source != "SCRIPT")
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-101"];
    $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
    echo json_encode($echoResponse);
    exit();   
}

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error)
{
  	die("Connection failed: " . $conn->connect_error);
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["0"];
    $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
    echo json_encode($echoResponse);
    exit();
}
else
{
    $conn->autocommit(TRUE);
    $insertProdConsumptionQuery = "INSERT INTO EnvoyReadings (EnvoyReadingDate, EnvoyProductionActual, EnvoyConsumptionActual, EnvoyProduction, EnvoyConsumption) VALUE (?, ?, ?, ?, ?)";
    $insertStmtProdConsumptionByDate = $conn->prepare($insertProdConsumptionQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

if($source == "SCRIPT")
{
    $action = "INS";
    $readingDate = date("Y-m-d",strtotime(date("Y-m-d")) - 86400);
    $productionReading = getDataFromEnphase($readingDate,$enphaseProductionURL,"production");
    $consumptionReading = getDataFromEnphase($readingDate,$enphaseConsumptionURL,"consumption");
}

if($action == "INS")
{
    insertEnvoyReadingData($readingDate,$productionReading,$consumptionReading);
    $echoResponse["envoyReadingDate"] = dateinDDMMMYYY($readingDate);
    $echoResponse["envoyProduction"] = sprintf("%05.2f",round($productionReading/1000,1));
    $echoResponse["envoyConsumption"] = sprintf("%05.2f",round($consumptionReading/1000,1));
    $echoResponse["result"] = "OK";
    $echoResponse["message"] = $responseArray["5"];
}
else
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
    echo json_encode($echoResponse);
    exit();
}
if($source != "SCRIPT")
{
    populateEnvoyResponseTable($readingDate);
    $echoResponse["trace"] = $traceMessage;
    $echoResponse["resultData"] = $resultData;
    $echoResponse["debugMessage"] = $debugMessage;
}
$maxminResponse = json_decode(file_get_contents("http://lamp.local/envoy/srvmaxmin.php?action=REP&date=".date("Y-m-d",strtotime("-1 days"))),true);
global $telegramMessage;
$telegramMessage = "Envoy Reading Date : ".$echoResponse["envoyReadingDate"].PHP_EOL.
"Envoy Produced Units : ".$echoResponse["envoyProduction"]." kWh".PHP_EOL.
"Envoy Consumed Units : ".$echoResponse["envoyConsumption"]." kWh".PHP_EOL.
"Max Production : ".$maxminResponse["MaxProd"]." W at ".$maxminResponse["MaxProdTime"].PHP_EOL.
"Min Production : ".$maxminResponse["MinProd"]." W at ".$maxminResponse["MinProdTime"].PHP_EOL.
"Max Consumption : ".$maxminResponse["MaxCons"]." W at ".$maxminResponse["MaxConsTime"].PHP_EOL.
"Min Consumption : ".$maxminResponse["MinCons"]." W at ".$maxminResponse["MinConsTime"].PHP_EOL;
sendTelegramMessage($telegramMessage);
closeConnection();
$echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
echo json_encode($echoResponse);
?>