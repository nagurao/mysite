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
$action = "REP";
$src = "WEB";
$scriptVersion = "1.0";

$envoyDateEpoch = 0;
$reportTimeEpoch = 0;
$envoyDateYYYYMM = "";
$dateYYYYMM = "";
$envoyLifeTimeProdRaw = 0;
$envoyLifeTimeConsRaw = 0;
$envoyLifeTimeNetRaw = 0;
$currLifeTimeProdRaw = 0;
$currLifeTimeConsRaw = 0;
$currLifeTimeNetRaw = 0;
$currMonthProd = 0;
$currMonthCons = 0;
$currMonthNet = 0;

$echoResponse["version"] = $scriptVersion;
if (isset($_GET['action']))
    $action = testinput($_GET['action']);

if (isset($_GET['src']))
    $src = testinput($_GET['src']);

if (isset($_GET['date']))
    $dateYYYYMM = testinput($_GET['date']);
else
    $dateYYYYMM = YYYYMMFromEpoch(time());

$reportTimeEpoch = time();
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
    
    $currMonthSelQuery = "SELECT NetMeterYYYYMM, LifeTimeProdRaw, LifeTimeConsRaw, LifeTimeNetRaw, CurrMonthProd, CurrMonthCons, CurrMonthNet FROM EnvoyMonthlyReadings WHERE NetMeterYYYYMM = ? ORDER BY NetMeterYYYYMM DESC LIMIT 0,1";
    $currMonthSelStmt = $conn->prepare($currMonthSelQuery);

    $prevMonthSelQuery = "SELECT NetMeterYYYYMM, LifeTimeProdRaw, LifeTimeConsRaw, LifeTimeNetRaw, CurrMonthProd, CurrMonthCons, CurrMonthNet FROM EnvoyMonthlyReadings WHERE NetMeterYYYYMM <= ? ORDER BY NetMeterYYYYMM DESC LIMIT 0,1";
    $prevMonthSelStmt = $conn->prepare($prevMonthSelQuery);
    
    $currentInsUpdQuery = "REPLACE INTO EnvoyMonthlyReadings SET NetMeterYYYYMM = ?, LifeTimeProdRaw = ?,LifeTimeConsRaw = ?,LifeTimeNetRaw = ?,CurrMonthProd = ?,CurrMonthCons = ?,CurrMonthNet = ?;";
    $currentInsUpdStmt = $conn->prepare($currentInsUpdQuery);

    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

if($action != "INS" && $action != "REP")
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
    echo json_encode($echoResponse);
    exit();
}

if($action == "REP")
{
    if($currMonthSelStmt->bind_param("s",$dateYYYYMM))
    {
        $currMonthSelStmt->execute();
        $result = $currMonthSelStmt->get_result();
        if (mysqli_num_rows($result) > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $currLifeTimeProdRaw = $row["LifeTimeProdRaw"];
                $currLifeTimeConsRaw = $row["LifeTimeConsRaw"];
                $currLifeTimeNetRaw = $row["LifeTimeNetRaw"];
                $currMonthProd = $row["CurrMonthProd"];
                $currMonthCons = $row["CurrMonthCons"];
                $currMonthNet = $row["CurrMonthNet"];  
            }
        }
        else
        {
            $echoResponse["result"] = "NoData";
            $echoResponse["message"] = $responseArray["30"];
            $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
            echo json_encode($echoResponse);
            exit(); 
        }
    }
}
if($action == "INS")
{
    $envoyURL = "http://envoy.local/production.json";
    $envoyData = json_decode(file_get_contents($envoyURL));
    $envoyDateEpoch = $envoyData->consumption[0]->readingTime;
    $reportTimeEpoch = $envoyDateEpoch;
    $envoyLifeTimeProdRaw = $envoyData->production[1]->whLifetime;
    $envoyLifeTimeConsRaw = $envoyData->consumption[0]->whLifetime;
    $envoyLifeTimeNetRaw = $envoyData->consumption[1]->whLifetime;

    if ($envoyDateEpoch == 0)
    {
        $echoResponse["result"] = "NoData";
        $echoResponse["message"] = $responseArray["10"];
        $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
        echo json_encode($echoResponse);
        exit();   
    }
    $envoyDateYYYYMM = YYYYMMFromEpoch($envoyData->consumption[0]->readingTime);
    $dateYYYYMM = $envoyDateYYYYMM;
    if($currMonthSelStmt->bind_param("s",$envoyDateYYYYMM))
    {
        $currMonthSelStmt->execute(); 
        $result = $currMonthSelStmt->get_result();
        if (mysqli_num_rows($result) == 0)
        {
            if($prevMonthSelStmt->bind_param("s",$envoyDateYYYYMM))
            {
                $resultPrev = $prevMonthSelStmt->execute();
                $resultPrev = $prevMonthSelStmt->get_result();
                if (mysqli_num_rows($resultPrev) == 0)
                {
                    $currLifeTimeProdRaw = $envoyLifeTimeProdRaw;
                    $currLifeTimeConsRaw = $envoyLifeTimeConsRaw;
                    $currLifeTimeNetRaw = $envoyLifeTimeNetRaw;
                    $currMonthProd = $currMonthCons = $currMonthNet = 0;  
                }
                else
                {
                    while ($prevRow = $resultPrev->fetch_assoc())
                    {
                        $currLifeTimeProdRaw = $prevRow["LifeTimeProdRaw"];
                        $currLifeTimeConsRaw = $prevRow["LifeTimeConsRaw"];
                        $currLifeTimeNetRaw = $prevRow["LifeTimeNetRaw"];
                    }   
                }
            }    
        }
        else
        {
            while ($row = $result->fetch_assoc())
            {
                $currLifeTimeProdRaw = $row["LifeTimeProdRaw"];
                $currLifeTimeConsRaw = $row["LifeTimeConsRaw"];
                $currLifeTimeNetRaw = $row["LifeTimeNetRaw"];
                $currMonthProd = $row["CurrMonthProd"];
                $currMonthCons = $row["CurrMonthCons"];
                $currMonthNet = $row["CurrMonthNet"];  
            }
        }
        $currMonthProd = $currMonthProd + $envoyLifeTimeProdRaw - $currLifeTimeProdRaw;
        $currMonthCons = $currMonthCons + $envoyLifeTimeConsRaw - $currLifeTimeConsRaw;
        $currMonthNet  = $currMonthNet  + $envoyLifeTimeNetRaw  - $currLifeTimeNetRaw;
        $currLifeTimeProdRaw = $envoyLifeTimeProdRaw;
        $currLifeTimeConsRaw = $envoyLifeTimeConsRaw;
        $currLifeTimeNetRaw  = $envoyLifeTimeNetRaw;

        if ($currentInsUpdStmt->bind_param("sssssss",$envoyDateYYYYMM,$currLifeTimeProdRaw,$currLifeTimeConsRaw,$currLifeTimeNetRaw,$currMonthProd,$currMonthCons,$currMonthNet))
        {
            $currentInsUpdStmt->execute();
            $result = $currentInsUpdStmt->get_result();
            commitNow();
        }
    }
}

if($action == "INS")
    $echoResponse["message"] = $responseArray["28"];
else
    $echoResponse["message"] = $responseArray["29"];
  
$echoResponse["result"] = "OK";
$echoResponse["EnvoyReadingDateTime"] = messageDateTimeFromEpoch($reportTimeEpoch);
$echoResponse["EnvoyReadingYYYYMM"] = $dateYYYYMM;
$echoResponse["EnvoyProductionLifeTime"] = floatval($currLifeTimeProdRaw);
$echoResponse["EnvoyConsumptionLifeTime"] = floatval($currLifeTimeConsRaw);
$echoResponse["EnvoyNetConsumptionLifeTime"] = floatval($currLifeTimeNetRaw);
$echoResponse["CurrMonthProduction"] = floatval(sprintf("%07.2f",round($currMonthProd/1000,2)));
$echoResponse["CurrMonthConsumption"] = floatval(sprintf("%07.2f",round($currMonthCons/1000,2)));
$echoResponse["CurrMonthNetConsumption"] = floatval(sprintf("%07.2f",round($currMonthNet/1000,2)));
closeConnection();
$echoResponse["trace"] = $traceMessage;
$echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
echo json_encode($echoResponse);
?>