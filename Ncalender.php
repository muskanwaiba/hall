<?php
require_once 'db.php';

$month = $_GET['month'] ?? '';
$data = [];

$stmt = $conn->prepare("
  SELECT DISTINCT event_date FROM booking
  WHERE event_date LIKE CONCAT(?, '%')
  AND status IN ('Pending','Confirmed')
");
$stmt->bind_param("s", $month);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $data[] = $r['event_date'];
}

echo json_encode($data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hall Availability Calendar</title>
  <link rel="stylesheet" href="ncalendar.css">
</head>
<body>

<section class="calendar-section">
  <h2>Check Hall Availability</h2>

  <div class="calendar-container">
    <div class="calendar-header">
      <button id="prevMonth">&lt;</button>
      <h3 id="monthYear"></h3>
      <button id="nextMonth">&gt;</button>
    </div>

    <div class="calendar-weekdays">
      <span>Sun</span><span>Mon</span><span>Tue</span>
      <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
    </div>

    <div class="calendar-days" id="calendarDays"></div>
  </div>
</section>

<script src="ncalendar.js"></script>
</body>
</html>
