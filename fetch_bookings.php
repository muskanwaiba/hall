<?php
require_once 'db.php';
header('Content-Type: application/json');

$month = $_GET['month'] ?? date('Y-m');
$bookedDates = [];

// Use a prepared statement to prevent SQL injection
$sql = "SELECT DISTINCT event_date FROM booking WHERE status IN ('Pending', 'Confirmed') AND event_date LIKE ?";
$stmt = $conn->prepare($sql);
$search = $month . '%';
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $bookedDates[] = $row['event_date'];
}

echo json_encode($bookedDates);
$stmt->close();
$conn->close();