<?php
$startTime = hrtime(true);
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require '/var/www/mysite/common/database.php';
require '/var/www/mysite/common/helper.php';
require '/var/www/mysite/dbread/reportdata.php';

$echoResponse=array();
$traceMessage = "";
$resultData = "";
$responseArray = array();
fillResponseArray();
$debugMessage = "";
$reportSrc = "";

$scriptVersion = "1.0";
$echoResponse["version"] = $scriptVersion;

if ($scriptVersion != "1.0")
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-100"];
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
    echo json_encode($echoResponse);
    exit();
}
else
{
    $currSelQuery = "SELECT ReadingImport, ReadingExport, ReadingTimestamp FROM DailyReadings WHERE ReadingDate=?";
    $currSelStmt = $conn->prepare($currSelQuery);

    $currEnvoySelQuery = "SELECT EnvoyReadingDate, EnvoyProduction, EnvoyConsumption FROM EnvoyReadings WHERE EnvoyReadingDate=?";
    $currEnvoySelStmt = $conn->prepare($currEnvoySelQuery);

    $currYTDSelQuery = "SELECT NetImportUnits, NetExportUnits,NetUnitsPerDay,NetImportYTDUnits,NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate<=? ORDER BY NetReadingDate DESC LIMIT 1,1";
    $currYTDSelStmt = $conn->prepare($currYTDSelQuery);

    $currYTDSelQuery = "SELECT NetImportUnits, NetExportUnits,NetUnitsPerDay,NetImportYTDUnits,NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate=?";
    $currYTDSelStmt = $conn->prepare($currYTDSelQuery);
    
    $prevBillSelQuery = "SELECT BillDate, BillImportReading, BillExportReading, MeterImportReading, MeterExportReading, BillImportedUnits, BillExportedUnits, BillCarryForward, MeterCarryForwardUnits FROM NetMeterBillData ORDER BY BillDate DESC LIMIT 0,1";
    $prevBillSelStmt = $conn->prepare($prevBillSelQuery);

    $lastYTDSelQuery = "SELECT NetImportUnits, NetExportUnits,NetUnitsPerDay FROM NetReadings ORDER BY NetReadingDate DESC LIMIT 0,1";
    $lastYTDSelStmt = $conn->prepare($lastYTDSelQuery);
 
    $lastEnvoySelQuery = "SELECT EnvoyProduction, EnvoyConsumption FROM EnvoyReadings ORDER BY EnvoyReadingDate DESC LIMIT 0,1";
    $lastEnvoySelStmt = $conn->prepare($lastEnvoySelQuery);

    $reportType = testinput($_GET['reportType']);
    $reportOrder = testinput($_GET['reportOrder']);
    $fromDate = testinput($_GET['fromDate']);
    $toDate = testinput($_GET['toDate']);
    
    if (isset($_GET['reportSrc']))
        $reportSrc = testinput($_GET['reportSrc']);

    /* 
    $reportType = "ROL";
    $reportOrder = "ASC";
    $fromDate = "2022-11-10";
    $toDate = "2022-11-12";
    $reportSrc = "HOME";
    /**/
    prepareReport();
    closeConnection();
    $echoResponse["trace"] = $traceMessage;
    $echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";//$_SERVER["REQUEST_TIME_FLOAT"];
    echo json_encode($echoResponse);
}
?>