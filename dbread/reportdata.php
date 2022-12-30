<?php
function prepareReport()
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $reportOrder;
    global $fromDate;
    global $toDate;
    global $reportType;
    global $currSelStmt;
    global $currYTDSelStmt;
    global $prevBillSelStmt;
    global $currEnvoySelStmt;
    global $lastYTDSelStmt;
    global $lastEnvoySelStmt;
    global $responseArray;
    global $echoResponse;
    global $reportSrc;
    
    $importData = array();
    $exportData = array();
    $envoyProd = array();
    $envoyCons = array();
    $resultData = "";
    $count = 0;
    if ($reportOrder == "DESC")
        $currDate = $toDate;
    else
        $currDate = $fromDate;

    $resultData = "<table><tr>";
    $resultData = $resultData."<th>Date</th>";
    $resultData = $resultData."<th>Import Reading</th>";
    $resultData = $resultData."<th>Export Reading</th>";
    $resultData = $resultData."<th>Import</th>";
    $resultData = $resultData."<th>Export</th>";
    $resultData = $resultData."<th>Net Units/Day</th>";
    $resultData = $resultData."<th>Imported Units</th>";
    $resultData = $resultData."<th>Exported Units</th>";
    $resultData = $resultData."<th>Net Units</th>";
    $resultData = $resultData."<th>Generated Units</th>";
    $resultData = $resultData."<th>Consumed Units</th>";
    $resultData = $resultData."</tr>";


    $netExportUnits = $netImportUnits = $netImportYTDUnits = 0;
    $netExportYTDUnits = $netYTDUnits = $netUnitsPerDay = 0;
    $defaultValue = sprintf("%.2f",0);
    $readingDate = "";
    $readingImport = $readingExport = $defaultValue;
    if ($reportType == "ROL")
    {
        $repNetImport=$defaultValue;
        $repNetExport=$defaultValue;
        $repNetUnits=$defaultValue;
    }

    while (true)
    {
        $currImportValue = $currExportValue = $defaultValue;
        if($currSelStmt->bind_param("s",$currDate))
        {
            $currSelStmt->execute();
            $result = $currSelStmt->get_result();
            while ($row = $result->fetch_assoc())
            {
               $currImportValue = $row["ReadingImport"];
               $currExportValue = $row["ReadingExport"];
               if($currImportValue !=0 )
               {
                    $readingDate =   $row["ReadingTimestamp"];
                    $readingImport = $currImportValue;
                    $readingExport = $currExportValue;
               }
            }
            if ($currImportValue !=0 )
                $count = $count + 1;
        }
        $netExportUnits = $netImportUnits = $netImportYTDUnits = $defaultValue;
        $netExportYTDUnits = $netYTDUnits = $netUnitsPerDay = $defaultValue;

        if($currYTDSelStmt->bind_param("s",$currDate))
        {
            $currYTDSelStmt->execute();
            $result = $currYTDSelStmt->get_result();
            while ($row = $result->fetch_assoc())
            {
                $netImportUnits = $row["NetImportUnits"];
                $netExportUnits = $row["NetExportUnits"];
                $netUnitsPerDay = $row["NetUnitsPerDay"];
                $netImportYTDUnits = $row["NetImportYTDUnits"];
                $netExportYTDUnits = $row["NetExportYTDUnits"];
                $netYTDUnits = $row["NetYTDUnits"];
            }
        }

        $envoyGenerated = $envoyConsumed = $defaultValue;
        if($currEnvoySelStmt->bind_param("s",$currDate))
        {
            $currEnvoySelStmt->execute();
            $result = $currEnvoySelStmt->get_result();
            while ($row = $result->fetch_assoc())
            {
                $envoyGenerated = $row["EnvoyProduction"];
                $envoyConsumed = $row["EnvoyConsumption"];
            }
        }

        if ($reportType == "ROL")
        {
            $repNetImport = sprintf("%.2f",$repNetImport + $netImportUnits);
            $repNetExport = sprintf("%.2f",$repNetExport + $netExportUnits);
            $repNetUnits = sprintf("%.2f",$repNetUnits + $netUnitsPerDay);
        }
        /*if ($reportType == "ROL")
        {
            $pullDate = date("Y-m-d",strtotime($currDate)-86400);
        
            if($currYTDSelStmt->bind_param("s",$pullDate))
            {
                $currYTDSelStmt->execute();
                $result = $currYTDSelStmt->get_result();
                while ($row = $result->fetch_assoc())
                {
                    $prevNetImportUnits = $row["NetImportUnits"];
                    $prevNetExportUnits = $row["NetExportUnits"];
                    $prevNetUnitsPerDay = $row["NetUnitsPerDay"];
                    $prevNetImportYTDUnits = $row["NetImportYTDUnits"];
                    $prevNetExportYTDUnits = $row["NetExportYTDUnits"];
                    $prevNetYTDUnits = $row["NetYTDUnits"];
                }
            }
        }*/

        if ($currImportValue >= 0)
        {
            $resultData = $resultData."<tr>";
            $resultData = $resultData."<td>".dateinDDMMMYYY($currDate)."</td>";
            $resultData = $resultData."<td>".$currImportValue."</td>";
            $resultData = $resultData."<td>".$currExportValue."</td>";
            $resultData = $resultData."<td>".$netImportUnits."</td>";
            $resultData = $resultData."<td>".$netExportUnits."</td>";
            $resultData = $resultData."<td>".$netUnitsPerDay."</td>";
            //$repNetImport = $repNetExport = $repNetUnits = 0;
            if ($reportType == "ROL")
            {
                $resultData = $resultData."<td>".$repNetImport."</td>";
                $resultData = $resultData."<td>".$repNetExport."</td>";
                $resultData = $resultData."<td>".$repNetUnits."</td>";
            }
            else
            { 
                $resultData = $resultData."<td>".$netImportYTDUnits."</td>";
                $resultData = $resultData."<td>".$netExportYTDUnits."</td>";
                $resultData = $resultData."<td>".$netYTDUnits."</td>";
            }
            $resultData = $resultData."<td>".$envoyGenerated."</td>";
            $resultData = $resultData."<td>".$envoyConsumed."</td>";
            $resultData = $resultData."</tr>";

            // Data in JSON being made available for HomePage and Report Page
            //if ($reportSrc == "HOME")
            //{
            $tempImportArray = array();
            $tempExportArray = array();
            $tempProdArray = array();
            $tempConsArray = array();

            $tempImportArray["date"] = dateinDDMMMYYY($currDate);
            $tempImportArray["value"] = $netImportUnits;
            array_push($importData,$tempImportArray);

            $tempExportArray["date"] = dateinDDMMMYYY($currDate);
            $tempExportArray["value"] = $netExportUnits;
            array_push($exportData,$tempExportArray);
                
            $tempProdArray["date"] = dateinDDMMMYYY($currDate);
            $tempProdArray["value"] = $envoyGenerated;
            array_push($envoyProd,$tempProdArray);

            $tempConsArray["date"] = dateinDDMMMYYY($currDate);
            $tempConsArray["value"]= $envoyConsumed;
            array_push($envoyCons,$tempConsArray);
            //}
        }
        if ($reportOrder == "DESC")
        {
            $currDate = date("Y-m-d", (strtotime($currDate) - 86400));
            if ($currDate < $fromDate )
                break;
        }
        else
        {
            $currDate = date("Y-m-d", (strtotime($currDate) + 86400));
            if ($currDate > $toDate )
                break;            
        }
    }

    $resultData = $resultData."</table>";

    $prevBillSelStmt->execute();
    $result = $prevBillSelStmt->get_result();
    while ($row = $result->fetch_assoc())
    {
        $echoResponse["lastBilledDate"] = date("d-M-Y",strtotime($row["BillDate"]));
        $echoResponse["prevBillImport"] = $row["BillImportReading"];
        $echoResponse["prevBillExport"] = $row["BillExportReading"];
        $echoResponse["prevBillDateImport"]= $row["MeterImportReading"];
        $echoResponse["prevBillDateExport"]= $row["MeterExportReading"];
        $echoResponse["prevBilledImportUnits"] = $row["BillImportedUnits"];
        $echoResponse["prevBilledExportUnits"] = $row["BillExportedUnits"];        
    }

    $lastYTDSelStmt->execute();
    $result = $lastYTDSelStmt->get_result();
    while ($row = $result->fetch_assoc())
    {
        $echoResponse["prevImport"] = $row["NetImportUnits"];
        $echoResponse["prevExport"] = $row["NetExportUnits"];
        $echoResponse["prevNet"] = $row["NetUnitsPerDay"];
    }

    $lastEnvoySelStmt->execute();
    $result = $lastEnvoySelStmt->get_result();
    while ($row = $result->fetch_assoc())
    {
        $echoResponse["prevGenerated"] = $row["EnvoyProduction"];
        $echoResponse["prevConsumed"] = $row["EnvoyConsumption"];
    }

    if ($count >= 1)
    {
        $echoResponse["resultData"] =  $resultData;
        $echoResponse["result"] = "OK";
        $echoResponse["message"] = $responseArray["3"];
        $echoResponse["importData"] = $importData;
        $echoResponse["exportData"] = $exportData;
        $echoResponse["readingDate"] = date("d-M-Y H:i",strtotime($readingDate));
        $echoResponse["readingImport"] = $readingImport;
        $echoResponse["readingExport"] = $readingExport;
        $echoResponse["envoyProd"] = $envoyProd;
        $echoResponse["envoyCons"] = $envoyCons;

    }
    else
    {
        $resultData = "<table></table>";
        $echoResponse["resultData"] =  $resultData;
        $echoResponse["result"] = "NoData";
        $echoResponse["message"] = $responseArray["4"];
    }
    $echoResponse["trace"] = "";
    $resultData = "";
}
?>