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
$dateYYYYMMDD = "";
$rowCount = 0;
$telegramMessage = "";

$echoResponse["version"] = $scriptVersion;
if (isset($_GET['action']))
    $action = testinput($_GET['action']);

if (isset($_GET['src']))
    $src = testinput($_GET['src']);

if (isset($_GET['date']))
    $dateYYYYMMDD = testinput($_GET['date']);
else
    $dateYYYYMMDD = "";

if($action == "REP")
    $src = "";

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
    $maxProdQuery = "REPLACE INTO EnvoyProductionMax (ProductionMaxDate, ProductionMax) VALUES (?, ?)";
    $maxProdStmt = $conn->prepare($maxProdQuery);
    $maxProdTodayQuery = "SELECT ProductionMaxDate, ProductionMax, ProductionMaxTime FROM EnvoyProductionMax WHERE ProductionMaxDate = ?";
    $maxProdTodayStmt =  $conn->prepare($maxProdTodayQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

$envoyDateEpoch = 0;
$envoyDateYYYYMMDD = "";
$envoyDateTime = "";
$maxProd = -5000;
$maxProdTime = "";
$envoyCurrProd = 0;

if($action == "INS")
{
    $envoyURL = "http://envoy.local/production.json";
    $envoyData = json_decode(file_get_contents($envoyURL));
    
    $envoyDateEpoch = $envoyData->production[1]->readingTime;
    $envoyDateYYYYMMDD = YYYYMMDDFromEpoch($envoyDateEpoch);
    $envoyCurrProd = round($envoyData->production[1]->wNow,2);
    $dateYYYYMMDD = $envoyDateYYYYMMDD;
}

if($maxProdTodayStmt->bind_param("s",$dateYYYYMMDD))
{
    $maxProdTodayStmt->execute();
    $result = $maxProdTodayStmt->get_result();
    $rowCount = mysqli_num_rows($result);
    if($rowCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $maxProd = $row["ProductionMax"];
            $maxProdTime = $row["ProductionMaxTime"];
        }
    }
}

if ($action == "INS")
{
    if($envoyCurrProd >= $maxProd)
    {
        if($maxProdStmt->bind_param("ss",$envoyDateYYYYMMDD, $envoyCurrProd ))
        {
            $maxProdStmt->execute();
            $result = $maxProdStmt->get_result();
            commitNow(__FUNCTION__);
        }
        $telegramMessage = "";
        $telegramMessage = "Max Solar Production Reported".PHP_EOL.
                           "Report Date & Time : ".dMYHiFromEpoch($envoyDateEpoch).PHP_EOL.
                           "Max Value : ".sprintf("%07.2f",$envoyCurrProd)." W";
        sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);                           
        $echoResponse["maxProdDate"] = dateinDDMMMYYYFromEpoch($envoyDateEpoch);
        $echoResponse["maxProdTime"] = timeinHHMMSSFromEpoch($envoyDateEpoch);
        $echoResponse["maxProdValue"] = sprintf("%07.2f",$envoyCurrProd)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["11"];
    }
    else
    {
        $echoResponse["result"] = "OK";
        $echoResponse["prodMessage"] = "Reported at :".dateinDDMMMYYYFromEpoch($envoyDateEpoch)." ".timeinHHMMSSFromEpoch($envoyDateEpoch)." Max Production Value :". sprintf("%07.2f",$maxProd)." W, Current Production : ".sprintf("%07.2f",$envoyCurrProd)." W";
        $echoResponse["message"] = $responseArray["14"];
    }
}
else if ($action == "REP")
{
    if ($rowCount == 0)
    {
        $echoResponse["result"] = "NoData";
        $echoResponse["message"] = $responseArray["13"];
    }
    else
    {
        $echoResponse["maxProdDate"] = dateinDMY($maxProdTime);
        $echoResponse["maxProdTime"] = timeinHHMM($maxProdTime);
        $echoResponse["maxProdValue"] = sprintf("%07.2f",$maxProd)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["12"];
    }
}
else
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
}
$echoResponse["trace"] = $traceMessage;
echo json_encode($echoResponse);
closeConnection();
?>