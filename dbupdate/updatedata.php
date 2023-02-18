<?php

function updateReadingData ($readingDate,$importReading,$exportReading)
{
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate.$importReading.$exportReading;
    global $updateStmtImpExpByDate;

    if ($updateStmtImpExpByDate->bind_param("sss",$importReading,$exportReading,$readingDate))
    {
        $updateStmtImpExpByDate->execute();
        $result = $updateStmtImpExpByDate->get_result();
        commitNow();
    }
}

function updateNetMeterCalcData($readingDate,$currImport,$currExport,$currPerDay,$currImportYTD,$currExportYTD,$currYTD)
{
    global $netReadingUpdateStmt;
    global $traceMessage;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate.$currImport.$currExport.$currPerDay.$currImportYTD.$currExportYTD.$currYTD;
    if ($netReadingUpdateStmt->bind_param("sssssss",$currImport,$currExport,$currPerDay,$currImportYTD,$currExportYTD,$currYTD,$readingDate))
    {
        $netReadingUpdateStmt->execute();
        $result = $netReadingUpdateStmt->get_result();
        commitNow();
    }
}

function updateImpactedReadingData($readingDate,$importReading,$exportReading)
{
    global $traceMessage;
    global $currSelStmt;
    global $prevSelStmt;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate.$importReading.$exportReading;

    $nextDate = date("Y-m-d",(strtotime($readingDate) + 86400));
    while(true)
    {        
        if(readingExists($nextDate))
        {
            if($prevSelStmt->bind_param("s",$nextDate))
            {
                $prevSelStmt->execute();
                $result = $prevSelStmt->get_result();
                while ($row = $result->fetch_assoc())
                {
                    $prevImportValue = $row["ReadingImport"];
                    $prevExportValue = $row["ReadingExport"];
                }
            }

            if($currSelStmt->bind_param("s",$nextDate))
            {
                $currSelStmt->execute();
                $result = $currSelStmt->get_result();
                while ($row = $result->fetch_assoc())
                {
                    $currImportValue = $row["ReadingImport"];
                    $currExportValue = $row["ReadingExport"];
                }
            }
            
            if (($currImportValue > $prevImportValue) && ($currExportValue > $prevExportValue))
                break;
            else
                updateReadingData ($nextDate,$importReading,$exportReading);
            $nextDate = date("Y-m-d",(strtotime($nextDate) + 86400));
            //if (strtotime($nextDate) >= strtotime(date("Y-m-d")))
            //  break;
        }
        else
            break;
    }
}

function updateImpactedNetMeterCalcData($readingDate)
{
    global $traceMessage;
    global $currSelStmt;
    global $prevSelStmt;
    global $prevYTDUpdStmt;
    $traceMessage = $traceMessage.__FUNCTION__.$readingDate;    

    while(true)
    {
        $currImportValue=0;
        $currExportValue=0;
        $prevImportValue=0;
        $prevExportValue=0;
        $prevImportYTD=0;
        $prevExportYTD=0;
        $prevYTD=0;
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
        if($prevYTDUpdStmt->bind_param("s",$readingDate))
        {
           $prevYTDUpdStmt->execute();
           $result = $prevYTDUpdStmt->get_result();
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
        
        /*echo "----------------";
        echo $readingDate;
        echo $currImportValue;
        echo $currExportValue;
        echo $prevImportValue;
        echo $prevExportValue;
        echo $prevImportYTD;
        echo $prevExportYTD;
        echo $prevYTD;
        echo "----------------";*/

        updateNetMeterCalcData($readingDate,$currImport,$currExport,$currPerDay,$currImportYTD,$currExportYTD,$currYTD);
        $readingDate = date("Y-m-d",(strtotime($readingDate) + 86400));
        if (!readingExists($readingDate))
            break;
    }
}
?>