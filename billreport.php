<!DOCTYPE html>
<html>
<head>
	<title>Net Meter Monthly Bill Details</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
        table
        {
            border-collapse: collapse;
            table-layout: auto
        }
          
        th, td
        {
            text-align: left;
            padding: 8px;
            border-color: #96D4D4;
			column-width: auto;
        }
          
        tr:nth-child(even)
        {
            background-color: #D6EEEE;
        }
        table, th, td 
        {
            border: 1px solid black;
            border-collapse: collapse;
        }
        th, td
        {
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
      <li class="active"><a href="billreport.php">Net Meter Billing Report</a></li>
      <li><a href="billgraph.php">Net Meter Billing Graph</a></li>
      <li><a href="bill.php">Net Meter Monthly Bill</a></li>
    </ul>
  </div>
</nav>
<div style="margin: auto;width: 70%;">
	<div class="alert alert-success alert-dismissible" id="success" style="display:none;">
	  <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
	</div>
	<form id="idMeterForm" name="nameMeterForm" method="post">
		<br></br>
		<div class="form-group" id="idResultTable"></div>
	</form>
</div>
<script>
$(document).ready(function() {
//$("#idResultTable").hide();
$.ajax({
	url: "srvbill.php",
	type: "GET",
	data: 
    {
		action: "REP",
	},
	cache: false,
	success: function(dataResult)
    {
		var dataResult = JSON.parse(dataResult);
		if (dataResult.result == "OK")
		{	
			$("#idResultTable").show();
			$('#idResultTable').html(dataResult.resultData);
		}
		else if (dataResult.result == "FATAL")
		{
			alert("Fatal error encountered");
		}
		$('#success').html(dataResult.message);
		$('idtraceLabel').html(dataResult.trace);
	}
});
});
</script>
</body>
<footer>
<label>&copy; Nagu </label>
</footer>
</html>