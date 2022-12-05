const char MainPage[] PROGMEM = R"=====(
<!DOCTYPE html>
<html>
<head>
<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<meta content="utf-8" http-equiv="encoding">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
.slidecontainer
{
  width: 100%;
}

.slider
{
  -webkit-appearance: none;
  width: 50%;
  height: 25px;
  background: #d3d3d3;
  outline: none;
  opacity: 0.7;
  -webkit-transition: .2s;
  transition: opacity .2s;
}

.slider:hover
{
  opacity: 1;
}

.slider::-webkit-slider-thumb
{
  -webkit-appearance: none;
  appearance: none;
  width: 25px;
  height: 25px;
  background: #4CAF50;
  cursor: pointer;
}

.slider::-moz-range-thumb
{
  width: 25px;
  height: 25px;
  background: #4CAF50;
  cursor: pointer;
}

p.lucidaconsole 
{
  font-family: Lucida Console, Monospace;
}

/* The container */
.container {
  display: block;
  position: relative;
  padding-left: 35px;
  margin-bottom: 12px;
  cursor: pointer;
  font-size: 22px;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Hide the browser's default checkbox */
.container input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

/* Create a custom checkbox */
.checkmark {
  position: absolute;
  top: 0;
  left: 0;
  height: 25px;
  width: 25px;
  background-color: #eee;
}

/* On mouse-over, add a grey background color */
.container:hover input ~ .checkmark {
  background-color: #ccc;
}

/* When the checkbox is checked, add a blue background */
.container input:checked ~ .checkmark {
  background-color: #2196F3;
}

/* Create the checkmark/indicator (hidden when not checked) */
.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

/* Show the checkmark when checked */
.container input:checked ~ .checkmark:after {
  display: block;
}

/* Style the checkmark/indicator */
.container .checkmark:after {
  left: 9px;
  top: 5px;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 3px 3px 0;
  -webkit-transform: rotate(45deg);
  -ms-transform: rotate(45deg);
  transform: rotate(45deg);
}

.button {
  background-color: #4CAF50;
  border: none;
  color: white;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
}
</style>
</head>
<body onload="getData()">

<center><h1>Net Meter LCD Node</h1></center>
<div class="slidecontainer">
  <p class = "lucidaconsole">LCD Backlight Time: <span id="backlightTimeValue"></span></p>
  <input type="range" min="1" max="15" class="slider" id="backlightTimeRange" value="">

  <p class = "lucidaconsole">LCD Backlight
  <input type="checkbox" id="chkBoxBackLight" onClick="chkBackLight()"> </p>

  <!-- <p class = "lucidaconsole">URL: <span id="currURL"></span></p> -->
</div>


<script>
var backlightLabel  = document.getElementById("backlightTimeValue");
var backlightSlider = document.getElementById("backlightTimeRange");

var prevBacklightValue = "";
var prevBackLight = "";

<!-- var urlLabel = document.getElementById("currURL"); -->
backlightLabel.innerHTML = backlightSlider.value;

backlightSlider.oninput = function()
{
  backlightLabel.innerHTML = this.value;
  setBackLightTime(this.value);
}

backlightLabel.onchange = function()
{
  backlightLabel.innerHTML = this.value;
  setBackLightTime(this.value);
}

function chkBackLight()
{
  if(chkBoxBackLight.checked == true)
    setBackLight("1");
  else
    setBackLight("0");
}

function setBackLightTime(value)
{
  if(prevBacklightValue != value)
  {
    prevBacklightValue = value;
    var xhttp = new XMLHttpRequest();
    var url = "setBackLightTime?backlightTime="+value;
    xhttp.open("GET", url, true);
    xhttp.send(); 
  }
}

function setBackLight(value)
{
  if(prevBackLight != value)
  {
    prevBackLight = value;
    var xhttp = new XMLHttpRequest();
    var url = "setBackLight?backlight="+value;
    xhttp.open("GET", url, true);
    xhttp.send(); 
  }
}

function getData()
{
  getBackLightTime();
  getBackLight();
  getVersion();
}

function getBackLightTime()
{
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function()
  {
    if (this.readyState == 4 && this.status == 200)
    {
      document.getElementById("backlightTimeValue").innerHTML = this.responseText;
      document.getElementById("backlightTimeRange").value = this.responseText;
      document.getElementById("backlightTimeRange").defaultValue = this.responseText;
    }
  };
  xhttp.open("GET", "getBackLightTime", true);
  xhttp.send();  
}


function getBackLight()
{
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function()
  {
    if (this.readyState == 4 && this.status == 200)
    {
      if(this.responseText == "1")
        document.getElementById("chkBoxBackLight").checked = true;
      else
        document.getElementById("chkBoxBackLight").checked = false;    
    }
  };
  xhttp.open("GET", "getBackLight", true);
  xhttp.send();  
}

function getVersion()
{
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function()
  {
    if (this.readyState == 4 && this.status == 200)
    {
      document.getElementById("codeVersion").innerHTML = this.responseText;     
    }
  };
  xhttp.open("GET", "getVersion", true);
  xhttp.send();
}

</script>

<small><small><small>
  <p class = "lucidaconsole">Code Version : <span id="codeVersion"></span></p>
</small></small></small>
</body>
</html>
)=====";

const char ErrorPage[] PROGMEM = R"=====(
<!DOCTYPE html>
<html>
<body>
<center>
<h2>404 Error. Thats an error.</h2>
<h3>The requested page was not found on this server</h3>
</center>
</body>
</html>
)=====";