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

    $latestMeterReadingStmt->execute();
    $result = $latestMeterReadingStmt->get_result();
    if (mysqli_num_rows($result) == 0)
    {
        $echoResponse["ReadingImport"] = 0;
        $echoResponse["ReadingExport"] = 0;
    }
    else
    {
        while ($row = $result->fetch_assoc())
        {
            $echoResponse["ReadingImport"] = $row["ReadingImport"];
            $echoResponse["ReadingExport"] = $row["ReadingExport"];
        }
    }

    $latestBillReadingStmt->execute();
    $result = $latestBillReadingStmt->get_result();
    if (mysqli_num_rows($result) == 0)
    {
        $echoResponse["BillImportReading"] = 0;
        $echoResponse["BillExportReading"] = 0;
        $echoResponse["MeterImportReading"] = 0;
        $echoResponse["MeterExportReading"] = 0;
    }
    else
    {
        while ($row = $result->fetch_assoc())
        {
            $echoResponse["BillImportReading"] = $row["BillImportReading"];
            $echoResponse["BillExportReading"] = $row["BillExportReading"];
            $echoResponse["MeterImportReading"] = $row["MeterImportReading"];
            $echoResponse["MeterExportReading"] = $row["MeterExportReading"];
        }
    }
    echo json_encode($echoResponse);
}
?>