<?php
$startTime = hrtime(true);
ini_set('display_errors', 1); 
error_reporting(E_ALL);
require '../common/database.php';
require '../common/helper.php';
require '../dbinsert/insertdata.php';
require '../dbread/reportdata.php';

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
$dateYYYYMMDD = "";
$prodData = array();
$consData = array();
$production = $consumption = 0.00;
$rowCount = 0;
$echoResponse["version"] = $scriptVersion;

if (isset($_GET['action']))
    $action = testinput($_GET['action']);

if (isset($_GET['date']))
    $dateYYYYMMDD = testinput($_GET['date']);
else
    $dateYYYYMMDD = "";

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
    $hourlySelQuery = "SELECT EnvoyReadingDate, EnvoyReadingTimeEpoch, EnvoyReadingTime, EnvoyProdHour, EnvoyConsHour, EnvoyProdDay, EnvoyConsDay FROM EnvoyHourlyReadings WHERE EnvoyReadingDate >= ?";
    $hourlySelStmt = $conn->prepare($hourlySelQuery);
    $echoResponse["trace"] = "";
    $echoResponse["resultData"] = "";
}

if ($action == "REP" && $dateYYYYMMDD == "")
    $dateYYYYMMDD = YYYYMMDDFromEpoch(time());

if ($action == "REP")
{
    if($hourlySelStmt->bind_param("s",$dateYYYYMMDD))
    {
        $echoResponse["EnvoyDate"] = dateinDMY($dateYYYYMMDD);
        $hourlySelStmt->execute();
        $result = $hourlySelStmt->get_result();
        $rowCount = mysqli_num_rows($result);
        if($rowCount > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $readingTimeEpoch = $row["EnvoyReadingTimeEpoch"];
                $tempProdArray = array();
                $tempConsArray = array();
                $tempNetArray = array();
                $tempProdArray["time"] = $tempConsArray["time"] = timeinHHMMSSFromEpoch($readingTimeEpoch);
                $tempProdArray["value"] = $row["EnvoyProdHour"];
                $tempConsArray["value"] = $row["EnvoyConsHour"];
                array_push($prodData,$tempProdArray);
                array_push($consData,$tempConsArray);
                $production = $row["EnvoyProdDay"];
                $consumption = $row["EnvoyConsDay"];
            }
            $echoResponse["result"] = "OK";
            $echoResponse["ProdData"] = $prodData;
            $echoResponse["ConsData"] = $consData;
            $echoResponse["Production"] = $production;
            $echoResponse["Consumption"] = $consumption;
        }
        else
        {
            $echoResponse["result"] = "NoData";
        }

    }
}
else
{
    $echoResponse["result"] = "FATAL";
    $echoResponse["message"] = $responseArray["-98"];
    echo json_encode($echoResponse);
    exit();
}
closeConnection();
$echoResponse["processTime"] = round((hrtime(true) - $startTime)/1e+6,2)."ms";
echo json_encode($echoResponse);
?>