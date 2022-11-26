<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);

require 'common/database.php';
require 'dbread/readdata.php';
require 'common/helper.php';

$echoResponse=array();
$responseArray = array();
fillResponseArray();

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
    $currSelQuery = "SELECT ReadingDate, ReadingImport, ReadingExport , ReadingTimestamp FROM DailyReadings WHERE ReadingDate<=? ORDER BY ReadingDate DESC LIMIT 0, 1";
    $currSelStmt = $conn->prepare($currSelQuery);

    $currYTDSelQuery = "SELECT NetImportUnits, NetExportUnits,NetUnitsPerDay,NetImportYTDUnits,NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate<=? ORDER BY NetReadingDate DESC LIMIT 0,1";
    $currYTDSelStmt = $conn->prepare($currYTDSelQuery);

    $src = "";
    $prevFlag = "false";
    $readingDate = testinput($_GET['readingDate']);
    if (isset($_GET['src']))
    {
        $src = testinput($_GET['src']);
        //$readingDate = date("Y-m-d",strtotime($readingDate) - 86400);
    }

    if (isset($_GET['prev']))
    {
        $prevFlag = testinput($_GET['prev']);
        if($prevFlag == "true")
            $readingDate = date("Y-m-d",strtotime($readingDate) - 86400);
    }
    $checkQuery = "SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate=?";
    $checkStmtByDate = $conn->prepare($checkQuery);

    if (readingExists($readingDate))
        getNetMeterReadings($readingDate);
    else
    {
        $echoResponse["ReadingDate"] = strtoupper(dateinDDMMMYYY($readingDate));
        $echoResponse["ReadingTimeHHMM"] = date("H:i",strtotime("now"));
        $echoResponse["result"] = "NoData";
        $echoResponse["message"] = $responseArray["4"];
    }
    //getNetMeterReadings ('2021-11-01');
    closeConnection();
    echo json_encode($echoResponse);
}

?>