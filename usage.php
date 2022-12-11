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
    $lastSelQuery = "SELECT ReadingDate, ReadingImport, ReadingExport , ReadingTimestamp FROM DailyReadings ORDER BY ReadingDate DESC LIMIT 0, 1";
    
    $lastEnvoySelQuery = "SELECT EnvoyReadingDate, EnvoyProduction, EnvoyConsumption FROM EnvoyReadings WHERE EnvoyReadingDate<=? ORDER BY EnvoyReadingDate DESC LIMIT 0,1";
    $lastEnvoySelStmt = $conn->prepare($lastEnvoySelQuery);
    
    $currYTDSelQuery = "SELECT NetImportUnits, NetExportUnits,NetUnitsPerDay,NetImportYTDUnits,NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate<=? ORDER BY NetReadingDate DESC LIMIT 0,1";
    $currYTDSelStmt = $conn->prepare($currYTDSelQuery);

    $src = "";
    $prevFlag = "false";
    if (isset($_GET['src']))
    {
        $src = testinput($_GET['src']);
        if ($src == "ESP")
        {
            $currSelStmt = $conn->prepare($lastSelQuery);
            $currSelStmt->execute();
            $result = $currSelStmt->get_result();
            while ($row = $result->fetch_assoc())
            {
                $readingDate = $row["ReadingDate"];
            }
        }
        //$readingDate = date("Y-m-d",strtotime($readingDate) - 86400);
    }
    else
    {        
        if (isset($_GET['readingDate']))
            $readingDate = testinput($_GET['readingDate']);
        else
            $readingDate = date("Y-m-d");
    }    

    $currSelStmt = $conn->prepare($currSelQuery);
    /*if (isset($_GET['prev']))
    {
        $prevFlag = testinput($_GET['prev']);
        if($prevFlag == "true")
            $readingDate = date("Y-m-d",strtotime($readingDate) - 86400);
    }*/
    $checkQuery = "SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate=?";
    $checkStmtByDate = $conn->prepare($checkQuery);

    $prevBillSelQuery = "SELECT BillImportReading, BillExportReading, MeterImportReading, MeterExportReading FROM NetMeterBillData ORDER BY BillDate DESC LIMIT 0,1";
    $prevBillSelStmt = $conn->prepare($prevBillSelQuery);

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