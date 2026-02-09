<?php
include 'db_config.php';

$location = $_POST['location'];
$crime_type = $_POST['crime_type'];

$api_url = "http://127.0.0.1:5000/predict";
$data = json_encode(["location" => $location, "crime_type" => $crime_type]);

$options = [
    "http" => [
        "header" => "Content-type: application/json",
        "method" => "POST",
        "content" => $data
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($api_url, false, $context);
$result = json_decode($response, true);

echo json_encode(["risk_level" => $result['risk_level']]);
?>
