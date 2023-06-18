/*
const char MainPage2[] PROGMEM = R"=====(
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="refresh" content="310"/>
<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<meta content="utf-8" http-equiv="encoding">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style></style>
</head>
<body onload="getData()">
<center><h1>Envoy Real Time Display</h1></center>
<div class="slidecontainer">
</div>
<script type="text/javascript">
    window.onload = function ()
    {
        getData();
    }
</script>
<script>
function getData()
{
    getProdData();
    getConsData();
    getNetData();
}
function getProdData()
{
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function()
    {
        if (this.readyState == 4 && this.status == 200)
        {
            document.getElementById("idLabelProd").innerHTML = this.responseText; 
        }
    };
    xhttp.open("GET", "getProdData", true);
    xhttp.send();
}

function getConsData()
{
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function()
    {
        if (this.readyState == 4 && this.status == 200)
        {
            document.getElementById("idLabelCons").innerHTML = this.responseText; 
        }
    };
    xhttp.open("GET", "getConsData", true);
    xhttp.send();
}
function getNetData()
{
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function()
    {
        if (this.readyState == 4 && this.status == 200)
        {
            document.getElementById("idLabelNet").innerHTML = this.responseText; 
        }
    };
    xhttp.open("GET", "getNetData", true);
    xhttp.send();
}

</script>
<div class="form-group">
    <table id="tableStats">
        <tr>
            <td style="text-align: left;"><label>Production</label></td>
            <td style="text-align: left;"><label>:</label></td>
            <td style="text-align: right;"><label id="idLabelProd"></label><label> (W)</label></td>
        </tr>
        <tr>
            <td style="text-align: left;"><label>Consumption</label></td>
            <td style="text-align: left;"><label>:</label></td>
            <td style="text-align: right;"><label id="idLabelCons"></label><label> (W)</label></td>
        </tr> 
        <tr>
            <td style="text-align: left;"><label id="idLabelImpExp"></label></td>
            <td style="text-align: left;"><label>:</label></td>
            <td style="text-align: right;"><label id="idLabelNet"></label><label> (W)</label></td>
        </tr>       
    </table>
</div>        
</body>
</html>
)=====";
*/