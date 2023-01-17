<?php

function globalPreparedStmts()
{
    $checkQuery = "SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate=?";
    //$checkStmtByDate = $conn->prepare($checkQuery);
}
function dateinDDMMMYYY($dateToConvert)
{
    return date("d-M-Y",strtotime($dateToConvert));
}

function operationToPeform($readingDate)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__.$readingDate;

    if (readingExists($readingDate))
        return "UPD";
    return "INS";
}

function readingExists($readingDate)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__.$readingDate;
    global $checkStmtByDate;
    if ($checkStmtByDate->bind_param("s", $readingDate))
    {
        $checkStmtByDate->execute();
        if($checkStmtByDate->get_result()->num_rows > 0)
            return true;
    }
    return false;
}

function commitNow()
{
    global $conn;
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    $conn->commit();
}

function closeConnection()
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $conn;
    $conn->close();
}

function populateResponseTable($readingDate)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__.$readingDate;
    global $conn;
    global $resultData;
    $netReadingSelectQuery ="SELECT NetReadingDate, NetImportUnits, NetExportUnits, NetUnitsPerDay, NetImportYTDUnits ,NetExportYTDUnits ,NetYTDUnits FROM NetReadings WHERE NetReadingDate>=?";
    $netReadingSelectStmt = $conn->prepare($netReadingSelectQuery);

    $resultData = "<table><tr>";
    $resultData = $resultData."<th>Date</th>";
    $resultData = $resultData."<th>Import</th>";
    $resultData = $resultData."<th>Export</th>";
    $resultData = $resultData."<th>Units/ Day</th>";
    $resultData = $resultData."<th>Net Import</th>";
    $resultData = $resultData."<th>Net Export</th>";
    $resultData = $resultData."<th>Net Units</th>";
    $resultData = $resultData."</tr>";
    

    if ($netReadingSelectStmt->bind_param("s",$readingDate))
    {
        $netReadingSelectStmt->execute();
        $result = $netReadingSelectStmt->get_result();
        while ($row = $result->fetch_assoc())
        {
            $resultData = $resultData."<tr>";
            $resultData = $resultData."<td>".dateinDDMMMYYY($row["NetReadingDate"])."</td>";
            $resultData = $resultData."<td>".$row["NetImportUnits"]."</td>";
            $resultData = $resultData."<td>".$row["NetExportUnits"]."</td>";
            $resultData = $resultData."<td>".$row["NetUnitsPerDay"]."</td>";
            $resultData = $resultData."<td>".$row["NetImportYTDUnits"]."</td>";
            $resultData = $resultData."<td>".$row["NetExportYTDUnits"]."</td>";
            $resultData = $resultData."<td>".$row["NetYTDUnits"]."</td>";
            $resultData = $resultData."</tr>";
        }
    }

    $resultData = $resultData."</table>";
}

function populateEnvoyResponseTable($readingDate)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__.$readingDate;
    global $conn;
    global $resultData;
    $envoyReadingSelectQuery ="SELECT EnvoyReadingDate, EnvoyProductionActual, EnvoyConsumptionActual, EnvoyProduction, EnvoyConsumption FROM EnvoyReadings WHERE EnvoyReadingDate>=?";
    $envoyReadingSelectStmt = $conn->prepare($envoyReadingSelectQuery);

    $resultData = "<table><tr>";
    $resultData = $resultData."<th>Date</th>";
    $resultData = $resultData."<th>Production (W)</th>";
    $resultData = $resultData."<th>Consumption (W)</th>";
    $resultData = $resultData."<th>Production (kW)</th>";
    $resultData = $resultData."<th>Consumption (kW)</th>";
    $resultData = $resultData."</tr>";
    

    if ($envoyReadingSelectStmt->bind_param("s",$readingDate))
    {
        $envoyReadingSelectStmt->execute();
        $result = $envoyReadingSelectStmt->get_result();
        while ($row = $result->fetch_assoc())
        {
            $resultData = $resultData."<tr>";
            $resultData = $resultData."<td>".dateinDDMMMYYY($row["EnvoyReadingDate"])."</td>";
            $resultData = $resultData."<td>".$row["EnvoyProductionActual"]."</td>";
            $resultData = $resultData."<td>".$row["EnvoyConsumptionActual"]."</td>";
            $resultData = $resultData."<td>".$row["EnvoyProduction"]."</td>";
            $resultData = $resultData."<td>".$row["EnvoyConsumption"]."</td>";
            $resultData = $resultData."</tr>";
        }
    }

    $resultData = $resultData."</table>";
}

function fillResponseArray()
{
    global $responseArray;
    $responseArray["0"] = "Fatal Aplication Error.";
    $responseArray["1"] = "Net Meter Data Inserted successfully.";
    $responseArray["2"] = "Net Meter Data Updated successfully.";
    $responseArray["3"] = "Net Meter Data Reterived successfully.";
    $responseArray["4"] = "No Net Meter Data available.";
    $responseArray["5"] = "Envoy Reading Data Inserted successfully.";
    $responseArray["6"] = "Envoy (Local) Reading Data Inserted successfully.";
    $responseArray["7"] = "Envoy (Local) Reading Data Reterived successfully.";
    $responseArray["-1"] = "Error inserting Net Meter details.";
    $responseArray["-2"] = "Error updating Net Meter details.";
    $responseArray["-97"] = "Database Auto Commit Issue.".
    $responseArray["-98"] = "Invalid Operation.";
    $responseArray["-99"] = "Invalid Meter Reading Date.";
    $responseArray["-100"] = "Invalid Script Version";
    $responseArray["-101"] = "Required Parameters not passed";
}

function testinput($data)
{
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}


/* The folloing is called from
    srvnetmeterbill.php
*/
function populateBillTable()
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $resultData;
    global $reportBillDataStmt;
    $resultData = "<table><tr>";
    $resultData = $resultData."<th>Bill Date</th>";
    $resultData = $resultData."<th>Bill Imp</th>";
    $resultData = $resultData."<th>Bill Exp</th>";
    $resultData = $resultData."<th>Imp Units</th>";
    $resultData = $resultData."<th>Exp Units</th>";
    $resultData = $resultData."<th>Bill CF Units</th>";
    $resultData = $resultData."<th>Actual Imp</th>";
    $resultData = $resultData."<th>Actual Exp</th>";
    $resultData = $resultData."<th>Actual Imp Units</th>";
    $resultData = $resultData."<th>Actual Exp Units</th>";
    $resultData = $resultData."<th>Actual CF Units</th>";
    $resultData = $resultData."</tr>";
    

    $reportBillDataStmt->execute();
    $result = $reportBillDataStmt->get_result();
    while ($row = $result->fetch_assoc())
    {
        $billDateDMY = date("d-M-Y",strtotime($row["BillDate"]));
        $resultData = $resultData."<tr>";
        $resultData = $resultData."<td>".dateinDDMMMYYY($billDateDMY)."</td>";
        $resultData = $resultData."<td>".$row["BillImportReading"]."</td>";
        $resultData = $resultData."<td>".$row["BillExportReading"]."</td>";
        $resultData = $resultData."<td>".$row["BillImportedUnits"]."</td>";
        $resultData = $resultData."<td>".$row["BillExportedUnits"]."</td>";
        $resultData = $resultData."<td>".$row["BillCarryForward"]."</td>";
        $resultData = $resultData."<td>".$row["MeterImportReading"]."</td>";
        $resultData = $resultData."<td>".$row["MeterExportReading"]."</td>";
        $resultData = $resultData."<td>".$row["MeterImportedUnits"]."</td>";
        $resultData = $resultData."<td>".$row["MeterExportedUnits"]."</td>";
        $resultData = $resultData."<td>".$row["MeterCarryForwardUnits"]."</td>";            
        $resultData = $resultData."</tr>";
    }
    $resultData = $resultData."</table>";
}

function populateBillGraph()
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $responseArray;
    global $reportBillDataStmt; 
    global $maxReadings; 
    global $echoResponse;
    $importData = array();
    $exportData = array();
    $importBillData = array();
    $exportBillData = array(); 
    
    $rowCount = 0;
    $reportBillDataStmt->execute();
    $result = $reportBillDataStmt->get_result();
    while ($row = $result->fetch_assoc())
    {
        $rowCount = $rowCount + 1;
        if ($maxReadings != 0 && $rowCount > $maxReadings)
            break;
        $tempImportData = array();
        $tempExportData = array();
        $tempImportBillData = array();
        $tempExportBillData = array();

        $tempImportBillData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempImportBillData["value"] = $row["BillImportedUnits"];
        array_push($importBillData,$tempImportBillData);

        $tempExportBillData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempExportBillData["value"] = $row["BillExportedUnits"];
        array_push($exportBillData,$tempExportBillData);

        $tempImportData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempImportData["value"] = $row["MeterImportedUnits"];
        array_push($importData,$tempImportData);

        $tempExportData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempExportData["value"] = $row["MeterExportedUnits"];
        array_push($exportData,$tempExportData);
    }
    if ($rowCount >= 1)
    {
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["3"];
        $echoResponse["importData"] = $importData;
        $echoResponse["exportData"] = $exportData;
        $echoResponse["importBillData"] = $importBillData;
        $echoResponse["exportBillData"] = $exportBillData;
    }
    else
    {
        $echoResponse["result"] = "NoData";
        $echoResponse["message"] = $responseArray["4"];
    }
}

function getDataFromEnphase($envoyReadingDate,$url,$key)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $enphaseKey;
    global $enphaseUserId;
    global $enphaseReadingStartDate;
    global $enphaseReadingEndDate;

    $dataURL = $url.$enphaseKey.$enphaseUserId.$enphaseReadingStartDate.$envoyReadingDate.$enphaseReadingEndDate.$envoyReadingDate;
    $returnValue = 0;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $dataURL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    $response = json_decode(curl_exec($curl),true);
    curl_close($curl);
    if($response["start_date"] == $envoyReadingDate && count($response[$key]) > 0)
        $returnValue = $response[$key][0];
    return $returnValue;
}
?>
