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
    $maxProdQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ProductionMax, ProductionMaxTime) VALUES (?, ?, ?)";
    $maxProdStmt = $conn->prepare($maxProdQuery);
    $minProdQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ProductionMin, ProductionMinTime) VALUES (?, ?, ?)";
    $minProdStmt = $conn->prepare($minProdQuery);

    $maxminProdTodayQuery = "SELECT EnvoyMaxMinDate, ProductionMax, ProductionMaxTime, ProductionMin, ProductionMinTime FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
    $maxminProdTodayStmt =  $conn->prepare($maxminProdTodayQuery);

    $maxConsQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ConsumptionMax, ConsumptionMaxTime) VALUES (?, ?, ?)";
    $maxConsStmt = $conn->prepare($maxConsQuery);
    $minConsQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ConsumptionMin, ConsumptionMinTime) VALUES (?, ?, ?)";
    $minConsStmt = $conn->prepare($maxConsQuery); 

    $maxminConsTodayQuery = "SELECT EnvoyMaxMinDate, ConsumptionMax, ConsumptionMaxTime, ConsumptionMin, ConsumptionMinTime FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
    $maxminConsTodayStmt =  $conn->prepare($maxminConsTodayQuery);

    $maxminQuery = "SELECT EnvoyMaxMinDate, ProductionMax, ProductionMaxTime, ProductionMin, ProductionMinTime, ConsumptionMax, ConsumptionMaxTime, ConsumptionMin, ConsumptionMinTime FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
    $maxminStmt = $conn->prepare($maxminQuery);

    $maxminInsUpdQuery = "REPLACE INTO EnvoyDailyMaxMin (EnvoyMaxMinDate, ProductionMax, ProductionMaxTime, ProductionMin, ProductionMinTime, ConsumptionMax, ConsumptionMaxTime, ConsumptionMin, ConsumptionMinTime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $maxminInsUpdStmt = $conn->prepare($maxminInsUpdQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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


//
$currMaxProd = $currMinProd = $maxProd = $minProd = 0;
$currMaxCons = $currMinCons = $maxCons = $minCons = 0;
$currMaxProdTime = $currMinProdTime = "";
$currMaxConsTime = $currMinConsTime = "";

$updateMaxProdRecord = FALSE;
$updateMinProdRecord = FALSE;
$updateMaxConsRecord = FALSE;
$updateMinConsRecord = FALSE;
//
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
    $envoyDateTime = datetimeFromEpoch($envoyDateEpoch);
    if ($envoyDateEpoch == 0)
        return;
}

if($maxminStmt->bind_param("s",$dateYYYYMMDD))
{
    $maxminStmt->execute();
    $result = $maxminStmt->get_result();
    $rowCount = mysqli_num_rows($result);
    if($rowCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $currMaxProd = $row["ProductionMax"];
            $currMaxProdTime = $row["ProductionMaxTime"];
            $currMinProd = $row["ProductionMin"];
            $currMinProdTime = $row["ProductionMinTime"];
            $currMaxCons = $row["ConsumptionMax"];
            $currMaxConsTime = $row["ConsumptionMaxTime"];
            $currMinCons = $row["ConsumptionMin"];
            $currMinConsTime = $row["ConsumptionMinTime"];            
        }
    }
    else
    {
        $currMaxProd = 0.00;
        $currMinProd = 9999.99;
        $currMaxCons = 0;
        $currMinCons = 9999.99;
        $currMaxProdTime = $currMinProdTime = $currMaxConsTime = $currMinConsTime = $envoyDateTime;

    }
}
if ($action == "INS")
{
    if($envoyCurrProd >= $currMaxProd)
    {
        $currMaxProd = $envoyCurrProd;
        $currMaxProdTime = $envoyDateTime;
        $updateMaxProdRecord = TRUE;
    }
    if($envoyCurrProd < $currMinProd)
    {
        $currMinProd = $envoyCurrProd;
        $currMinProdTime = $envoyDateTime;
        $updateMinProdRecord = TRUE;
    }
    if($envoyCurrCons >= $currMaxCons)
    {
        $currMaxCons = $envoyCurrCons;
        $currMaxConsTime = $envoyDateTime;
        $updateMaxConsRecord = TRUE;
    }
    if($envoyCurrCons < $currMinCons)
    {
        $currMinCons = $envoyCurrCons;
        $currMinConsTime = $envoyDateTime;
        $updateMinConsRecord = TRUE;
    }

    if ($updateMaxProdRecord || $updateMinProdRecord || $updateMaxConsRecord || $updateMinConsRecord)
    {
        $telegramMessage = "Maximum & Minimum Solar Production & Consumption Update".PHP_EOL;
        if($maxminInsUpdStmt ->bind_param("sssssssss",$envoyDateYYYYMMDD, $currMaxProd, $currMaxProdTime, $currMinProd, $currMinProdTime, $currMaxCons, $currMaxConsTime, $currMinCons, $currMinConsTime ))
        {
            $maxminInsUpdStmt->execute();
            $result = $maxProdStmt->get_result();
            commitNow(__FUNCTION__);
        }
        
        if ($updateMaxProdRecord)
        {
            $telegramMessage = $telegramMessage."Max Solar Production Reported At : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL."Max Value : ".sprintf("%07.2f",$currMaxProd)." W".PHP_EOL;
        }
        
        if ($updateMinProdRecord)
        {
            $telegramMessage = $telegramMessage."Min Solar Production Reported At : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL."Max Value : ".sprintf("%07.2f",$currMinProd)." W".PHP_EOL;
        }
        
        if ($updateMaxConsRecord)
        {
            $telegramMessage = $telegramMessage."Max Consumption Reported At : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL."Max Value : ".sprintf("%07.2f",$currMaxCons)." W".PHP_EOL;
        }
        if ($updateMinConsRecord)
        {
            $telegramMessage = $telegramMessage."Min Consumption Reported At : ".YYYYMMDDFromEpoch($envoyDateEpoch).PHP_EOL."Min Value : ".sprintf("%07.2f",$currMinCons)." W".PHP_EOL;
        }        
        
        sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);
    }

}
/*
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
*/
/*if ($action == "INS")
{
    if($envoyCurrProd >= $maxProd)
    {
        if($maxProdStmt->bind_param("sss",$envoyDateYYYYMMDD, $envoyCurrProd, $envoyDateTime ))
        {
            $maxProdStmt->execute();
            echo "Max Prod Insert Error : ".mysqli_error($conn);
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
        if($minProdStmt->bind_param("sss",$envoyDateYYYYMMDD, $envoyCurrProd,$envoyDateTime ))
        {
            $minProdStmt->execute();
            echo "Min Prod Insert Error : ".mysqli_error($conn);
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
        if($maxConsStmt->bind_param("sss",$envoyDateYYYYMMDD, $envoyCurrCons,$envoyDateTime ))
        {
            $maxConsStmt->execute();
            echo "Max Cons Insert Error : ".mysqli_error($conn);
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
        if($minConsStmt->bind_param("sss",$envoyDateYYYYMMDD, $envoyCurrCons,$envoyDateTime ))
        {
            $minConsStmt->execute();
            echo "Min Cons Insert Error : ".mysqli_error($conn);
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
    
}*/
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