<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require 'common/database.php';
require 'common/helper.php';
require 'dbread/reportdata.php';

$echoResponse=array();
$traceMessage = "";
$resultData = "";
$responseArray = array();
fillResponseArray();
$debugMessage = "";

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
    $currSelQuery = "SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate=?";
    $currSelStmt = $conn->prepare($currSelQuery);

    $currYTDSelQuery = "SELECT NetImportUnits, NetExportUnits,NetUnitsPerDay,NetImportYTDUnits,NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate<=? ORDER BY NetReadingDate DESC LIMIT 1,1";
    $currYTDSelStmt = $conn->prepare($currYTDSelQuery);

    $currYTDSelQuery = "SELECT NetImportUnits, NetExportUnits,NetUnitsPerDay,NetImportYTDUnits,NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate=?";
    $currYTDSelStmt = $conn->prepare($currYTDSelQuery);
    
    $reportType = testinput($_GET['reportType']);
    $reportOrder = testinput($_GET['reportOrder']);
    $fromDate = testinput($_GET['fromDate']);
    $toDate = testinput($_GET['toDate']);
    /*
    $reportType = "FIX";
    $reportOrder = "DESC";
    $fromDate = "2022-11-10";
    $toDate = "2022-11-10";
    */
    prepareReport();
    closeConnection();
    $echoResponse["trace"] = $traceMessage;
    echo json_encode($echoResponse);
}
?>