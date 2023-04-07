<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="refresh" content="310"/>
        <title>BHR's Net Meter Daily Envoy Usage Report</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
        <script type="text/javascript">function autoRefresh( t ) {setTimeout("location.reload(true);", t);}</script>
        <script type="text/javascript">
            window.onload = function () {
                var scriptVersion = "1.0";
                var reportSrc = "WEB";
                $("#idLabelProcessTimeText").hide();
                $("#idLabelProcessTime").hide();
                $.ajax({
                    url: "srv/srvusagegraph.php",
                    type: "GET",
                    data: {
                        scriptVersion: scriptVersion,
                        reportSrc: reportSrc,
                    },
                    cache: false,
                    success: function (dataResult) {
                        var dataResult = JSON.parse(dataResult);
                        var consData = dataResult.ConsData;
                        var prodData = dataResult.ProdData;
                        var netData = dataResult.NetData;
                        var reportDate = dataResult.EnvoyDate;
                        var currDayProd = dataResult.Production;
                        var currDayCons = dataResult.Consumption;
                        var dataPointsData = [];
                        var dataPointsCons = [];
                        var dataPointsProd = [];
                        var dataPointsNet = [];
                        var i = 0;
                        var avgCons = 0;
                        var avgProd = 0;
                        var avgNet = 0;
                        var numEntries = 0;
                        for (var key in consData) {
                            numEntries++;
                            //if(parseInt(consData[key].time.substring(3,5)) % 30 == 0)
                            if( (parseInt(consData[key].time.substring(3,5))>= 29 && parseInt(consData[key].time.substring(3,5))<= 31) || 
                                (parseInt(consData[key].time.substring(3,5))>= 59 ) ||
                                (parseInt(consData[key].time.substring(3,5))<= 01)) 
                            {
                                dataPointsData[i] = consData[key].time.substring(0,5);
                                dataPointsCons[i] = numeral(consData[key].value).format("00.00");
                                dataPointsProd[i] = numeral(prodData[key].value).format("00.00");
                                dataPointsNet[i] = numeral(netData[key].value).format("00.00");
                                i++;
                            }
                            avgCons = avgCons + (numeral(parseFloat(consData[key].value)).format("00.00") * 1.00);
                            avgProd = avgProd + (numeral(parseFloat(prodData[key].value)).format("00.00") * 1.00);
                            avgNet = avgNet + (numeral(parseFloat(netData[key].value)).format("00.00") * 1.00);
                        }
                        avgCons = avgCons/numEntries;
                        avgProd = avgProd/numEntries;
                        avgNet = avgNet/numEntries;
                        avgCons = parseFloat(numeral(avgCons).format("00.00"));
                        avgProd = parseFloat(numeral(avgProd).format("00.00"));
                        avgNet = parseFloat(numeral(avgNet).format("00.00"));
                        $("#idLabelDDMMMYYYY").text("Hourly Usage for " + reportDate);
                        $("#idLabelAvgCons").text(numeral(avgCons).format("00.00"));
                        $("#idLabelAvgProd").text(numeral(avgProd).format("00.00"));
                        $("#idLabelAvgNet").text(numeral(avgNet).format("00.00"));
                        $("#idLabelProd").text(numeral(currDayProd).format("00.00"));
                        $("#idLabelCons").text(numeral(currDayCons).format("00.00"));
                        $("#idLabelProcessTimeText").show();
                        $("#idLabelProcessTime").show();
                        document.getElementById("idLabelProcessTimeText").innerHTML = "Process Time: ";
                        document.getElementById("idLabelProcessTime").innerHTML = dataResult.processTime;
                        new Chart("myChart", {
                            type: "line",
                            data: {
                                labels: dataPointsData,
                                datasets: [
                                    {
                                        label: "Consumption (W)",
                                        data: dataPointsCons,
                                        borderColor: "red",
                                        fill: true,
                                    },
                                    {
                                        label: "Production (W)",
                                        data: dataPointsProd,
                                        borderColor: "green",
                                        fill: true,
                                    },
                                    {
                                        label: "Net (W)",
                                        data: dataPointsNet,
                                        borderColor: "blue",
                                        fill: true,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                interaction: { intersect: true, mode: "index", axis: "y" },
                                legend: { display: true },
                                tooltips: { mode: "index", intersect: true },
                            },
                        });
                    },
                });
            };
        </script>
    </head>
    <body><!--</body> onload = "JavaScript:autoRefresh(900000);">-->
        <nav class="navbar navbar-inverse">
            <div class="container-fluid">
                <div class="navbar-header">
                    <a class="navbar-brand" href="netmeter.html">BHR's Net Meter Details</a>
                </div>
                <ul class="nav navbar-nav">
                    <li><a href="netmeter.html">Home</a></li>
                    <li><a href="reading.php">Net Meter Daily Readings</a></li>
                    <li><a href="webreport.php">Net Meter Units Report</a></li>
                    <li><a href="webbillreport.php">Net Meter Billing Report</a></li>
                    <li><a href="webbillgraph.php">Net Meter Billing Graph</a></li>
                    <li><a href="webbill.php">Net Meter Monthly Bill</a></li>
                    <li><a href="webrephourly.php">Hourly Usage</a></li>
                    <li class="active"><a href="webrepusage.php">Current Day Usage</a></li>
                </ul>
            </div>
        </nav>
        <div class="form-group"></div>

        <div class="form-group">
            <center><label for="labelDDMMMYYYY" id="idLabelDDMMMYYYY" name="nameLabelDDMMMYYYY"></label></center>
        </div>

        <div style="margin: auto; width: 100%;">
            <canvas id="myChart" style="width: 100%; max-width: 1000px;"></canvas>
        </div>

        <div class="form-group">
            <table id="tableStats">
                <tr>
                    <td style="text-align: left;"><label>Average Production</label></td>
                    <td style="text-align: left;"><label>:</label></td>
                    <td style="text-align: right;"><label id="idLabelAvgProd"></label><label> (W)</label></td>
                </tr>
                <tr>
                    <td style="text-align: left;"><label>Average Consumption</label></td>
                    <td style="text-align: left;"><label>:</label></td>
                    <td style="text-align: right;"><label id="idLabelAvgCons"></label><label> (W)</label></td>
                </tr>
                <tr>
                    <td style="text-align: left;"><label>Average Net</label></td>
                    <td style="text-align: left;"><label>:</label></td>
                    <td style="text-align: right;"><label id="idLabelAvgNet"></label><label> (W)</label></td>
                </tr>   
                <tr>
                    <td style="text-align: left;"><label>Production</label></td>
                    <td style="text-align: left;"><label>:</label></td>
                    <td style="text-align: right;"><label id="idLabelProd"></label><label> (kWh)</label></td>
                </tr>
                <tr>
                    <td style="text-align: left;"><label>Consumption</label></td>
                    <td style="text-align: left;"><label>:</label></td>
                    <td style="text-align: right;"><label id="idLabelCons"></label><label> (kWh)</label></td>
                </tr>                                                             
            </table>
        </div>
    </body>
    <footer>
        <label>&copy; Nagu </label>
        <br><small style="font-size:xx-small" ><label id="idLabelProcessTimeText"></label> <label id="idLabelProcessTime"></label> </small>
    </footer>
</html>