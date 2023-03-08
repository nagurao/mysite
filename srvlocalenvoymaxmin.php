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
$rowProdCount = 0;
$rowConsCount = 0;
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
    $maxProdQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ProductionMax) VALUES (?, ?)";
    $maxProdStmt = $conn->prepare($maxProdQuery);
    $minProdQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ProductionMin) VALUES (?, ?)";
    $minProdStmt = $conn->prepare($minProdQuery);

    $maxminProdTodayQuery = "SELECT EnvoyMaxMinDate, ProductionMax, ProductionMaxTime, ProductionMin, ProductionMinTime FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
    $maxminProdTodayStmt =  $conn->prepare($maxminProdTodayQuery);

    $maxConsQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ConsumptionMax) VALUES (?, ?)";
    $maxConsStmt = $conn->prepare($maxConsQuery);
    $minConsQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ConsumptionMin) VALUES (?, ?)";
    $minConsStmt = $conn->prepare($maxConsQuery); 

    $maxminConsTodayQuery = "SELECT EnvoyMaxMinDate, ConsumptionMax, ConsumptionMaxTime, ConsumptionMin, ConsumptionMinTime FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
    $maxminConsTodayStmt =  $conn->prepare($maxminConsTodayQuery);

    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

$envoyDateEpoch = 0;
$envoyDateYYYYMMDD = "";
$envoyDateTime = "";

$maxProd = 0;
$maxProdTime = "";
$minProd = 0;
$minProdTime = "";
$envoyCurrProd = 0;

$maxCons = 0;
$maxConsTime = "";
$minCons = 0;
$minConsTime = "";
$envoyCurrCons = 0;

$maxProdUpdated = $minProdUpdated = FALSE;
$maxConsUpdated = $minConsUpdated = FALSE;
if($action == "INS")
{
    $envoyURL = "http://envoy.local/production.json";
    $envoyData = json_decode(file_get_contents($envoyURL));
    
    $envoyDateEpoch = $envoyData->production[1]->readingTime;
    $envoyDateYYYYMMDD = YYYYMMDDFromEpoch($envoyDateEpoch);
    $envoyCurrProd = round($envoyData->production[1]->wNow,2);
    $envoyCurrCons = round($envoyData->consumption[0]->wNow,2);
    $dateYYYYMMDD = $envoyDateYYYYMMDD;
}

if($maxminProdTodayStmt->bind_param("s",$dateYYYYMMDD))
{
    $maxminProdTodayStmt->execute();
    $result = $maxminProdTodayStmt->get_result();
    $rowProdCount = mysqli_num_rows($result);
    if($rowProdCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $maxProd = $row["ProductionMax"];
            $maxProdTime = $row["ProductionMaxTime"];
            $minProd = $row["ProductionMin"];
            $minProdTime = $row["ProductionMinTime"];
        }
    }
}

if($maxminConsTodayStmt->bind_param("s",$dateYYYYMMDD))
{
    $maxminConsTodayStmt->execute();
    $result = $maxminConsTodayStmt->get_result();
    $rowConsCount = mysqli_num_rows($result);
    if($rowConsCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $maxCons = $row["ConsumptionMax"];
            $maxConsTime = $row["ConsumptionMaxTime"];
            $minCons = $row["ConsumptionMin"];
            $minConsTime = $row["ConsumptionMinTime"];
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
                           "Report Date & Time : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL.
                           "Max Value : ".sprintf("%07.2f",$envoyCurrProd)." W";
        sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);                           
        $echoResponse["maxProdDate"] = dateinDDMMMYYYFromEpoch($envoyDateEpoch);
        $echoResponse["maxProdTime"] = timeinHHMMSSFromEpoch($envoyDateEpoch);
        $echoResponse["maxProdValue"] = sprintf("%07.2f",$envoyCurrProd)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["maxProdMessage"] = "New Max";
        $maxProdUpdated = TRUE;
    }

    if($envoyCurrProd < $minProd)
    {
        if($minProdStmt->bind_param("ss",$envoyDateYYYYMMDD, $envoyCurrProd ))
        {
            $minProdStmt->execute();
            $result = $minProdStmt->get_result();
            commitNow(__FUNCTION__);
        }
        $telegramMessage = "";
        $telegramMessage = "Min Solar Production Reported".PHP_EOL.
                           "Report Date & Time : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL.
                           "Min Value : ".sprintf("%07.2f",$envoyCurrProd)." W";
        sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);                           
        $echoResponse["minProdDate"] = dateinDDMMMYYYFromEpoch($envoyDateEpoch);
        $echoResponse["minProdTime"] = timeinHHMMSSFromEpoch($envoyDateEpoch);
        $echoResponse["minProdValue"] = sprintf("%07.2f",$envoyCurrProd)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["minProdMessage"] = "New Min";
        $minProdUpdated = TRUE;      
    }
    if (!$maxProdUpdated)
    {
        $echoResponse["result"] = "OK";
        $echoResponse["prodMaxMessage"] = "Reported at :".dateinDDMMMYYYFromEpoch($envoyDateEpoch)." ".timeinHHMMSSFromEpoch($envoyDateEpoch)." Max Production Value :". sprintf("%07.2f",$maxProd)." W, Current Production : ".sprintf("%07.2f",$envoyCurrProd)." W";
        $echoResponse["message"] = $responseArray["14"];
    }
    if (!$minProdUpdated)
    {
        $echoResponse["result"] = "OK";
        $echoResponse["prodMinMessage"] = "Reported at :".dateinDDMMMYYYFromEpoch($envoyDateEpoch)." ".timeinHHMMSSFromEpoch($envoyDateEpoch)." Min Production Value :". sprintf("%07.2f",$minProd)." W, Current Production : ".sprintf("%07.2f",$envoyCurrProd)." W";
        $echoResponse["message"] = $responseArray["14"];
    }   
    

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
                           "Report Date & Time : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL.
                           "Max Value : ".sprintf("%07.2f",$envoyCurrCons)." W";
        sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);                           
        $echoResponse["maxConsDate"] = dateinDDMMMYYYFromEpoch($envoyDateEpoch);
        $echoResponse["maxConsTime"] = timeinHHMMSSFromEpoch($envoyDateEpoch);
        $echoResponse["maxConsValue"] = sprintf("%07.2f",$envoyCurrCons)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["11"];
        $maxConsUpdated = TRUE;
    }
    if($envoyCurrCons < $minCons)
    {
        if($minConsStmt->bind_param("ss",$envoyDateYYYYMMDD, $envoyCurrCons ))
        {
            $minConsStmt->execute();
            $result = $minConsStmt->get_result();
            commitNow(__FUNCTION__);
        }
        $telegramMessage = "";
        $telegramMessage = "Min Consumption Reported".PHP_EOL.
                           "Report Date & Time : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL.
                           "Min Value : ".sprintf("%07.2f",$envoyCurrCons)." W";
        sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);                           
        $echoResponse["minConsDate"] = dateinDDMMMYYYFromEpoch($envoyDateEpoch);
        $echoResponse["minConsTime"] = timeinHHMMSSFromEpoch($envoyDateEpoch);
        $echoResponse["minConsValue"] = sprintf("%07.2f",$envoyCurrCons)." W";
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["11"];  
        $minConsUpdated = TRUE;      
    }
    if (!$maxConsUpdated)
    {
        $echoResponse["result"] = "OK";
        $echoResponse["consMaxMessage"] = "Reported at :".dateinDDMMMYYYFromEpoch($envoyDateEpoch)." ".timeinHHMMSSFromEpoch($envoyDateEpoch)." Max Consumption Value :". sprintf("%07.2f",$maxCons)." W, Current Consumption : ".sprintf("%07.2f",$envoyCurrCons)." W";
        $echoResponse["message"] = $responseArray["14"];
    }
    if (!$minConsUpdated)
    {
        $echoResponse["result"] = "OK";
        $echoResponse["consMinMessage"] = "Reported at :".dateinDDMMMYYYFromEpoch($envoyDateEpoch)." ".timeinHHMMSSFromEpoch($envoyDateEpoch)." Min Consumption Value :". sprintf("%07.2f",$minProd)." W, Current Consumption : ".sprintf("%07.2f",$envoyCurrCons)." W";
        $echoResponse["message"] = $responseArray["14"];
    }     
}
else if ($action == "REP")
{
    if (($rowProdCount + $rowConsCount) == 0)
    {
        $echoResponse["result"] = "NoData";
        $echoResponse["message"] = $responseArray["13"];
    }
    else
    {
        $echoResponse["maxProdDate"] = dateinDDMMMYYY($maxProdTime);
        $echoResponse["maxProdTime"] = timeinHHMMSS($maxProdTime);
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