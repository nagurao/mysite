<?php

function getNetMeterReadings($readingDate)
{
    global $currSelStmt;
    global $currYTDSelStmt;
    global $prevBillSelStmt;
    global $echoResponse;
    global $responseArray;
    global $src;
    $currImportValue=0;
    $currExportValue=0;
    $netImportUnits = 0;
    $netExportUnits = 0;
    $netUnitsPerDay = 0;
    $netImportYTDUnits = 0;
    $netExportYTDUnits = 0;
    $netYTDUnits = 0;
    $billImportYTDUnits = 0;
    $billExportYTDUnits = 0;
    $currReadingTimeStamp="";
    if($currSelStmt->bind_param("s",$readingDate))
    {
        $currSelStmt->execute();
        $result = $currSelStmt->get_result();
        while ($row = $result->fetch_assoc())
        {
           $currImportValue = $row["ReadingImport"];
           $currExportValue = $row["ReadingExport"];
           $currReadingTimeStamp = $row["ReadingTimestamp"];
        }
    }
    if($currYTDSelStmt->bind_param("s",$readingDate))
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
    $prevBillSelStmt->execute();
    $result = $prevBillSelStmt->get_result();
    while ($row = $result->fetch_assoc())
    {
        $billImportYTDUnits = $row["BillImportReading"];
        $billExportYTDUnits = $row["BillExportReading"];
        $echoResponse["PrevBillImport"] = sprintf("%06.1f",$billImportYTDUnits);//$row["BillImportReading"];
        $echoResponse["PrevBillExport"] = sprintf("%06.1f",$billExportYTDUnits);
        $echoResponse["PrevBillDateImport"]= sprintf("%06.1f",$row["MeterImportReading"]);;
        $echoResponse["PrevBillDateExport"]= sprintf("%06.1f",$row["MeterExportReading"]);
    }

    if ($src == "ESP")
    {
        $echoResponse["Source"] = $src;
        //$echoResponse["ReadingDate"] = strtoupper(dateinDDMMMYYY($readingDate));
        //$echoResponse["ReadingDate"] = strtoupper(dateinDDMMMYYY($currReadingTimeStamp));
        $echoResponse["ReadingDate"] = strtoupper(date("dMY",strtotime(($currReadingTimeStamp))));
        $echoResponse["ReadingTimeHHMM"] = date("H:i",strtotime($currReadingTimeStamp));
        $echoResponse["ReadingTimeStamp"] = $currReadingTimeStamp;
        //$echoResponse["ReadingTime"] = date("H:i",strtotime("now"));
        $echoResponse["ReadingImport"] = sprintf("%06.1f",$currImportValue);//str_pad($currImportValue,6,'*',STR_PAD_LEFT);
        $echoResponse["ReadingExport"] = sprintf("%06.1f",$currExportValue);//str_pad($currExportValue,6,'*',STR_PAD_LEFT);
        $echoResponse["NetImportUnits"] = sprintf("%04.1f",$netImportUnits);//str_pad($netImportUnits,4,'*',STR_PAD_LEFT);
        $echoResponse["NetExportUnits"] = sprintf("%04.1f",$netExportUnits);//str_pad($netExportUnits,4,'*',STR_PAD_LEFT);
        $echoResponse["NetUnitsPerDay"] = sprintf("%04.1f",$netUnitsPerDay);//$netUnitsPerDay;
        $echoResponse["NetImportYTDUnits"] = sprintf("%06.1f",$netImportYTDUnits);//$netImportYTDUnits;
        $echoResponse["NetExportYTDUnits"] = sprintf("%06.1f",$netExportYTDUnits);//$netExportYTDUnits;
        $echoResponse["NetYTDUnits"] = sprintf("%06.1f",$netYTDUnits);//$netYTDUnits;
        $echoResponse["BillYTDImportUnits"] = sprintf("%06.1f",($currImportValue - $billImportYTDUnits));
        $echoResponse["BillYTDExportUnits"] = sprintf("%06.1f",($currExportValue - $billExportYTDUnits));
    }
    else
    {
        $echoResponse["ReadingDate"] = dateinDDMMMYYY($readingDate);
        $echoResponse["ReadingTimeHHMM"] = date("H:i",strtotime($currReadingTimeStamp));
        $echoResponse["ReadingTimeStamp"] = $currReadingTimeStamp;
        $echoResponse["ReadingImport"] = $currImportValue;
        $echoResponse["ReadingExport"] = $currExportValue;
        $echoResponse["NetImportUnits"] = $netImportUnits;
        $echoResponse["NetExportUnits"] = $netExportUnits;
        $echoResponse["NetUnitsPerDay"] = $netUnitsPerDay;
        $echoResponse["NetImportYTDUnits"] = $netImportYTDUnits;
        $echoResponse["NetExportYTDUnits"] = $netExportYTDUnits;
        $echoResponse["NetYTDUnits"] = $netYTDUnits;
    }
    $echoResponse["result"] = "OK";
    $echoResponse["message"] = $responseArray["3"];
}

?>