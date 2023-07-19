<?php
$startTime = hrtime(true);
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require '/var/www/mysite/common/database.php';
require '/var/www/mysite/common/helper.php';
require '/var/www/mysite/dbinsert/insertdata.php';
require '/var/www/mysite/dbread/reportdata.php';

$echoResponse=array();
$traceMessage = "";
$resultData = "";
$responseArray = array();
fillResponseArray();
$debugMessage = "";
$fatalFlag = false;
$action = "INS";
$prevAction = "";
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
$envoyProdMonth = 0;
$envoyConsMonth = 0;
$envoyMaxProdPeriod = 0;
$envoyMaxConsPeriod = 0;
$envoyMinProdPeriod = 0;
$envoyMinConsPeriod = 0;
$envoyProductionMonth = 0;
$envoyConsumptionMonth = 0;
$prevMaxProdHour = $prevMaxConsHour = $prevMinProdHour = $prevMinConsHour = "";
$currMaxProdHour = $currMaxConsHour = $currMinProdHour = $currMinConsHour = "";
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
    $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
    echo json_encode($echoResponse);
    exit();
}
else
{
    $conn->autocommit(TRUE);
    $insertEnvoyHourlyQuery = "INSERT INTO EnvoyHourlyReadings (EnvoyReadingDate,EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay, EnvoyProdMonth, EnvoyConsMonth) VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertEnvoyHourlyStmt = $conn->prepare($insertEnvoyHourlyQuery);
    $lastEnvoyHourlySelQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay,EnvoyProdMonth, EnvoyConsMonth,EnvoyReadingTimestamp  FROM EnvoyHourlyReadings ORDER BY EnvoyReadingTimeEpoch DESC LIMIT 0,1";
    $lastEnvoyHourlySelStmt = $conn->prepare($lastEnvoyHourlySelQuery);
    $prevEnvoyHourlySelQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay,EnvoyConsDay,EnvoyProdMonth, EnvoyConsMonth, EnvoyReadingTimestamp  FROM EnvoyHourlyReadings ORDER BY EnvoyReadingTimeEpoch DESC LIMIT 1,1";
    $prevEnvoyHourlySelStmt = $conn->prepare($prevEnvoyHourlySelQuery);

    $maxminQuery = "SELECT EnvoyMaxMinDate, ProductionMax, ProductionMaxTime, ProductionMin, ProductionMinTime, ConsumptionMax, ConsumptionMaxTime, ConsumptionMin, ConsumptionMinTime, EnvoyMaxMinUpdateTimestamp FROM EnvoyDailyMaxMin WHERE EnvoyMaxMinDate = ?";
    $maxminStmt = $conn->prepare($maxminQuery);

    $maxProdPeriodQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, date_format(SubTime(EnvoyReadingTimestamp,\"01:00:00\" ),'%H:%i') AS PrevHour, date_format(EnvoyReadingTimestamp,'%H:%i') AS CurrHour FROM EnvoyHourlyReadings WHERE EnvoyReadingDate >= CURRENT_DATE AND EnvoyProdHour = (SELECT MAX(EnvoyProdHour) FROM EnvoyHourlyReadings WHERE EnvoyReadingDate = CURRENT_DATE)";
    $maxProdPeriodStmt = $conn->prepare($maxProdPeriodQuery);
    $minProdPeriodQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, date_format(SubTime(EnvoyReadingTimestamp,\"01:00:00\" ),'%H:%i') AS PrevHour, date_format(EnvoyReadingTimestamp,'%H:%i') AS CurrHour FROM EnvoyHourlyReadings WHERE EnvoyReadingDate >= CURRENT_DATE AND EnvoyProdHour = (SELECT MIN(EnvoyProdHour) FROM EnvoyHourlyReadings WHERE EnvoyReadingDate = CURRENT_DATE AND EnvoyProdHour > 0)";
    $minProdPeriodStmt = $conn->prepare($minProdPeriodQuery);
    $maxConsPeriodQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyConsHour, date_format(SubTime(EnvoyReadingTimestamp,\"01:00:00\" ),'%H:%i') AS PrevHour, date_format(EnvoyReadingTimestamp,'%H:%i') AS CurrHour FROM EnvoyHourlyReadings WHERE EnvoyReadingDate >= CURRENT_DATE AND EnvoyConsHour = (SELECT MAX(EnvoyConsHour) FROM EnvoyHourlyReadings WHERE EnvoyReadingDate = CURRENT_DATE)";
    $maxConsPeriodStmt = $conn->prepare($maxConsPeriodQuery);
    $minConsPeriodQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyConsHour, date_format(SubTime(EnvoyReadingTimestamp,\"01:00:00\" ),'%H:%i') AS PrevHour, date_format(EnvoyReadingTimestamp,'%H:%i') AS CurrHour FROM EnvoyHourlyReadings WHERE EnvoyReadingDate >= CURRENT_DATE AND EnvoyConsHour = (SELECT MIN(EnvoyConsHour) FROM EnvoyHourlyReadings WHERE EnvoyReadingDate = CURRENT_DATE)";
    $minConsPeriodStmt = $conn->prepare($minConsPeriodQuery);
/*
SELECT EnvoyProdHour , 
date_format(SubTime(EnvoyReadingTimestamp,"01:00:00"),'%H:%i') AS PrevHour,
date_format(EnvoyReadingTimestamp,'%H:%i') AS CurrHour
FROM EnvoyHourlyReadings
WHERE EnvoyReadingDate >= CURRENT_DATE
AND
EnvoyProdHour = (SELECT MAX(EnvoyProdHour) FROM EnvoyHourlyReadings
WHERE EnvoyReadingDate = CURRENT_DATE)
*/
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

if($action != "INS" && $action != "REP")
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
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
    $echoResponse["envoyProductionMonth"] = floatval($envoyProductionMonth);
    $echoResponse["envoyConsumptionMonth"] = floatval($envoyConsumptionMonth);
    $echoResponse["result"] = "OK";
    if($prevAction == "INS" && $action == "REP")
        $echoResponse["message"] = $responseArray["8"];
    else
        $echoResponse["message"] = $responseArray["9"];
}
//$envoyDateEpoch = YYYYMMDDFromEpoch(time());
$rowCount = 0;
$currMaxProd = $currMinProd = $currMaxCons = $currMinCons  = 0.00;
$currMaxProdTime = $currMinProdTime = $currMaxConsTime = $currMinConsTime = "";
$maxMinDate = $envoyDate;
if(hhFromEpoch(time()) == 00 && mmFromEpoch(time() == 00))
{
    $maxMinDate = prevDays(1);
}

if($maxminStmt->bind_param("s",$maxMinDate))
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

if($maxProdPeriodStmt->execute())
{
    $result = $maxProdPeriodStmt->get_result();
    $rowCount = mysqli_num_rows($result);
    if($rowCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $envoyMaxProdPeriod = $row["EnvoyProdHour"];
            $prevMaxProdHour = $row["PrevHour"];
            $currMaxProdHour = $row["CurrHour"];
        }
        $echoResponse["MaxProdPerHour"] = sprintf("%05.2f",$envoyMaxProdPeriod);
        $echoResponse["MaxProdStartHour"] = $prevMaxProdHour;
        $echoResponse["MaxProdEndHour"] = $currMaxProdHour;
    }
}

if($minProdPeriodStmt->execute())
{
    $result = $minProdPeriodStmt->get_result();
    $rowCount = mysqli_num_rows($result);
    if($rowCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $envoyMinProdPeriod = $row["EnvoyProdHour"];
            $prevMinProdHour = $row["PrevHour"];
            $currMinProdHour = $row["CurrHour"];
        }
        $echoResponse["MinProdPerHour"] = sprintf("%05.2f",$envoyMinProdPeriod);
        $echoResponse["MinProdStartHour"] = $prevMinProdHour;
        $echoResponse["MinProdEndHour"] = $currMinProdHour;
    }
}

if($maxConsPeriodStmt->execute())
{
    $result = $maxConsPeriodStmt->get_result();
    $rowCount = mysqli_num_rows($result);
    if($rowCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $envoyMaxConsPeriod = $row["EnvoyConsHour"];
            $prevMaxConsHour = $row["PrevHour"];
            $currMaxConsHour = $row["CurrHour"];
        }
        $echoResponse["MaxConsPerHour"] = sprintf("%05.2f",$envoyMaxConsPeriod);
        $echoResponse["MaxConsStartHour"] = $prevMaxConsHour;
        $echoResponse["MaxConsEndHour"] = $currMaxConsHour;
    }
}

if($minConsPeriodStmt->execute())
{
    $result = $minConsPeriodStmt->get_result();
    $rowCount = mysqli_num_rows($result);
    if($rowCount > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $envoyMinConsPeriod = $row["EnvoyConsHour"];
            $prevMinConsHour = $row["PrevHour"];
            $currMinConsHour = $row["CurrHour"];
        }
        $echoResponse["MinConsPerHour"] = sprintf("%05.2f",$envoyMinConsPeriod);
        $echoResponse["MinConsStartHour"] = $prevMinConsHour;
        $echoResponse["MinConsEndHour"] = $currMinConsHour;
    }
}

if ($prevMaxProdHour != "")
    $telegramMessage = $telegramMessage."Max Hourly Production between ".$prevMaxProdHour." - ".$currMaxProdHour." : ".sprintf("%05.2f",$envoyMaxProdPeriod)." kWh".PHP_EOL;
if ($prevMinProdHour != "")
    $telegramMessage = $telegramMessage."Min Hourly Production between ".$prevMinProdHour." - ".$currMinProdHour." : ".sprintf("%05.2f",$envoyMinProdPeriod)." kWh".PHP_EOL;
if ($prevMaxConsHour != "")
    $telegramMessage = $telegramMessage."Max Hourly Consumption between ".$prevMaxConsHour." - ".$currMaxConsHour." : ".sprintf("%05.2f",$envoyMaxConsPeriod)." kWh".PHP_EOL;
if ($prevMinConsHour != "")
    $telegramMessage = $telegramMessage."Min Hourly Consumption between ".$prevMinConsHour." - ".$currMinConsHour." : ".sprintf("%05.2f",$envoyMinConsPeriod)." kWh".PHP_EOL;

$telegramMessage = $telegramMessage."**************************".PHP_EOL; 
global $telegramChatId;
global $telegramDadChatId;
sendTelegramMessageToBot($telegramChatId,$telegramHourlyBotAPIToken, $telegramMessage);
sendTelegramMessageToBot($telegramDadChatId,$telegramDadHourlyBotAPIToken, $telegramMessage);
closeConnection();
$echoResponse["trace"] = $traceMessage;
$echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
echo json_encode($echoResponse);
?>