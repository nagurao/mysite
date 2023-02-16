<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require 'common/database.php';
require 'common/helper.php';
require 'dbinsert/insertdata.php';
require 'dbupdate/updatedata.php';

$startDate=strtotime("2022-08-10");
$echoResponse=array();
$traceMessage = "";
$resultData = "";
$responseArray = array();
fillResponseArray();
$debugMessage = "";
$fatalFlag = false;
$whatsappMessage = "";

$scriptVersion = testinput($_POST['scriptVersion']);
//$scriptVersion = "2.0";
$echoResponse["version"] = $scriptVersion;

if (isset($_POST['action']))
    $src = testinput($_POST['action']);
else
    $fatalFlag = true;

if (isset($_POST['readingDate']))
    $readingDate = testinput($_POST['readingDate']);
else
    $fatalFlag = true;

if (isset($_POST['importReading']))
    $importReading = testinput($_POST['importReading']);
else
    $fatalFlag = true;

if (isset($_POST['exportReading']))
    $exportReading = testinput($_POST['exportReading']);
else
    $fatalFlag = true;
//$action = testinput($_POST['action']);
//$readingDate = testinput($_POST['readingDate']);
//$importReading = testinput($_POST['importReading']);
//$exportReading = testinput($_POST['exportReading']);

if ($scriptVersion != "2.0")
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-100"];
    echo json_encode($echoResponse);
    exit();   
}

if ($fatalFlag)
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-101"];
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
    $conn->autocommit(TRUE);
    //globalPreparedStmts();
    $checkQuery = "SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate=?";
    $checkStmtByDate = $conn->prepare($checkQuery);

    $insertImpExpQuery = "INSERT INTO DailyReadings (ReadingDate, ReadingImport, ReadingExport) VALUE (?, ?, ?)";
    $insertStmtImpExpByDate = $conn->prepare($insertImpExpQuery);

    $netReadingQuery  = "INSERT INTO NetReadings (NetReadingDate, NetImportUnits, NetExportUnits,NetUnitsPerDay,NetImportYTDUnits,NetExportYTDUnits,NetYTDUnits) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $netReadingStmt = $conn->prepare($netReadingQuery);
    
    $netReadingUpdateQuery ="UPDATE NetReadings SET NetImportUnits=?, NetExportUnits=?,NetUnitsPerDay=?,NetImportYTDUnits=?,NetExportYTDUnits=?,NetYTDUnits=? WHERE NetReadingDate=?";
    $netReadingUpdateStmt = $conn->prepare($netReadingUpdateQuery);

    $updateQueryImpExpByDate = "UPDATE DailyReadings SET ReadingImport=?, ReadingExport=? WHERE ReadingDate=?";
    $updateStmtImpExpByDate =  $conn->prepare($updateQueryImpExpByDate);

    $currSelQuery = "SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate<=? ORDER BY ReadingDate DESC LIMIT 0, 1";
    $currSelStmt = $conn->prepare($currSelQuery);
    
    $prevSelQuery = "SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate<=? ORDER BY ReadingDate DESC LIMIT 1, 1";
    $prevSelStmt = $conn->prepare($prevSelQuery);

    $prevYTDInsQuery = "SELECT NetImportYTDUnits, NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate<=? ORDER BY NetReadingDate DESC LIMIT 0,1";
    $prevYTDInsStmt = $conn->prepare($prevYTDInsQuery);

    $prevYTDUpdQuery = "SELECT NetImportYTDUnits, NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate<=? ORDER BY NetReadingDate DESC LIMIT 1,1";
    $prevYTDUpdStmt = $conn->prepare($prevYTDUpdQuery);

    initialReadings();
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

/*
$action = testinput($_POST['action']);
$readingDate = testinput($_POST['readingDate']);
$importReading = testinput($_POST['importReading']);
$exportReading = testinput($_POST['exportReading']);
*/

/*
$action = "INS";
$readingDate = "2022-08-15";
$importReading = 874.20;
$exportReading = 219.50;
$readingDate = "2022-08-12";
$importReading = 845.80;
$exportReading = 189.70;
*/

if (strtotime($readingDate) < $startDate)
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-99"];
    echo json_encode($echoResponse);
    exit();
}

$action=operationToPeform($readingDate);

if($action == "INS")
{
    insertMissingReadingData($readingDate);
    insertReadingData($readingDate,$importReading,$exportReading);
    insertNetMeterCalcData($readingDate);
    $echoResponse["result"] = "OK";
    $echoResponse["message"] = $responseArray["1"];
}
else if($action == "UPD")
{
    updateReadingData($readingDate,$importReading,$exportReading);
    updateImpactedReadingData($readingDate,$importReading,$exportReading);
    updateImpactedNetMeterCalcData($readingDate);
    $echoResponse["result"] = "OK";
    $echoResponse["message"] = $responseArray["2"];
}
else
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    echo json_encode($echoResponse);
    exit();
}

populateResponseTable($readingDate);
closeConnection();
$echoResponse["trace"] = $traceMessage;
$echoResponse["resultData"] = $resultData;
$echoResponse["debugMessage"] = $debugMessage;
echo json_encode($echoResponse);
sendWhatsAppMessage($whatsappMessage);
?>