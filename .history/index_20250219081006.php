<?php include 'db_co.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Crime Prediction System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <h1>Crime Prediction System</h1>
    
    <form id="crimeForm">
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" required>

        <label for="crime_type">Crime Type:</label>
        <select id="crime_type" name="crime_type">
            <option value="theft">Theft</option>
            <option value="assault">Assault</option>
            <option value="fraud">Fraud</option>
        </select>

        <button type="submit">Predict Crime Risk</button>
    </form>

    <p id="predictionResult"></p>

    <script src="assets/script.js"></script>
    <script>document.getElementById("crimeForm").addEventListener("submit", function(event) {
    event.preventDefault();
    
    let formData = new FormData(this);

    fetch("predict.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById("predictionResult").innerText = "Crime Risk Level: " + data.risk_level;
    })
    .catch(error => console.error("Error:", error));
});
</script>
</body>
</html>
