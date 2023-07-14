<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
error_reporting(E_ALL);
require '/var/www/mysite/common/database.php';
require '/var/www/mysite/common/helper.php';
require '/var/www/mysite/dbread/reportdata.php';

$echoResponse=array();
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error)
{
  	die("Connection failed: " . $conn->connect_error);
    $echoResponse["result"] = "FATAL";
    echo json_encode($echoResponse);
    exit();
}
else
{
    $latestMeterReadingQuery = "SELECT ReadingImport, ReadingExport, ReadingTimestamp FROM DailyReadings ORDER BY ReadingDate DESC LIMIT 0,1";
    $latestMeterReadingStmt = $conn->prepare($latestMeterReadingQuery);

    $latestBillReadingQuery = "SELECT BillImportReading,BillExportReading,MeterImportReading,MeterExportReading FROM NetMeterBillData ORDER BY BillDate DESC LIMIT 0,1";
    $latestBillReadingStmt =  $conn->prepare($latestBillReadingQuery);
    
    $latestMonthProdConsQuery = "SELECT CurrMonthProd,CurrMonthCons FROM EnvoyMonthlyReadings ORDER BY NetMeterYYYYMM DESC LIMIT 0, 1";
    $latestMonthProdConsStmt = $conn->prepare($latestMonthProdConsQuery);

    $latestMeterReadingStmt->execute();
    $result = $latestMeterReadingStmt->get_result();
    if (mysqli_num_rows($result) == 0)
    {
        $echoResponse["readingImport"] = floatval(0);
        $echoResponse["readingExport"] = floatval(0);
    }
    else
    {
        while ($row = $result->fetch_assoc())
        {
            $echoResponse["readingImport"] = floatval($row["ReadingImport"]);
            $echoResponse["readingExport"] = floatval($row["ReadingExport"]);
        }
    }

    $latestBillReadingStmt->execute();
    $result = $latestBillReadingStmt->get_result();
    if (mysqli_num_rows($result) == 0)
    {
        $echoResponse["billImportReading"] = floatval(0);
        $echoResponse["billExportReading"] = floatval(0);
        $echoResponse["meterImportReading"] = floatval(0);
        $echoResponse["meterExportReading"] = floatval(0);
    }
    else
    {
        while ($row = $result->fetch_assoc())
        {
            $echoResponse["billImportReading"] = floatval($row["BillImportReading"]);
            $echoResponse["billExportReading"] = floatval($row["BillExportReading"]);
            $echoResponse["meterImportReading"] = floatval($row["MeterImportReading"]);
            $echoResponse["meterExportReading"] = floatval($row["MeterExportReading"]);
        }
    }

    $latestMonthProdConsStmt->execute();
    $result = $latestMonthProdConsStmt->get_result();
    if (mysqli_num_rows($result) == 0)
    {
        $echoResponse["currMonthProd"] = floatval(0);
        $echoResponse["currMonthCons"] = floatval(0);
    }
    else
    {
        while ($row = $result->fetch_assoc())
        {
            $echoResponse["currMonthProd"] = floatval($row["CurrMonthProd"]);
            $echoResponse["currMonthCons"] = floatval($row["CurrMonthCons"]);
        }
    }

    echo json_encode($echoResponse);
}
?>