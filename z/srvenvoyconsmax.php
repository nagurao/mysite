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
    $maxConsQuery = "REPLACE INTO EnvoyConsumptionMax (ConsumptionMaxDate, ConsumptionMax) VALUES (?, ?)";
    $maxConsStmt = $conn->prepare($maxConsQuery);
    $maxConsTodayQuery = "SELECT ConsumptionMaxDate, ConsumptionMax, ConsumptionMaxTime FROM EnvoyConsumptionMax WHERE ConsumptionMaxDate = ?";
    $maxConsTodayStmt =  $conn->prepare($maxConsTodayQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

$envoyDateEpoch = 0;
$envoyDateYYYYMMDD = "";
$envoyDateTime = "";
$maxCons = 0;
$maxConsTime = "";
$envoyCurrCons = 0;

if($action == "INS")
{
    $envoyURL = "http://envoy.local/production.json";
    $envoyData = json_decode(file_get_contents($envoyURL));
    
    $envoyDateEpoch = $envoyData->consumption[0]->readingTime;
    $envoyDateYYYYMMDD = YYYYMMDDFromEpoch($envoyDateEpoch);
    $envoyCurrCons = round($envoyData->consumption[0]->wNow,2);
    $dateYYYYMMDD = $envoyDateYYYYMMDD;
    if ($envoyDateEpoch == 0)
        return;    
}

if($maxConsTodayStmt->bind_param("s",$dateYYYYMMDD))
{
    $maxConsTodayStmt->execute();
    $result = $maxConsTodayStmt->get_result();
    $rowCount = mysqli_num_rows($result);
    if($rowCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $maxCons = $row["ConsumptionMax"];
            $maxConsTime = $row["ConsumptionMaxTime"];
        }
    }
}

if ($action == "INS")
{
    if($envoyCurrCons >= $maxCons)
    {
        if($maxConsStmt->bind_param("ss",$envoyDateYYYYMMDD, $envoyCurrCons ))
        {
            $maxConsStmt->execute();
            $result = $maxConsStmt->get_result();
            commitNow(__FUNCTION__);
        }
        $telegramMessage = "";
        $telegramMessage = "Max Consumption Reported".PHP_EOL.
                           "Report Date & Time : ".dMYHiFromEpoch($envoyDateEpoch).PHP_EOL.
                           "Max Value : ".sprintf("%07.2f",$envoyCurrCons)." W";
        //sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);                           
        $echoResponse["maxConsDate"] = dateinDDMMMYYYFromEpoch($envoyDateEpoch);
        $echoResponse["maxConsTime"] = timeinHHMMSSFromEpoch($envoyDateEpoch);
        $echoResponse["maxConsValue"] = sprintf("%07.2f",$envoyCurrCons)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["15"];
    }
    else
    {
        $echoResponse["result"] = "OK";
        $echoResponse["ConsMessage"] = "Reported at :".dateinDDMMMYYYFromEpoch($envoyDateEpoch)." ".timeinHHMMSSFromEpoch($envoyDateEpoch)." Max Consumption Value :".sprintf("%07.2f",$maxCons)." W, Current Consumption : ".sprintf("%07.2f",$envoyCurrCons)." W";
        $echoResponse["message"] = $responseArray["18"];
    }
}
else if ($action == "REP")
{
    if ($rowCount == 0)
    {
        $echoResponse["result"] = "NoData";
        $echoResponse["message"] = $responseArray["17"];
    }
    else
    {
        $echoResponse["maxConsDate"] = dateinDMY($maxConsTime);
        $echoResponse["maxConsTime"] = timeinHHMM($maxConsTime);
        $echoResponse["maxConsValue"] = sprintf("%07.2f",$maxCons)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["16"];
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