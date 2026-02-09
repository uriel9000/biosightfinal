<?php include 'db_config.php'; ?>
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
</body>
</html>
