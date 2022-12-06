<!DOCTYPE html>
<html>
<head>
	<title>Net Meter Detailed Report</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
	<style>
        table
        {
            border-collapse: collapse;
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
      <li class="active"><a href="report.php">Net Meter Units Report</a></li>
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
		<!--<input type="button" name="save" class="btn btn-primary" value="Submit" id="butsave"> -->
		<br></br>
		<div class="form-group" id="idResultTable"></div>
	</form>
</div>
<script>function setTwoNumberDecimal(el) {el.value = parseFloat(el.value).toFixed(2);};</script>
<script>
	function pullData()
	{
		$("#idResultTable").innerHTML = "";
		$("#idResultTable").hide();
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
			alert("From-Date should be prior to To-Date");
		}
		else
		{
			$.ajax({
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
			    var dataResult = JSON.parse(dataResult);
				$("#idResultTable").hide();
				$("#idResultTable").innerHTML = "";
			    if (dataResult.result == "OK")
			    {	
				    $("#idResultTable").show();
				    $('#idResultTable').html(dataResult.resultData);
			    }
			    else if (dataResult.result == "FATAL")
			    {
				    alert("Fatal error encountered");
			    }
			    $('#fupForm').find('input:text').val('');
			    if (dataResult.message != "")
				{
					$("#success").show();
			    	$('#success').html(dataResult.message);
				}
            }
	    });
		}
	}
</script>

<script>
$(document).ready(function()
{
    $("#idResultTable").hide();
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
</body>
<footer>
      &copy; Nagu
</footer>
</html>