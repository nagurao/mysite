<?php
$startTime = hrtime(true);
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require '/var/www/mysite/common/database.php';
require '/var/www/mysite/common/helper.php';
require '/var/www/mysite/dbinsert/insertdata.php';

$startMMYYYY="202209";
$echoResponse=array();
$traceMessage = "";
$resultData = "";
$responseArray = array();
fillResponseArray();
$debugMessage = "";
$maxReadings = 0;
//$scriptVersion = testinput($_POST['scriptVersion']);
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
    $conn->autocommit(TRUE);

    $insertBillDataQuery="INSERT INTO NetMeterBillData (BillDate, BillYYYYMM, BillImportReading, BillExportReading, BillImportedUnits, BillExportedUnits, BillCarryForward, BillUnitsCredited,MeterImportReading, MeterExportReading, MeterImportedUnits, MeterExportedUnits, MeterCarryForwardUnits) VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertBillDataStmt = $conn->prepare($insertBillDataQuery);

    $checkQuery = "SELECT BillYYYYMM FROM NetMeterBillData WHERE BillYYYYMM=?";
    $checkStmtByDate = $conn->prepare($checkQuery);

    $prevBillDataQuery = "SELECT BillDate, BillYYYYMM, BillImportReading, BillExportReading, BillImportedUnits, BillExportedUnits, BillCarryForward, BillUnitsCredited,MeterImportReading, MeterExportReading, MeterImportedUnits, MeterExportedUnits, MeterCarryForwardUnits FROM NetMeterBillData WHERE BillYYYYMM <= ? ORDER BY BillYYYYMM DESC LIMIT 0,1";
    $prevBillDataStmt = $conn->prepare($prevBillDataQuery);

    $reportBillDataQuery = "SELECT BillDate, BillYYYYMM, BillImportReading, BillExportReading, BillImportedUnits, BillExportedUnits, BillCarryForward, BillUnitsCredited, MeterImportReading, MeterExportReading, MeterImportedUnits, MeterExportedUnits, MeterCarryForwardUnits FROM NetMeterBillData ORDER BY BillYYYYMM DESC";
    $reportBillDataQueryAsc = "SELECT BillDate, BillYYYYMM, BillImportReading, BillExportReading, BillImportedUnits, BillExportedUnits, BillCarryForward, BillUnitsCredited, MeterImportReading, MeterExportReading, MeterImportedUnits, MeterExportedUnits, MeterCarryForwardUnits FROM NetMeterBillData ORDER BY BillYYYYMM";
    
    $reportBillDataStmt = $conn->prepare($reportBillDataQuery);

    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
    //initialBillReadings();
}

if (isset($_POST['action']))
    $action = testinput($_POST['action']);

//    $action = testinput($_POST['action']);

if (isset($_GET['action']))
    $action = testinput($_GET['action']);
/*
$action = "INS";
$billDate = "2022-09-08";
$billImport = "1127.00";
$billExport = "508.00";
$meterImport = "1127.00";
$meterExport = "767.00";
*/
if($action == "INS")
{
    $billDate = testinput($_POST['billDate']);
    $billImport = testinput($_POST['billImport']);
    $billExport = testinput($_POST['billExport']);
    $meterImport = testinput($_POST['meterImport']);
    $meterExport = testinput($_POST['meterExport']);
    insertBillData($billDate,$billImport,$billExport,$meterImport,$meterExport);
    populateBillTable();
    $echoResponse["result"] = "OK";
    $echoResponse["message"] = $responseArray["1"];
}
else if($action == "REP")
{
    populateBillTable();
    $echoResponse["result"] = "OK";
}
else if($action == "GRAPH")
{
    if (isset($_GET['maxReadings']))
        $maxReadings = testinput($_GET['maxReadings']);
    
    $reportBillDataStmt = $conn->prepare($reportBillDataQueryAsc);
    populateBillGraph();
}
else
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    echo json_encode($echoResponse);
    exit();
}

closeConnection();
$echoResponse["trace"] = $traceMessage;
$echoResponse["resultData"] = $resultData;
$echoResponse["debugMessage"] = $debugMessage;
$echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
echo json_encode($echoResponse);
?>