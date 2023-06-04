<?php
$envoyURL = "http://envoy.local/production.json";
$envoyData = json_decode(file_get_contents($envoyURL),true);
$envoyResponse = file_get_contents($envoyURL);
echo $envoyResponse;

?>