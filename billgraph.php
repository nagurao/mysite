<!DOCTYPE html>
<html>
    <head>
        <title>Net Meter Monthly Bill Details Graph</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
        <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <style>
            table {
                border-collapse: collapse;
                table-layout: auto;
            }

            th,
            td {
                text-align: left;
                padding: 8px;
                border-color: #96d4d4;
                column-width: auto;
            }

            tr:nth-child(even) {
                background-color: #d6eeee;
            }
            table,
            th,
            td {
                border: 1px solid black;
                border-collapse: collapse;
            }
            th,
            td {
                text-align: center;
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-inverse">
            <div class="container-fluid">
                <div class="navbar-header">
                    <a class="navbar-brand" href="netmeter.html">BHR's Net Meter Details</a>
                </div>
                <ul class="nav navbar-nav">
                    <li><a href="netmeter.html">Home</a></li>
                    <li><a href="reading.php">Net Meter Daily Readings</a></li>
                    <li><a href="report.php">Net Meter Units Report</a></li>
                    <li><a href="billreport.php">Net Meter Billing Report</a></li>
                    <li class="active"><a href="billgraph.php">Net Meter Billing Graph</a></li>
                    <li><a href="bill.php">Net Meter Monthly Bill</a></li>
                </ul>
            </div>
        </nav>
        <div style="margin: auto; width: 70%;">
            <div class="alert alert-success alert-dismissible" id="success" style="display: none;">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
            </div>
            <form id="idMeterForm" name="nameMeterForm" method="post">
                <br />
                <div class="form-group" id="idResultTable"></div>
            </form>
        </div>
        <div style="margin: auto; width: 100%;">
            <canvas id="myChart" style="width: 100%; max-width: 1000px;"></canvas>
        </div>
        <script>
            var maxReadings = 24;
            $(document).ready(function () {
                //$("#idResultTable").hide();
                $.ajax({
                    url: "srvbill.php",
                    type: "GET",
                    data: {
                        action: "GRAPH",
                        maxReadings: maxReadings,
                    },
                    cache: false,
                    success: function (dataResult) {
                        var dataResult = JSON.parse(dataResult);
                        var importData = dataResult.importData;
                        var exportData = dataResult.exportData;
                        var exportBillData = dataResult.exportBillData;
                        var dataPointsData = [];
                        var dataPointsImport = [];
                        var dataPointsExport = [];
                        var dataPointsExportBill = [];
                        var i = 0;
                        for (var key in importData) {
                            dataPointsData[i] = importData[key].date;
                            dataPointsImport[i] = numeral(importData[key].value).format("0000.00");
                            dataPointsExport[i] = numeral(exportData[key].value).format("0000.00");
                            dataPointsExportBill[i] = numeral(exportBillData[key].value).format("0000.00");
                            i++;
                        }
                        new Chart("myChart", {
                            type: "line",
                            data: {
                                labels: dataPointsData,
                                datasets: [
                                    {
                                        label: "Imported Units (kW)",
                                        data: dataPointsImport,
                                        borderColor: "red",
                                        fill: true,
                                    },
                                    {
                                        label: "Exported Units (kW)",
                                        data: dataPointsExport,
                                        borderColor: "green",
                                        fill: true,
                                    },
                                    {
                                        label: "Exported Units Billed (kW)",
                                        data: dataPointsExportBill,
                                        borderColor: "blue",
                                        fill: true,
                                    },
                                ],
                            },
                            options: {
                                legend: { display: true },
                                //plugins: { title: {  text: monthName, display: true}}
                            },
                        });
                    },
                });
            });
        </script>
    </body>
    <footer>
        <label>&copy; Nagu </label>
    </footer>
</html>