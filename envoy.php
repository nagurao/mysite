<!DOCTYPE html>
<html>
    <head>
        <title>Envoy Production & Consumption Readings</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <style>
            table {
                border-collapse: collapse;
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
                    <li class="active"><a href="reading.php">Net Meter Daily Readings</a></li>
                    <li><a href="report.php">Net Meter Units Report</a></li>
                    <li><a href="billreport.php">Net Meter Billing Report</a></li>
                    <li><a href="billgraph.php">Net Meter Billing Graph</a></li>
                    <li><a href="bill.php">Net Meter Monthly Bill</a></li>
                </ul>
            </div>
        </nav>
        <div style="margin: auto; width: 60%;">
            <div class="alert alert-success alert-dismissible" id="success" style="display: none;">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
            </div>
            <form id="idMeterForm" name="nameMeterForm" method="post">
                <div class="form-group">
                    <label for="reading-date">Reading Date:</label>
                    <input type="date" class="form-control" id="idReadingDate" name="nameReadingDate" />
                    <script>
                        $("#idReadingDate").val(new Date().toJSON().slice(0, 10));
                    </script>
                </div>
                <div class="form-group">
                    <label for="prod">Production Reading:</label>
                    <input type="text" class="form-control" id="idProdReading" placeholder="0.00" name="nameProdReading" pattern="[0-9]+([\.][0-9]+)?" step="0.01" min="0" />
                </div>
                <div class="form-group">
                    <label for="cons">Consumption Reading:</label>
                    <input type="text" class="form-control" id="idConsReading" placeholder="0.00" name="nameConsReading" pattern="[0-9]+([\.][0-9]+)?" step="0.01" min="0" />
                </div>
                <input type="button" name="save" class="btn btn-primary" value="Submit" id="butsave" />
                <br />
                <div class="form-group" id="idResultTable"></div>
            </form>
        </div>
        <script>
            function setTwoNumberDecimal(el) {
                el.value = parseFloat(el.value).toFixed(2);
            }
        </script>
        <script>
            $(document).ready(function () {
                $("#butsave").on("click", function () {
                    $("#butsave").attr("disabled", "disabled");
                    $("#idResultTable").hide();
                    var readingDate = $("#idReadingDate").val();
                    var productionReading = $("#idProdReading").val();
                    var consumptionReading = $("#idConsReading").val();
                    var nanFlag = false;
                    var scriptVersion = "1.0";
                    var source = "WEB";
                    if (isNaN(productionReading) || isNaN(consumptionReading)) nanFlag = true;

                    if (readingDate != "" && productionReading != "" && consumptionReading != "" && !nanFlag) {
                        $.ajax({
                            url: "srvenvoy.php",
                            type: "POST",
                            data: {
                                scriptVersion: scriptVersion,
                                action: "INS",
                                readingDate: readingDate,
                                productionReading: productionReading,
                                consumptionReading: consumptionReading,
                                source: source,
                            },
                            cache: false,
                            success: function (dataResult) {
                                var dataResult = JSON.parse(dataResult);
                                if (dataResult.result == "OK") {
                                    $("#idResultTable").show();
                                    $("#idResultTable").html(dataResult.resultData);
                                } else if (dataResult.result == "FATAL") {
                                    alert("Fatal error encountered");
                                }

                                //if(dataResult.trace != "")
                                //	alert(dataResult.trace);
                                $("#fupForm").find("input:text").val("");
                                $("#success").show();
                                $("#success").html(dataResult.message);
                                $("idtraceLabel").html(dataResult.trace);
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
                            },
                        });
                    } else {
                        alert("Please enter valid data !");
                        if (!nanFlag) $("#butsave").removeAttr("disabled");
                    }
                });
            });
        </script>
    </body>
    <footer>
        &copy; Nagu
    </footer>
</html>