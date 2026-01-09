<?php
header('Content-Type: application/json');

// Path to your JSON file
$jsonFile = __DIR__ . '/../gallery_data.json';

if (!file_exists($jsonFile)) {
    echo json_encode([
        "images" => [],
        "error" => "Gallery file not found"
    ]);
    exit;
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "images" => [],
        "error" => "Invalid JSON format"
    ]);
    exit;
}

// Ensure correct structure
if (!isset($data['images']) || !is_array($data['images'])) {
    echo json_encode(["images" => []]);
    exit;
}

echo json_encode([
    "images" => $data['images']
]);
