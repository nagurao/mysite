<!DOCTYPE html>
<html>
<head>
	<title>Net Meter Bill Details</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
      <li class="active"><a href="netmeter.html">Home</a></li>
      <li><a href="reading.php">Net Meter Daily Readings</a></li>
      <li><a href="report.php">Net Meter Units Report</a></li>
      <li><a href="billreport.php">Net Meter Billing Report</a></li>
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
			<label for="bill-date">Bill Date:</label>
			<input type="date" class="form-control" id="idBillDate" name="nameBillDate">
            <script>$('#idBillDate').val(new Date().toJSON().slice(0,10));</script>
		</div>
		<div class="form-group">
			<label for="billImport">Bill Import Reading:</label>
			<input type="text" class="form-control" id="idBillImport" placeholder="0.00" name="nameBillImport" pattern="[0-9]+([\.][0-9]+)?" step="0.01" min="0" onchange="setTwoNumberDecimal(this)">
		</div>
		<div class="form-group">
			<label for="billExport">Bill Export Reading:</label>
			<input type="text" class="form-control" id="idBillExport" placeholder="0.00" name="nameBillExport" pattern="[0-9]+([\.][0-9]+)?" step="0.01" min="0" onchange="setTwoNumberDecimal(this)">
		</div>
		<div class="form-group">
			<label for="meterImport">Meter Import Reading:</label>
			<input type="text" class="form-control" id="idMeterImport" placeholder="0.00" name="nameMeterImport" pattern="[0-9]+([\.][0-9]+)?" step="0.01" min="0" onchange="setTwoNumberDecimal(this)">
		</div>
		<div class="form-group">
			<label for="meterExport">Meter Export Reading:</label>
			<input type="text" class="form-control" id="idMeterExport" placeholder="0.00" name="nameMeterExport" pattern="[0-9]+([\.][0-9]+)?" step="0.01" min="0" onchange="setTwoNumberDecimal(this)">
		</div>        
		<input type="button" name="save" class="btn btn-primary" value="Submit" id="butsave">
		<br></br>
		<div class="form-group" id="idResultTable"></div>
	</form>
</div>
<script>function setTwoNumberDecimal(el) {el.value = parseFloat(el.value).toFixed(2);};</script>
<script>
$(document).ready(function() {
$('#butsave').on('click', function() {
$("#butsave").attr("disabled", "disabled");
$("#idResultTable").hide();
var billDate = $('#idBillDate').val();
var billImport = $('#idBillImport').val();
var billExport = $('#idBillExport').val();
var meterImport = $('#idMeterImport').val();
var meterExport = $('#idMeterExport').val();
var nanFlag = false;
var scriptVersion = "1.0";
if (isNaN(billImport) || isNaN(billExport) || isNaN(meterImport) || isNaN(meterExport) )
    nanFlag = true;

if(billDate!="" && billImport!="" && billExport!="" && meterImport!="" && meterExport!="" && !nanFlag)
{
	$.ajax({
		url: "srvnetmeterbill.php",
		type: "POST",
		data: 
        {
			scriptVersion: scriptVersion,
			action: "INS",
            billDate: billDate,
			billImport: billImport,
			billExport: billExport,
            meterImport: meterImport,
            meterExport: meterExport
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

			//if(dataResult.trace != "")
			//	alert(dataResult.trace);
			$('#fupForm').find('input:text').val('');
			$("#success").show();
			$('#success').html(dataResult.message);
			$('idtraceLabel').html(dataResult.trace);
			/*if (dataResult=="0")
            {
                alert("Error saving Net Meter Data");
            }
            else
            {
                //$("#butsave").removeAttr("disabled");
				$('#fupForm').find('input:text').val('');
				$("#success").show();
				$('#success').html('Data added successfully !'); 
				$('#tb').html(dataResult);
                alert(dataResult);						
			}*/
		}
	});
	}
	else{
		alert("Please enter valid data !");
        if (!nanFlag)
            $("#butsave").removeAttr("disabled");
	}
});
});
</script>
</body>
<footer>
      &copy; Nagu
</footer>
</html>