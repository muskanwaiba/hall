<?php
require_once 'db.php';

/* ---------------------------
   1. Fetch booked dates
---------------------------- */
$bookedDates = [];
$sql_fetch_dates = "SELECT DISTINCT event_date FROM booking WHERE status IN ('Pending','Confirmed')";
$result_dates = $conn->query($sql_fetch_dates);

if ($result_dates) {
    while ($row = $result_dates->fetch_assoc()) {
        $bookedDates[] = $row['event_date'];
    }
}
$bookedDatesJSON = json_encode($bookedDates);

$message = "";

/* ---------------------------
   2. Form submission
---------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $eventDate = $_POST['eventdate'] ?? '';
    $fullName = trim($_POST['fullname'] ?? '');
    $contactNumber = trim($_POST['contactnumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $shift = $_POST['shift'] ?? '';
    $guests = (int)($_POST['guests'] ?? 0);
    $eventType = $_POST['eventtype'] ?? '';
    $eventTime = $_POST['eventtime'] ?? '';
    $hallName = $_POST['hall'] ?? '';

    $status = 'Pending';

    /* ---------------------------
       Validation
    ---------------------------- */
    if (!$eventDate || !$fullName || !$email) {
        $message = "<div style='color:red;text-align:center;'>Required fields missing.</div>";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div style='color:red;text-align:center;'>Invalid email address.</div>";
    }
    elseif ($guests <= 0) {
        $message = "<div style='color:red;text-align:center;'>Invalid guest number.</div>";
    }
    else {

        /* ---------------------------
           Check hall availability
        ---------------------------- */
        $check = $conn->prepare("
            SELECT id FROM booking 
            WHERE event_date = ? 
            AND hall_name = ?
            AND status IN ('Pending','Confirmed')
        ");
        $check->bind_param("ss", $eventDate, $hallName);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "<div style='color:red;text-align:center;'>
                This hall is already booked on selected date.
            </div>";
        } else {

            /* ---------------------------
               Insert into users table
            ---------------------------- */
            $userStmt = $conn->prepare("
                INSERT INTO users (full_name, email, contact_number)
                VALUES (?, ?, ?)
            ");
            $userStmt->bind_param("sss", $fullName, $email, $contactNumber);

            if ($userStmt->execute()) {

                $userId = $userStmt->insert_id; // 🔥 IMPORTANT
                $userStmt->close();

                /* ---------------------------
                   Insert into booking table
                ---------------------------- */
                $bookStmt = $conn->prepare("
                    INSERT INTO booking 
                    (user_id, event_date, event_time, event_type, hall_name, guests, status, contact_number, email)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $bookStmt->bind_param(
                    "issssisss",
                    $userId,
                    $eventDate,
                    $eventTime,
                    $eventType,
                    $hallName,
                    $guests,
                    $status,
                    $contactNumber,
                    $email
                );

                if ($bookStmt->execute()) {
                    $message = "<div style='color:green;text-align:center;'>
                        Booking successfully submitted!
                    </div>";
                } else {
                    $message = "<div style='color:red;text-align:center;'>
                        Booking Error: {$bookStmt->error}
                    </div>";
                }

                $bookStmt->close();

            } else {
                $message = "<div style='color:red;text-align:center;'>
                    User creation failed.
                </div>";
            }
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Booking page - Hall Booking</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #1a1a1a; }
        .container { width: 90%; max-width: 1000px; margin: 50px auto; background: #fff; border-radius: 12px; border: 1px solid #2f2f2f2c; display: flex; padding: 24px; }
        .left, .right { flex: 1; padding: 18px; }
        .left { border-right: 1px solid #ddd; }
        .small-header h3 { font-size: 24px; color: #e74c3c; margin-bottom: 24px; text-align: center;}
        .calendar-controls { display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 12px; }
        .calendar-controls button { background: #e74c3c; color: #fff; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; }
        #calendarTable { width: 100%; border-collapse: collapse; text-align: center; margin-bottom: 10px; }
        #calendarTable th { padding: 8px; font-size: 13px; color: #65696f; }
        #calendarTable td { width: 30px; height: 30px; font-size: 13px; border-radius: 50%; cursor: pointer; }
        #calendarTable td.selected { background: #e74c3c; color: #fff; }
        #calendarTable td.booked { background: #e74c3c; color: #fff; pointer-events: none; opacity: 0.6; }
        #calendarTable td.disabled { color: #ccc; pointer-events: none; }
        #calendarTable td:hover:not(.selected):not(.disabled):not(.booked) { background: #ffd8c4; }
        .right h3 { font-size: 20px; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .right h3 span { color: #e74c3c; }
        form { display: flex; flex-direction: column; align-items: center; gap: 14px; }
        input, select { width: 90%; padding: 12px; border: 1px solid #2f2f2f2c; border-radius: 4px; box-sizing: border-box; }
        button[type="submit"] { background: #e74c3c; color: #fff; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 90%; }
        @media (max-width: 900px) { .container { flex-direction: column; } .left { border-right: none; border-bottom: 1px solid #ddd; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="left">
            <div class="small-header"><h3>Choose Your Date</h3></div>
            <div class="calendar-controls">
                <button id="prevMonth">&lt;</button>
                <span id="monthYear"></span>
                <button id="nextMonth">&gt;</button>
            </div>
            <table id="calendarTable"></table>
        </div>

        <div class="right">
            <h3>Fill the <span>Forms</span></h3>
            <?php echo $message; ?>
            <form method="POST" action="Booknow.php"> 
                <input type="hidden" name="eventdate" id="eventDateInput" required />
                <input type="text" name="fullname" placeholder="Full name" required />
                <input type="tel" name="contactnumber" placeholder="Contact Number" required />
                <input type="email" name="email" placeholder="Email Address" required />
                <select name="shift" required>
                    <option value="">Select Shift</option>
                    <option value="Morning">Morning</option>
                    <option value="Evening">Evening</option>
                    <option value="Whole Day">Whole Day</option>
                </select>
                <input type="number" name="guests" placeholder="Guest No." required />
                <select name="eventtype" required>
                    <option value="">Select event type</option>
                    <option value="Wedding">Wedding</option>
                    <option value="Party">Party</option>
                    <option value="Conference">Conference</option>
                </select>
                <select name="hall" required>
    <option value="">Select Hall</option>
    <option value="The Grand Ballroom">The Grand Ballroom</option>
    <option value="The Crystal Room">The Crystal Room</option>
</select>

                <input type="time" name="eventtime" value="12:00" required />
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

<script>
    var currentBookedDates = <?php echo $bookedDatesJSON; ?>;
    var calendarTable = document.getElementById('calendarTable');
    var monthYear = document.getElementById('monthYear');
    var eventDateInput = document.getElementById('eventDateInput'); 
    var today = new Date();
    var currMonth = today.getMonth();
    var currYear = today.getFullYear();
    var selectedDay = null;

    function fetchBookedDates(year, month, callback) {
        var yearMonth = year + '-' + ('0' + (month + 1)).slice(-2); 
        fetch('fetch_bookings.php?month=' + yearMonth) 
            .then(response => response.json())
            .then(data => {
                currentBookedDates = data; 
                if (callback) callback();
            })
            .catch(err => {
                console.error('Fetch error:', err);
                if (callback) callback(); // Load calendar anyway even if fetch fails
            });
    } 

    function updateCalendar(month, year) {
        var firstDay = new Date(year, month, 1);
        var startDay = firstDay.getDay() === 0 ? 7 : firstDay.getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        
        var html = '<thead><tr>' + weekdays.map(d => `<th>${d}</th>`).join('') + '</tr></thead><tbody><tr>';
        var dayCell = 1;

        for (var i = 1; i < startDay; i++, dayCell++) html += '<td class="disabled"></td>';

        for (var day = 1; day <= daysInMonth; day++, dayCell++) {
            var dateString = year + '-' + ('0' + (month + 1)).slice(-2) + '-' + ('0' + day).slice(-2);
            var isPast = new Date(year, month, day) < new Date().setHours(0,0,0,0);
            var isBooked = currentBookedDates.includes(dateString);
            var isSelected = selectedDay && day === selectedDay.day && month === selectedDay.month;

            var classes = [];
            if (isSelected) classes.push('selected');
            if (isPast) classes.push('disabled');
            if (isBooked) classes.push('booked');

            html += `<td class="${classes.join(' ')}" data-day="${day}">${day}</td>`;
            if (dayCell % 7 === 0 && day !== daysInMonth) html += '</tr><tr>';
        }
        
        while (dayCell % 7 !== 1) { html += '<td class="disabled"></td>'; dayCell++; }
        calendarTable.innerHTML = html + '</tr></tbody>';
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        monthYear.textContent = monthNames[month] + ' ' + year;
    }

    calendarTable.onclick = function (e) {
        if (e.target.tagName === 'TD' && !e.target.classList.contains('disabled') && !e.target.classList.contains('booked')) {
            var day = parseInt(e.target.getAttribute('data-day'));
            selectedDay = { day: day, month: currMonth, year: currYear };
            var formattedDate = currYear + '-' + ('0' + (currMonth + 1)).slice(-2) + '-' + ('0' + day).slice(-2);
            eventDateInput.value = formattedDate;
            document.querySelector('.right h3 span').textContent = formattedDate;
            updateCalendar(currMonth, currYear);
        }
    };

    document.getElementById('prevMonth').onclick = () => {
        currMonth--; if(currMonth < 0) { currMonth = 11; currYear--; }
        fetchBookedDates(currYear, currMonth, () => updateCalendar(currMonth, currYear));
    };

    document.getElementById('nextMonth').onclick = () => {
        currMonth++; if(currMonth > 11) { currMonth = 0; currYear++; }
        fetchBookedDates(currYear, currMonth, () => updateCalendar(currMonth, currYear));
    };

    // Initial load from PHP data
    updateCalendar(currMonth, currYear);
</script>
</body>
</html>