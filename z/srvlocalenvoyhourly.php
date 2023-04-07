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
$envoyDate = "";
$envoyProductionPrevHour = 0;
$envoyConsumptionPrevHour = 0;
$envoyProductionDay = 0;
$envoyConsumptionDay = 0;
$envoyProductionDayPrevHour = 0;
$envoyConsumptionDayPrevHour = 0;

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
    $insertEnvoyHourlyQuery = "INSERT INTO EnvoyHourlyReadings (EnvoyReadingDate,EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay) VALUE (?, ?, ?, ?, ?, ?, ?)";
    $insertEnvoyHourlyStmt = $conn->prepare($insertEnvoyHourlyQuery);
    $lastEnvoyHourlySelQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay,EnvoyReadingTimestamp  FROM EnvoyHourlyReadings ORDER BY EnvoyReadingTimeEpoch DESC LIMIT 0,1";
    $lastEnvoyHourlySelStmt = $conn->prepare($lastEnvoyHourlySelQuery);
    $prevEnvoyHourlySelQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay,EnvoyReadingTimestamp  FROM EnvoyHourlyReadings ORDER BY EnvoyReadingTimeEpoch DESC LIMIT 1,1";
    $prevEnvoyHourlySelStmt = $conn->prepare($prevEnvoyHourlySelQuery);

    $maxminQuery = "SELECT EnvoyMaxMinDate, ProductionMax, ProductionMaxTime, ProductionMin, ProductionMinTime, ConsumptionMax, ConsumptionMaxTime, ConsumptionMin, ConsumptionMinTime, EnvoyMaxMinUpdateTimestamp FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
    $maxminStmt = $conn->prepare($maxminQuery);

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
        insertLocalEnvoyHourlyData();
    
    $retryCount = 0;
    while(true)
    {
        fetchEnvoyHourlyData();
        if ($envoyDateEpoch != 0 || $retryCount > 5 )
            break;
        $retryCount++;
    }    
    $echoResponse["envoyHourlyReadingDate"] = $envoyDate;
    $echoResponse["envoyHourlyReadingDateTime"] = dMYHiFromEpoch($envoyDateEpoch);
    $echoResponse["envoyProductionPrevHour"] = sprintf("%05.2f",$envoyProductionPrevHour);
    $echoResponse["envoyConsumptionPrevHour"] = sprintf("%05.2f",$envoyConsumptionPrevHour);
    $echoResponse["envoyProductionDay"] = sprintf("%05.2f",$envoyProductionDayPrevHour);
    $echoResponse["envoyConsumptionDay"] = sprintf("%05.2f",$envoyConsumptionDayPrevHour);
    $echoResponse["result"] = "OK";
    if($action == "INS")
        $echoResponse["message"] = $responseArray["8"];
    else
        $echoResponse["message"] = $responseArray["9"];
}
//$envoyDateEpoch = YYYYMMDDFromEpoch(time());
$rowCount = 0;
$currMaxProd = $currMinProd = $currMaxCons = $currMinCons  = 0.00;
$currMaxProdTime = $currMinProdTime = $currMaxConsTime = $currMinConsTime = "";

if($maxminStmt->bind_param("s",$envoyDate))
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
}
if($rowCount > 0)
{
    //$echoResponse["EnvoyDate"] = dateinDMY($envoyDateYYYYMMDD);
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

global $telegramHourlyBotAPIToken;
$telegramMessage = "";
$telegramMessage =  "Envoy Last Hour Statistics".PHP_EOL.
                    "Reading Date Time : ".dMYHiFromEpoch($envoyDateEpoch).PHP_EOL.
                    "Prev. Hour Production : ".sprintf("%05.2f",$envoyProductionPrevHour)." kWh".PHP_EOL.
                    "Prev. Hour Consumption : ".sprintf("%05.2f",$envoyConsumptionPrevHour)." kWh".PHP_EOL.
                    "Today's Production : ".sprintf("%05.2f",$envoyProductionDayPrevHour)." kWh".PHP_EOL.
                    "Today's Consumption : ".sprintf("%05.2f",$envoyConsumptionDayPrevHour)." kWh".PHP_EOL;
if($rowCount > 0)
{
    $telegramMessage = $telegramMessage."**************************".PHP_EOL; 
    $telegramMessage = $telegramMessage."Max Solar Production At : ".messageDateTimeFromTimestamp($currMaxProdTime).PHP_EOL."Max Value : ".sprintf("%07.2f",$currMaxProd)." W".PHP_EOL;
    $telegramMessage = $telegramMessage."Min Solar Production At : ".messageDateTimeFromTimestamp($currMinProdTime).PHP_EOL."Min Value : ".sprintf("%07.2f",$currMinProd)." W".PHP_EOL;
    $telegramMessage = $telegramMessage."Max Consumption At : ".messageDateTimeFromTimestamp($currMaxConsTime).PHP_EOL."Max Value : ".sprintf("%07.2f",$currMaxCons)." W".PHP_EOL;
    $telegramMessage = $telegramMessage."Min Consumption At : ".messageDateTimeFromTimestamp($currMinConsTime).PHP_EOL."Min Value : ".sprintf("%07.2f",$currMinCons)." W".PHP_EOL; 
    $telegramMessage = $telegramMessage."**************************".PHP_EOL; 
}

sendTelegramMessageToBot($telegramHourlyBotAPIToken, $telegramMessage);
$echoResponse["trace"] = $traceMessage;
echo json_encode($echoResponse);
closeConnection();
?>