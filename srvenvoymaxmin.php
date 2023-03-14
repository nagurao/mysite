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

    $maxminQuery = "SELECT EnvoyMaxMinDate, ProductionMax, ProductionMaxTime, ProductionMin, ProductionMinTime, ConsumptionMax, ConsumptionMaxTime, ConsumptionMin, ConsumptionMinTime, EnvoyMaxMinUpdateTimestamp FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
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

$envoyCurrProd = 0;
$envoyCurrCons = 0;

$currMaxProd = $currMinProd = 0;
$currMaxCons = $currMinCons = 0;
$currMaxProdTime = $currMinProdTime = "";
$currMaxConsTime = $currMinConsTime = "";
$lastUpdated = "";

$updateMaxProdRecord = FALSE;
$updateMinProdRecord = FALSE;
$updateMaxConsRecord = FALSE;
$updateMinConsRecord = FALSE;

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
if ($action == "REP" && $dateYYYYMMDD == "")
    $dateYYYYMMDD = YYYYMMDDFromEpoch(time());
    
if ($envoyDateYYYYMMDD == "")
    $envoyDateYYYYMMDD = $dateYYYYMMDD;

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
            $lastUpdated = $row["EnvoyMaxMinUpdateTimestamp"];
        }
    }
    else
    {
        $currMaxProd = -9999.00;
        $currMinProd = 9999.99;
        $currMaxCons = -9999.00;
        $currMinCons = 9999.99;
        $currMaxProdTime = $currMinProdTime = $currMaxConsTime = $currMinConsTime = $envoyDateTime;
        $rowCount = 0;
    }
}
if ($action != "INS" && $action != "REP")
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];  
    $echoResponse["trace"] = $traceMessage;
    echo json_encode($echoResponse);
    closeConnection();  
}

$echoResponse["MaxProdMessage"] = "";
$echoResponse["MinProdMessage"] = "";
$echoResponse["MaxConsMessage"] = "";
$echoResponse["MinConsMessage"] = "";

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
            $result = $maxminInsUpdStmt->get_result();
            commitNow(__FUNCTION__);
            $echoResponse["message"] = $responseArray["27"];
        }
        
        if ($updateMaxProdRecord)
        {
            $telegramMessage = $telegramMessage."Max Solar Production Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch).PHP_EOL."Max Value : ".sprintf("%07.2f",$currMaxProd)." W".PHP_EOL;
            $echoResponse["MaxProdMessage"] = "Max Solar Production Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch)." Max Value : ".sprintf("%07.2f",$currMaxProd)." W";
        }
        
        if ($updateMinProdRecord)
        {
            $telegramMessage = $telegramMessage."Min Solar Production Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch).PHP_EOL."Min Value : ".sprintf("%07.2f",$currMinProd)." W".PHP_EOL;
            $echoResponse["MinProdMessage"] = "Min Solar Production Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch)." Min Value : ".sprintf("%07.2f",$currMinProd)." W";
        }

        if ($updateMaxConsRecord)
        {
            $telegramMessage = $telegramMessage."Max Consumption Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch).PHP_EOL."Max Value : ".sprintf("%07.2f",$currMaxCons)." W".PHP_EOL;
            $echoResponse["MaxConsMessage"] = "Max Consumption Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch)." Max Value : ".sprintf("%07.2f",$currMaxCons)." W";
        }

        if ($updateMinConsRecord)
        {
            $telegramMessage = $telegramMessage."Min Consumption Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch).PHP_EOL."Min Value : ".sprintf("%07.2f",$currMinCons)." W".PHP_EOL;
            $echoResponse["MinConsMessage"] = "Min Consumption Reported At : ".messageDateTimeFromEpoch($envoyDateEpoch)." Min Value : ".sprintf("%07.2f",$currMinCons)." W";
        }        
  
        $echoResponse["TelegramMessage"] = str_ireplace(PHP_EOL," ",$telegramMessage);
        sendTelegramMessageToBot($telegramMaxProdConsBotAPIToken, $telegramMessage);
    }

    if(!$updateMaxProdRecord)
        $echoResponse["MaxProdMessage"] = $responseArray["14"]." Current Production : ".sprintf("%07.2f",$envoyCurrProd)." W";

    if (!$updateMinProdRecord)
        $echoResponse["MinProdMessage"] = $responseArray["18"]." Current Production : ".sprintf("%07.2f",$envoyCurrProd)." W";
    
    if (!$updateMinProdRecord)
        $echoResponse["MaxConsMessage"] = $responseArray["22"]." Current Consumption : ".sprintf("%07.2f",$envoyCurrCons)." W";

    if (!$updateMinConsRecord)
        $echoResponse["MinConsMessage"] =  $responseArray["26"]." Current Consumption : ".sprintf("%07.2f",$envoyCurrCons)." W";

}   

if($action == "REP" && $rowCount == 0)
{
    $echoResponse["result"] = "NoData";
}
else
{
    $echoResponse["result"] = "OK";
    if($action == "REP")
        $echoResponse["message"] = $responseArray["13"];

    $echoResponse["EnvoyDate"] = dateinDMY($envoyDateYYYYMMDD);
    $echoResponse["MaxProd"] = sprintf("%07.2f",$currMaxProd);
    $echoResponse["MaxProdTime"] = timeinHHMM($currMaxProdTime);
    $echoResponse["MinProd"] = sprintf("%07.2f",$currMinProd);
    $echoResponse["MinProdTime"] = timeinHHMM($currMinProdTime);
    $echoResponse["MaxCons"] = sprintf("%07.2f",$currMaxCons);
    $echoResponse["MaxConsTime"] = timeinHHMM($currMaxConsTime);
    $echoResponse["MinCons"] = sprintf("%07.2f",$currMinCons);
    $echoResponse["MinConsTime"] = timeinHHMM($currMinConsTime);
    $echoResponse["LastUpdated"] = dMYHi($lastUpdated);
}
$echoResponse["trace"] = $traceMessage;
echo json_encode($echoResponse);
closeConnection();
?>