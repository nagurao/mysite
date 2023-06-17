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
function dateinDMY($dateToConvert)
{
    return strtoupper(date("dMY",strtotime($dateToConvert)));
}

function timeinHHMM($dateToConvert)
{
    return date("H:i",strtotime($dateToConvert));
}

function dMYHi($datetimeToConvert)
{
    return strtoupper(date("dMY H:i",strtotime($datetimeToConvert)));
}

function messageDateTimeFromTimestamp($timestamp)
{
    return strtoupper(date("d-M-Y H:i",strtotime($timestamp)));
}

function dateinDDMMMYYYFromEpoch($epochTime)
{
    return strtoupper(date("d-M-Y",@$epochTime));
}

function timeinHHMMSSFromEpoch($epochTime)
{
    return strtoupper(date("H:i:s",@$epochTime));
}

function messageDateTimeFromEpoch($epochTime)
{
    return strtoupper(date("d-M-Y H:i",@$epochTime));
}

function datetimeFromEpoch($epochTime)
{
    return date("Y-m-d H:i:s",@$epochTime);
}

function YYYYMMDDFromEpoch($epochTime)
{
    return date("Y-m-d",@$epochTime);
}

function YYYYMMFromEpoch($epochTime)
{
    return date("Ym",@$epochTime);
}

function hhFromEpoch($epochTime)
{
    return date("H",@$epochTime);
}

function hhFromTimestamp($timestamp)
{
    return date("H",strtotime($timestamp));    
}
function mmFromEpoch($epochTime)
{
    return date("i",@$epochTime);
}

function ddFromEpoch($epochTime)
{
    return date("d",@$epochTime);
}

function dMYHiFromEpoch($epochTime)
{
    return strtoupper(date("dMY H:i",@$epochTime));
}

function dMYFromEpoch($epochTime)
{
    return strtoupper(date("dMY",@$epochTime));
}

function HHMMFromEpoch($epochTime)
{
    return strtoupper(date("H:i",@$epochTime));
}

function prevDays($numPrevDays)
{
    $prevDays = -1 * $numPrevDays;
    $prevDays =  $prevDays." days";
    return date('Y-m-d', strtotime(@$prevDays));
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
    global $telegramMessage;
    global $whatsappMessage;
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
            $telegramMessage = "";
            $telegramMessage = "Reading Date : ".dateinDDMMMYYY($row["NetReadingDate"]).PHP_EOL.
                               "Imported Units : ".$row["NetImportUnits"]." kWh".PHP_EOL.
                               "Exported Units : ".$row["NetExportUnits"]." kWh".PHP_EOL.
                               "Net Units : ".$row["NetUnitsPerDay"]." kWh".PHP_EOL;
        }
        /*
        array_push($whatsappMessage,"Reading Date : ");
        array_push($whatsappMessage,dateinDDMMMYYY($row["NetReadingDate"]));
        array_push($whatsappMessage,"\n");
        array_push($whatsappMessage,"Imported Units : ");
        array_push($whatsappMessage,$row["NetImportUnits"]);
        array_push($whatsappMessage," kWh\n");
        array_push($whatsappMessage,"Exported Units : ");
        array_push($whatsappMessage,$row["NetExportUnits"]);
        array_push($whatsappMessage," kWh\n"); 
        array_push($whatsappMessage,"Net Units : ");
        array_push($whatsappMessage,$row["NetUnitsPerDay"]);
        array_push($whatsappMessage," kWh"); 
        */
        
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
    $responseArray["8"] = "Envoy (Hourly) Reading Data Inserted successfully.";
    $responseArray["9"] = "Envoy (Hourly) Reading Data Reterived successfully.";  
    $responseArray["10"] = "No Envoy (Hourly) Reading Data available.";  
    $responseArray["11"] = "Envoy Max Production Reading Data Inserted successfully.";
    $responseArray["12"] = "Envoy Max Production Reading Data Reterived successfully.";  
    $responseArray["13"] = "No Envoy Max Production Reading Data available."; 
    $responseArray["14"] = "Current Production less than available Envoy Max Production.";   
    $responseArray["15"] = "Envoy Min Production Reading Data Inserted successfully.";
    $responseArray["16"] = "Envoy Min Production Reading Data Reterived successfully.";  
    $responseArray["17"] = "No Envoy Min Production Reading Data available."; 
    $responseArray["18"] = "Current Production greater than available Envoy Min Production.";  
    $responseArray["19"] = "Envoy Max Consumption Reading Data Inserted successfully.";
    $responseArray["20"] = "Envoy Max Consumption Reading Data Reterived successfully.";  
    $responseArray["21"] = "No Envoy Max Consumption Reading Data available."; 
    $responseArray["22"] = "Current Consumption less than available Envoy Max Consumption.";   
    $responseArray["23"] = "Envoy Min Consumption Reading Data Inserted successfully.";
    $responseArray["24"] = "Envoy Min Consumption Reading Data Reterived successfully.";  
    $responseArray["25"] = "No Envoy Min Consumption Reading Data available."; 
    $responseArray["26"] = "Current Consumption greater than available Envoy Min Consumption.";  
    $responseArray["27"] = "Max/Min Solar Production or Consumption Data Inserted";              
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
    $resultData = $resultData."<th>Bill Units Credited</th>";
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
        $resultData = $resultData."<td>".$row["BillUnitsCredited"]."</td>";
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
    $cfData = array();
    $importBillData = array();
    $exportBillData = array(); 
    $cfBillData = array();
    
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
        $tempCFData = array();
        $tempImportBillData = array();
        $tempExportBillData = array();
        $tempCFBillData = array();

        $tempImportBillData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempImportBillData["value"] = $row["BillImportedUnits"];
        array_push($importBillData,$tempImportBillData);

        $tempExportBillData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempExportBillData["value"] = $row["BillExportedUnits"];
        array_push($exportBillData,$tempExportBillData);

        $tempCFBillData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempCFBillData["value"] = $row["BillCarryForward"];
        array_push($cfBillData,$tempCFBillData);

        $tempImportData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempImportData["value"] = $row["MeterImportedUnits"];
        array_push($importData,$tempImportData);

        $tempExportData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempExportData["value"] = $row["MeterExportedUnits"];
        array_push($exportData,$tempExportData);

        $tempCFData["date"] = date("M-Y",strtotime($row["BillDate"]));
        $tempCFData["value"] = $row["MeterCarryForwardUnits"];
        array_push($cfData,$tempCFData);


    }
    if ($rowCount >= 1)
    {
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["3"];
        $echoResponse["importData"] = $importData;
        $echoResponse["exportData"] = $exportData;
        $echoResponse["cfData"] = $cfData;
        $echoResponse["importBillData"] = $importBillData;
        $echoResponse["exportBillData"] = $exportBillData;
        $echoResponse["cfBillData"] = $cfBillData;
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

function sendWhatsAppMessage($message)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $callmebotPhone;
    global $callmebotAPI;
    global $callmebotURL;
    $finalMessage = "";
    foreach($message as $word)
        $finalMessage = $finalMessage.$word;

    $postMessage = $callmebotURL.urlencode($finalMessage);
    //$url='https://api.callmebot.com/whatsapp.php?source=php&phone='.$phone.'&text='.urlencode($message).'&apikey='.$apikey;
    //$html=file_get_contents($postMessage);
    $response = file_get_contents($postMessage);
}

function sendTelegramMessage($message)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $telegramURL;
    global $telegramChatId;
    $telegram = array();
    $telegram["chat_id"] = $telegramChatId;
    $telegram["text"] = $message;
    $response = file_get_contents($telegramURL.http_build_query($telegram) );   
}

function sendTelegramMessageToBot($telegramChatId,$apiToken,$message)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    $telegramURL = "https://api.telegram.org/bot$apiToken/sendMessage?";
    $telegram = array();
    $telegram["chat_id"] = $telegramChatId;
    $telegram["text"] = $message;
    $response = file_get_contents($telegramURL.http_build_query($telegram) );   
}
?>