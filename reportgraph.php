<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>Net Meter Detailed Graph</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
		<script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
		<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
		<script type="text/javascript">
			window.onload = function()
			{
				pullData();
			}
		</script>
		<script>function setTwoNumberDecimal(el) {el.value = parseFloat(el.value).toFixed(2);};</script>
		<script type="text/javascript">
			var  getDateString = function(date, format)
			{
				var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
				getPaddedComp = function(comp)
				{
					return ((parseInt(comp) < 10) ? ('0' + comp) : comp)
				},
				formattedDate = format,
				opt = {
					"y+": date.getFullYear(), // year
					"M+": months[date.getMonth()], //month
					"d+": getPaddedComp(date.getDate()), //day
					"h+": getPaddedComp((date.getHours() > 12) ? date.getHours() % 12 : date.getHours()), //hour
					"H+": getPaddedComp(date.getHours()), //hour
					"m+": getPaddedComp(date.getMinutes()), //minute
					"s+": getPaddedComp(date.getSeconds()), //second
					"S+": getPaddedComp(date.getMilliseconds()), //millisecond,
					"b+": (date.getHours() >= 12) ? 'PM' : 'AM'
				};

				for (var key in opt)
				{
					if (new RegExp("(" + key + ")").test(format))
					{
						formattedDate = formattedDate.replace(RegExp.$1, opt[key]);
					}
				}
				return formattedDate;
			};
		</script>

		<script type="text/javascript">
			function pullData()
			{
				$("#success").innerHTML = "";
				$("#success").hide();
				$("#idLabelReportPeriod").hide();
				var reportOrder = "DESC";
				var reportType = "FIX";
				if(document.getElementById('idRollReport').checked)
					reportType = "ROL";
				if(document.getElementById('idAscOrder').checked)
					reportOrder = "ASC";
				
				if(reportType == "ROL")
				{
					reportOrder = "ASC";
					document.getElementById('idAscOrder').checked = true;
				}
				var scriptVersion = "1.0";
				var fromDate = $('#idFromDate').val();
				var toDate = $('#idToDate').val();
				if (fromDate > toDate)
				{
					$("#success").innerHTML = "";
					$("#idLabelReportPeriod").hide();
					alert("From-Date should be prior to To-Date");
					return;
				}
				else
				{
					$.ajax(
						{
							url: "srvreport.php",
							type: "GET",
							data: 
							{
								scriptVersion: scriptVersion,
								reportType: reportType,
								reportOrder: reportOrder,
								fromDate: fromDate,
								toDate: toDate
							},
							cache: false,
							success: function(dataResult)
							{	
								var reportHeader = "Net Import Export from  " + getDateString(new Date(fromDate),"d-M-y") + " to " + getDateString(new Date(toDate),"d-M-y");
								$("#idLabelReportPeriod").text(reportHeader);
								$("#success").innerHTML = "";
								if (dataResult.result == "OK")
								{	
									$("#idLabelReportPeriod").show();
									var dataResult = JSON.parse(dataResult);
									var importData = dataResult.importData;
									var exportData = dataResult.exportData;
									var envoyProd = dataResult.envoyProd;
									var envoyCons = dataResult.envoyCons;
									var dataPointsData = [];
									var dataPointsImport = [];
									var dataPointsExport = [];
									var dataPointsNet = [];
									var dataPointsEnvoyProd = [];
									var dataPointsEnvoyCons = [];
									var dataPointsNetEnvoy = [];

									var i = 0;
									var numEntries = -1;
									var avgImport = 0;
									var avgExport = 0;
									var avgNet = 0;
									var avgGeneration = 0;
									var avgConsumption = 0;
									for (var key in importData)
									{
										dataPointsData[i] = importData[key].date;
										dataPointsImport[i] = numeral(importData[key].value).format('00.00');
										dataPointsExport[i] = numeral(exportData[key].value).format('00.00');
										dataPointsNet[i] = numeral(importData[key].value - exportData[key].value).format('00.00');
										dataPointsEnvoyProd[i] = numeral(envoyProd[key].value).format('00.00');
										dataPointsEnvoyCons[i] = numeral(envoyCons[key].value).format('00.00');
										dataPointsNetEnvoy[i] = numeral(envoyCons[key].value - envoyProd[key].value).format('00.00');

										if (numeral(importData[key].value).format('00.00') > 0 )
										{
											numEntries = numEntries + 1;
											if(i != 0)
											{
												avgImport = avgImport + parseFloat(numeral(importData[key].value).format('00.00'));
												avgExport = avgExport + parseFloat(numeral(exportData[key].value).format('00.00'));
												avgGeneration = avgGeneration + parseFloat(numeral(envoyProd[key].value).format('00.00'));
												avgConsumption = avgConsumption + parseFloat(numeral(envoyCons[key].value).format('00.00'));
											}
										}
										i++; 
									}
								
									new Chart("myChart",
									{
										type: "line",
										data: {
											labels: dataPointsData,
											datasets: [{
												label:"Imported Units (kWh)",
												data: dataPointsImport,
												borderColor: "red",
												fill: true
											},
											{
												label:"Exported Units (kWh)",
												data: dataPointsExport,
												borderColor: "green",
												fill: true
											},
											{
												label:"Net Units (kWh)",
												data: dataPointsNet,
												borderColor: "blue",
												fill: true  
											},
											{
												label:"Solar Generated Units (kWh)",
												data: dataPointsEnvoyProd,
												borderColor: "orange",
												fill: true  
											},
											{
												label:"Consumed Units (kWh)",
												data: dataPointsEnvoyCons,
												borderColor: "purple",
												fill: true  
											},
											{
												label:"Net Units (Enphase) (kWh)",
												data: dataPointsNetEnvoy,
												borderColor: "black",
												fill: true  
											}  
											
											]},
											options: { legend: {display: true},
											plugins: { title: {  text: monthName, display: true}}
										}
										
									});
									$('#success').html(dataResult.message);
								}
								else if (dataResult.result == "FATAL")
								{
									alert("Fatal error encountered");
								}
								if (dataResult.message != "")
								{
									//alert(dataResult.message);
									$("#success").show();
									$('#success').html(dataResult.message);
								}
							}
						});
				}
			}			
		</script>
	</head>
	<body>
		<nav class="navbar navbar-inverse">
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="netmeter.html">BHR's Net Meter Details</a>
				</div>
				<ul class="nav navbar-nav">
					<li class="active"><a href="netmeter.html">Home</a></li>
					<li><a href="reading.php">Net Meter Daily Readings</a></li>
					<li><a href="report.php">Net Meter Units Report</a></li>
					<li><a href="billreport.php">Net Meter Billing Report</a></li>
					<li><a href="billgraph.php">Net Meter Billing Graph</a></li>
					<li><a href="bill.php">Net Meter Monthly Bill</a></li>
				</ul>
			</div>
		</nav>
		<div style="margin: auto;width: 60%;">
			<div class="alert alert-success alert-dismissible" id="success" style="display:none;">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
			</div>
			<form id="idMeterForm" name="nameMeterForm" method="post">
				<div class="form-group">
					<label for="from-date">From Date:</label>
					<input type="date" class="form-control" id="idFromDate" name="nameFromDate" onChange="pullData()">
					<script>$('#idFromDate').val(new Date().toJSON().slice(0,10));</script>
				</div>
				<div class="form-group">
					<label for="to-date">To Date:</label>
					<input type="date" class="form-control" id="idToDate" name="nameToDate" onChange="pullData()">
					<script>$('#idToDate').val(new Date().toJSON().slice(0,10));</script>
				</div>        	
				<div class="form-group">
					<label for="fixedRep">Fixed Report:</label>
					<input type="radio" id="idFixedReport" name="nameReport" value="FIX" checked="checked">
					<label for="rollRep">Rolling Report:</label>
					<input type="radio" id="idRollReport" name="nameReport" value="ROL">
				</div>
				<div class="form-group">
					<label for="ascOrder">Ascending Date:</label>
					<input type="radio" id="idAscOrder" name="nameReportOrder" value="ASC">
					<label for="descOrder">Descending Date:</label>
					<input type="radio" id="idDescOrder" name="nameReportOrder" value="DESC" checked="checked">
				</div>		
				<br></br>
			</form>
		</div>
		
		<div class="form-group">
			<center><label for="labelReportPeriod" id="idLabelReportPeriod" name="nameLabelReportPeriod"></label></center>
		</div>

		<div class="form-group">
			<table id="tableStats">
				<tr>
					<td style="text-align:center" colspan="3"><label>Net Meter Stats for selected period</label></td>
				</tr>
				<tr><td style="text-align:center" colspan="3"><label></label></td></tr>
				<tr>
					<td style="text-align:left"><label>Imported Units</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelImportedUnitsTable"></label><label> (kWh)</label></td>
				</tr>  
				<tr>
					<td style="text-align:left"><label>Exported Units</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelExportedUnitsTable"></label><label> (kWh)</label></td>
				</tr> 
				<tr>
					<td style="text-align:left"><label>Net Units</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelNetUnitsTable"></label><label> (kWh)</label></td>
				</tr>  
				<tr>
					<td style="text-align:left"><label>Consumed Units (Enphase)</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelConsumedUnitsTable"></label><label> (kWh)</label></td>
				</tr> 
				<tr>
					<td style="text-align:left"><label>Generated Units (Enphase)</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelGeneratedUnitsTable"></label><label> (kWh)</label></td>
				</tr> 
				<tr>
					<td style="text-align:left"><label>Net Units (Enphase)</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelImpExpUnitsTable"></label><label> (kWh)</label></td>
				</tr> 
				<tr>
					<td style="text-align:left"><label>Delta Net Units (Net Meter & Enphase)</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelDeltaNetUnitsTable"></label><label> (kWh)</label></td>
				</tr>                                         
					
				<tr>
					<td style="text-align:left"><label>Average Units Imported</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelAvgImpTable"></label><label> (kWh)</label></td>
				</tr>
				<tr>
					<td style="text-align:left"><label>Average Units Exported</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelAvgExpTable"></label><label> (kWh)</label></td>
				</tr>
				<tr>
					<td style="text-align:left"><label>Average Net Units</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelAvgNetTable"></label><label> (kWh)</label></td>
				</tr>   
				<tr>
					<td style="text-align:left"><label>Average Daily Solar Generation (Enphase)</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelAvgSolGenTable"></label><label> (kWh)</label></td>
				</tr>  
				<tr>
					<td style="text-align:left"><label>Average Daily Consumption (Enphase)</label></td>
					<td style="text-align:left"><label>:</label></td>
					<td style="text-align:right"><label id="idLabelDailyConsTable"></label><label> (kWh)</label></td>
				</tr>
			</table>
		</div>
	
	<script>
	$(document).ready(function()
	{
		$('input[type=radio][name=nameReport]').change(function()
		{
			pullData();
		});
		
		$('input[type=radio][name=nameReportOrder]').change(function()
		{
			pullData();
		});
		$('input[type=date][name=nameFromDate]').change(function()
		{
			pullData();
		});
		
		$('input[type=radio][name=nameToDate]').change(function()
		{	
			pullData();
		});
		
	});
	</script>
	
	<div class="form-group">
	<center><label for="labelMMMYYYY" id="idLabelMMMYYYY" name="nameLabelMMMYYYY"></label></center>
	</div>
	
	<div style="margin: auto;width: 100%;"></div>
	<canvas id="myChart" style="width:100%;max-width:1000px"></canvas>
	</div>
	
	</body>
	<footer>
	<label>&copy; Nagu </label>
	</footer>
	</html>
	