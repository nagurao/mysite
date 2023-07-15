<?php

function initialReadings()
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__;
    global $checkStmtByDate;
    $startDate = "2022-08-10";
    $initialImport = 828.10;
    $initialExport = 146.50;
    $initialNetImport = 2.10;
    $initialNetExport = 10.50;
    if ($checkStmtByDate->bind_param("s", $startDate))
    {
        $checkStmtByDate->execute();
        $result = $checkStmtByDate->get_result();
        $i=0;
        while ($result->fetch_assoc())
            $i=$i+1;   
        if ($i==0) 
        {
            insertReadingData($startDate,$initialImport,$initialExport);
            $currPerDay = $initialNetImport - $initialNetExport;
            $currImportYTD =  $initialNetImport;
            $currExportYTD =  $initialNetExport;
            $currYTD = $currImportYTD - $currExportYTD;
            initialNetMeterCalcReadings($startDate,$initialNetImport,$initialNetExport,$currPerDay,$currImportYTD,$currExportYTD,$currYTD);
        }
    }
}

function initialNetMeterCalcReadings($startDate,$initialNetImport,$initialNetExport,$currPerDay,$currImportYTD,$currExportYTD,$currYTD)
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__.$startDate.$initialNetImport.$initialNetExport.$currPerDay.$currImportYTD.$currExportYTD.$currYTD;
    global $netReadingStmt;
    if ($netReadingStmt->bind_param("sssssss",$startDate,$initialNetImport,$initialNetExport,$currPerDay,$currImportYTD,$currExportYTD,$currYTD))
    {
       $netReadingStmt->execute();
       $result = $netReadingStmt->get_result();
       commitNow(__FUNCTION__);
    }
}

function insertReadingData($readingDate,$importReading,$exportReading)
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate.$importReading.$exportReading;
    global $insertStmtImpExpByDate;
    if ($insertStmtImpExpByDate->bind_param("sss",$readingDate,$importReading,$exportReading))
    {
        $insertStmtImpExpByDate->execute();
        $result = $insertStmtImpExpByDate->get_result();
        commitNow();
    }
}

function insertEnvoyReadingData($readingDate,$productionReading,$consumptionReading)
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate.$productionReading.$consumptionReading;
    global $insertStmtProdConsumptionByDate;
    $productionReadingRounded = sprintf("%05.2f",round($productionReading/1000,1));
    $consumptionReadingRounded = sprintf("%05.2f",round($consumptionReading/1000,1));
    if ($insertStmtProdConsumptionByDate->bind_param("sssss",$readingDate,$productionReading,$consumptionReading,$productionReadingRounded,$consumptionReadingRounded))
    {
        $insertStmtProdConsumptionByDate->execute();
        $result = $insertStmtProdConsumptionByDate->get_result();
        commitNow();
    }
}

function insertMissingReadingData($readingDate)
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate;
    global $checkStmtByDate;
    $currDate=strtotime($readingDate);
    $numPrevDays=1;
    $prevImport=$prevExport="";
    $backDate="";
    while(true)
    {
        $backDate=date("Y-m-d",$currDate - (86400 * $numPrevDays));
        if ($checkStmtByDate->bind_param("s", $backDate))
        {
            $checkStmtByDate->execute();
            $result = $checkStmtByDate->get_result();
            $i = 0;
            while($row = $result->fetch_assoc())
            {
                $prevImport = $row["ReadingImport"];
                $prevExport = $row["ReadingExport"];
                $i = $i + 1;
            }
            if ($i>0) break;
            else $numPrevDays = $numPrevDays + 1;
        }
        // Check whether we have gone too back in time
        $prevDate=strtotime($backDate);
        //if ($prevDate <= $startDate) break;
    }
    while ($numPrevDays-1>=1)
    {
        $backdate=date("Y-m-d",$prevDate);
        insertReadingData($backdate,$prevImport,$prevExport);
        insertNetMeterCalcData($backdate);
        $prevDate = $prevDate + 86400;
        $numPrevDays = $numPrevDays - 1;
    }
}

function insertNetMeterCalcData($readingDate)
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate;
    global $netReadingStmt;
    global $currSelStmt;
    global $prevSelStmt;
    global $prevYTDInsStmt;
    $currImportValue=0;
    $currExportValue=0;
    $prevImportValue=0;
    $prevExportValue=0;
    $prevImportYTD=0;
    $prevExportYTD=0;
    $prevYTD=0;
    //$currSelStmt = $conn->prepare("SELECT readingdate, import, export FROM importexport WHERE readingdate<=? ORDER BY readingdate DESC LIMIT 0, 1");
    //$currSelStmt = $conn->prepare("SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate<=? ORDER BY ReadingDate DESC LIMIT 0, 1");
    //$prevSelStmt = $conn->prepare("SELECT ReadingDate, ReadingImport, ReadingExport FROM DailyReadings WHERE ReadingDate<=? ORDER BY ReadingDate DESC LIMIT 1, 1");
    //$prevYTDStmt = $conn->prepare("SELECT NetImportYTDUnits, NetExportYTDUnits,NetYTDUnits FROM NetReadings WHERE NetReadingDate<=? ORDER BY NetReadingDate DESC LIMIT 0,1");

    if($currSelStmt->bind_param("s",$readingDate))
    {
       $currSelStmt->execute();
       $result = $currSelStmt->get_result();
       while ($row = $result->fetch_assoc())
       {
          $currImportValue = $row["ReadingImport"];
          $currExportValue = $row["ReadingExport"];
       }
    }
    
    if($prevSelStmt->bind_param("s",$readingDate))
    {
       $prevSelStmt->execute();
       $result = $prevSelStmt->get_result();
       while ($row = $result->fetch_assoc())
       {
          $prevImportValue = $row["ReadingImport"];
          $prevExportValue = $row["ReadingExport"];
       }
    }
    
    if($prevYTDInsStmt->bind_param("s",$readingDate))
    {
       $prevYTDInsStmt->execute();
       $result = $prevYTDInsStmt->get_result();
       while ($row = $result->fetch_assoc())
       {
          $prevImportYTD=$row["NetImportYTDUnits"];
          $prevExportYTD=$row["NetExportYTDUnits"];
          $prevYTD = $row["NetYTDUnits"];
       }
    } 
    
    $currImport = $currImportValue - $prevImportValue;
    $currExport = $currExportValue - $prevExportValue;
    $currPerDay = $currImport - $currExport;
    $currImportYTD =  $currImport + $prevImportYTD;
    $currExportYTD =  $currExport + $prevExportYTD;
    $currYTD = $currPerDay +  $prevYTD;
    
    if ($netReadingStmt->bind_param("sssssss",$readingDate,$currImport,$currExport,$currPerDay,$currImportYTD,$currExportYTD,$currYTD))
    {
        $netReadingStmt->execute();
        $result = $netReadingStmt->get_result();
        commitNow();
    }
}


/* The folloing is called from
    srvnetmeterbill.php
*/

function initialBillReadings()
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__;
    global $checkStmtByDate;
    $billDate = "2022-08-10";
    $billDateYYYYMM = "202208";
    $billImportReading = $billImpUnits = $meterImpReading = $meterImpUnits = 826.00;
    $billExportReading = $billExpUnits = $meterExpReading = $meterExpUnits = 136.00;
    $billCFUnits = $meterCFUnits = $billUnitsCredited = 0.00;
    if ($checkStmtByDate->bind_param("s", $billDateYYYYMM))
    {
        $checkStmtByDate->execute();
        $result = $checkStmtByDate->get_result();
        $i=0;
        while ($result->fetch_assoc())
            $i=$i+1;   
        if ($i==0) 
        {
            insertBillDetails($billDate,$billImportReading, $billExportReading,$billImpUnits, $billExpUnits,$billCFUnits,$billUnitsCredited,$meterImpReading, $meterExpReading, $meterImpUnits, $meterExpUnits, $meterCFUnits);
            commitNow(__FUNCTION__);
        }
    }
}

function insertBillData($billDate,$billImport,$billExport,$meterImport,$meterExport)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $prevBillDataStmt;
    global $insertBillDataStmt;
    $prevBillImpReadings = $prevBillExpReadings = 0;
    $prevMeterImpReadings = $prevMeterExpReadings = 0;
    $prevBillImport = $prevBillExport = $prevBillCFUnits = 0;
    $prevMeterImport = $prevMeterExport = $prevMeterCFUnits = 0;

    $currBillImpReadings = $currBillExpReadings = 0;
    $currMeterImpReadings = $currMeterExpReadings = 0;
    $currBillImport = $currBillExport = $currBillCFUnits = 0;
    $currMeterImport = $currMeterExport = $currMeterCFUnits = 0;
    $currBillUnitsCredited = 0;

    $currMM = date("m",strtotime($billDate));
    $currYYYYMM = date("Ym",strtotime($billDate));
    $prevDate = strtotime($billDate);

    $loopIndex = 0;
    while (true)
    {
        $prevMM = date("m",strtotime($billDate) - (86400 * $loopIndex));
        $prevDate = date("Y-m-d",strtotime($billDate) - (86400 * $loopIndex));
        if($prevMM != $currMM)
            break;
        $loopIndex = $loopIndex + 1;
    }

    $prevBillDateYYYYMM = date("Ym",strtotime($prevDate));

    if ($prevBillDataStmt->bind_param("s", $prevBillDateYYYYMM))
    {
        $prevBillDataStmt->execute();
        $result = $prevBillDataStmt->get_result();
        while($row = $result->fetch_assoc())
        {
            $prevBillImpReadings = $row["BillImportReading"];
            $prevBillExpReadings = $row["BillExportReading"];
            $prevBillImport = $row["BillImportedUnits"];
            $prevBillExport = $row["BillExportedUnits"];
            $prevBillCFUnits = $row["BillCarryForward"];
            $prevMeterImpReadings = $row["MeterImportReading"];
            $prevMeterExpReadings = $row["MeterExportReading"];
            $prevMeterImport = $row["MeterImportedUnits"];
            $prevMeterExport = $row["MeterExportedUnits"];
            $prevMeterCFUnits = $row["MeterCarryForwardUnits"];
        }
    }

    if ($currMM == 01 or $currMM == 07 )
    {
        $currBillUnitsCredited = $prevBillCFUnits;
        $prevMeterCFUnits = $prevMeterCFUnits - $currBillUnitsCredited;
        $prevBillCFUnits = 0;
    }

    $currBillImpReadings = $billImport;
    $currBillExpReadings = $billExport;
    $currBillImport = $currBillImpReadings - $prevBillImpReadings;
    $currBillExport = $currBillExpReadings - $prevBillExpReadings;
    $currBillCFUnits = $prevBillCFUnits + $currBillExport - $currBillImport;

    $currMeterImpReadings = $meterImport;
    $currMeterExpReadings = $meterExport;
    $currMeterImport = $currMeterImpReadings - $prevMeterImpReadings;
    $currMeterExport = $currMeterExpReadings - $prevMeterExpReadings;
    $currMeterCFUnits = $prevMeterCFUnits + $currMeterExport - $currMeterImport;

    if($currYYYYMM == "202208")
    {
        $billDate = "2022-08-10";
        $currYYYYMM = "202208";
        $currBillImpReadings = $currBillImport = $currMeterImpReadings = $currMeterImport = 826.00;
        $currBillExpReadings = $currBillExport = $currMeterExpReadings = $currMeterExport = 136.00;
        $currBillCFUnits = $currMeterCFUnits = $currBillUnitsCredited = 0.00;
    }

    if($insertBillDataStmt->bind_param("sssssssssssss",$billDate, $currYYYYMM, $currBillImpReadings, $currBillExpReadings, $currBillImport, $currBillExport, $currBillCFUnits, $currBillUnitsCredited, $currMeterImpReadings, $currMeterExpReadings, $currMeterImport, $currMeterExport, $currMeterCFUnits ))
    {
        $insertBillDataStmt->execute();
        $result = $insertBillDataStmt->get_result();
        commitNow(__FUNCTION__);
    }
}
function insertBillDetails($billDate,$billImport, $billExport,$billImpUnits, $billExpUnits,$billCFUnits,$billUnitsCredited,$meterImport, $meterExport, $meterImpUnits, $meterExpUnits, $meterCFUnits)
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $insertBillDataStmt;
    $billDateYYYYMM = date("Ym",strtotime($billDate));
    if ($insertBillDataStmt->bind_param("sssssssssssss",$billDate,$billDateYYYYMM,$billImport, $billExport,$billImpUnits, $billExpUnits,$billCFUnits,$$billUnitsCredited,$meterImport, $meterExport, $meterImpUnits, $meterExpUnits, $meterCFUnits))
    {
       $insertBillDataStmt->execute();
       $result = $insertBillDataStmt->get_result();
       commitNow(__FUNCTION__);
    }
}


function insertLocalEnvoyData()
{
    global $insertEnvoyLocalStmt;
    global $envoyLocalDateTime;
    global $envoyLocalProduction;
    global $envoyLocalConsumption;
    global $envoyLocalNet;
    global $envoyLocalProductionDay;
    global $envoyLocalConsumptionDay;

    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;

    //$envoyURL = "http://envoy.local/production.json";
    //$envoyData = json_decode(file_get_contents($envoyURL));
    $envoyData = json_decode(getDataFromEnvoy(),false);
    $envoyLocalDateTime = $envoyData->consumption[0]->readingTime;
    $envoyLocalDate = YYYYMMDDFromEpoch($envoyLocalDateTime);
    $envoyLocalProductionRaw = $envoyData->production[1]->wNow;
    $envoyLocalProduction = round($envoyData->production[1]->wNow,2);
    $envoyLocalConsumptionRaw = $envoyData->consumption[0]->wNow;
    $envoyLocalConsumption = round($envoyData->consumption[0]->wNow,2);
    $envoyLocalNetRaw = $envoyData->consumption[1]->wNow;
    $envoyLocalNet = round($envoyData->consumption[1]->wNow,2);
    $envoyLocalProductionDayRaw = $envoyData->production[1]->whToday;
    $envoyLocalProductionDay = round($envoyData->production[1]->whToday/1000,2);
    $envoyLocalConsumptionDayRaw = $envoyData->consumption[0]->whToday;
    $envoyLocalConsumptionDay = round($envoyData->consumption[0]->whToday/1000,2);

    if($insertEnvoyLocalStmt->bind_param("ssssssssssss",$envoyLocalDate,$envoyLocalDateTime,$envoyLocalConsumptionRaw , $envoyLocalConsumption, $envoyLocalProductionRaw,$envoyLocalProduction, $envoyLocalNetRaw,$envoyLocalNet, $envoyLocalProductionDayRaw, $envoyLocalProductionDay, $envoyLocalConsumptionDayRaw, $envoyLocalConsumptionDay ));
    {
        $insertEnvoyLocalStmt->execute();
        $result = $insertEnvoyLocalStmt->get_result();
        commitNow(__FUNCTION__);
    }

    /*
    echo ("Current Consumption is ".round($envoyData->consumption[0]->wNow,2)."W");
echo ("Net Consumption is ".round($envoyData->consumption[1]->wNow,2)."W");
echo ("Current Production is ".(round($envoyData->production[1]->wNow/1000,2))."kWh");
echo ("Consumed ".(round($envoyData->consumption[0]->whToday/1000,2))."kWh");
echo ("Produced ".(round($envoyData->production[1]->whToday/1000,2))."kWh");
    */
}

function insertLocalEnvoyHourlyData()
{
    global $traceMessage;
    $traceMessage = $traceMessage."->".__FUNCTION__;
    global $insertEnvoyHourlyStmt;
    global $currEnvoyHourlySelStmt;
    global $prevEnvoyHourlySelStmt;
    global $action;
    global $prevAction;
    global $envoyDateEpoch;
    global $envoyProductionPrevHour;
    global $envoyConsumptionPrevHour;
    global $envoyProductionDay;
    global $envoyConsumptionDay;
    global $envoyProductionDayPrevHour;
    global $envoyConsumptionDayPrevHour;
    
    //$envoyURL = "http://envoy.local/production.json";
    //$envoyData = json_decode(file_get_contents($envoyURL));
    $envoyData = json_decode(getDataFromEnvoy(),false);
    $envoyDateEpoch = $envoyData->consumption[0]->readingTime;
    $envoyDate = YYYYMMDDFromEpoch($envoyDateEpoch);
    $envoyDateTime = datetimeFromEpoch($envoyDateEpoch);
    $envoyProductionDay = round($envoyData->production[1]->whToday/1000,2);
    $envoyConsumptionDay = round($envoyData->consumption[0]->whToday/1000,2);

    $envoyProductionPrevHour = $envoyProductionDay - $envoyProductionDayPrevHour;
    $envoyConsumptionPrevHour = $envoyConsumptionDay - $envoyConsumptionDayPrevHour;
    $envoyPMonth = $envoyProductionMonth + $envoyProductionPrevHour;
    $envoyCMonth = $envoyConsumptionMonth + $envoyConsumptionPrevHour;
    if($insertEnvoyHourlyStmt->bind_param("sssssssss",$envoyDate, $envoyDateEpoch,  $envoyDateTime , $envoyProductionPrevHour, $envoyConsumptionPrevHour,$envoyProductionDay, $envoyConsumptionDay, $envoyPMonth, $envoyCMonth ));
    {
        $insertEnvoyHourlyStmt->execute();
        $result = $insertEnvoyHourlyStmt->get_result();
        commitNow(__FUNCTION__);
    }
    $prevAction = $action;
    $action = "REP";
    fetchEnvoyHourlyData();
}
?>