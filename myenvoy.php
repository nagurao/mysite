<?php

$envoyURL = "http://envoy.local/production.json";
$envoyData = json_decode(file_get_contents($envoyURL));

echo ("Current Consumption is ".round($envoyData->consumption[0]->wNow,2)."W");
echo ("Net Consumption is ".round($envoyData->consumption[1]->wNow,2)."W");
echo ("Current Production is ".(round($envoyData->production[1]->wNow/1000,2))."kWh");
echo ("Consumed ".(round($envoyData->consumption[0]->whToday/1000,2))."kWh");
echo ("Produced ".(round($envoyData->production[1]->whToday/1000,2))."kWh");

?>