// Simulated energy data values
const productionData = 100; // Simulated production data
const consumptionData = 80; // Simulated consumption data

// Calculate net energy data
const netEnergyData = productionData - consumptionData;

// Update the HTML elements with the energy data values
document.getElementById("production-value").innerText = productionData + " kWh";
document.getElementById("consumption-value").innerText = consumptionData + " kWh";
document.getElementById("net-energy-value").innerText = netEnergyData + " kWh";
