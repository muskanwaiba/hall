<?php
session_start();
if (isset($_GET['page']) && $_GET['page'] === 'log out') {
    header("Location: admin_login.php");
    exit; 
}
require_once "db.php"; 
date_default_timezone_set('Asia/Kathmandu'); 
$page = $_GET['page'] ?? 'dashboard';

// --- 1. Handle approve/reject POST globally (move this to top) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = intval($_POST['id']);

    if ($_POST['action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE booking SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    if ($_POST['action'] === 'reject') {
        $stmt = $conn->prepare("UPDATE booking SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    $redirectPage = $_GET['page'] ?? 'dashboard';
    header("Location: admin_dashboard.php?page=" . urlencode($redirectPage));
    exit;
}

// --- Email function remains the same ---
function send_booking_email($recipient_email, $status, $booking_details) {
    $subject = "Your Booking " . ucwords($status);
    $body = "Dear User,\n\n";
    $body .= "Your booking for the Hall: {$booking_details['hall_name']} on Date: {$booking_details['event_date']} at Time: {$booking_details['event_time']} for: {$booking_details['event_type']} has been " . $status . ".\n\n";
    $body .= "Thank you,\nAdmin Team";
    error_log("Email to {$recipient_email} - Status: {$status}");
    return true; 
}

// Admin session data
$admin_id = $_SESSION['admin_id'] ?? 'A1001';
$name = $_SESSION['name'] ?? 'Admin User';
$email = $_SESSION['email'] ?? 'admin@hall.com';
$page = $page ?? 'dashboard'; 

if ($page === 'dashboard') {

    // --- Dashboard statistics ---
    $totalUsers = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? '0';
    $totalBookings = $conn->query("SELECT COUNT(*) AS c FROM booking")->fetch_assoc()['c'] ?? '0';
    $pendingBookings = $conn->query("SELECT COUNT(*) AS c FROM booking WHERE status='pending'")->fetch_assoc()['c'] ?? '0';

    // --- Fetch all bookings for dashboard list ---
    $result_all_bookings = $conn->query("
        SELECT b.id, u.full_name AS user_name, u.email, b.event_date, b.event_time, b.hall_name, b.event_type, b.status
        FROM booking b
        JOIN users u ON b.user_id = u.id
        ORDER BY b.event_date DESC, b.event_time DESC
    ");

    // --- Calendar bookings ---
    $currentYearMonth = date('Y-m');
    $bookedDatesResult = $conn->query("
        SELECT event_date
        FROM booking        
        WHERE status = 'approved' AND event_date LIKE '{$currentYearMonth}-%'
    ");
    $bookedDays = [];
    if ($bookedDatesResult && $bookedDatesResult->num_rows > 0) {
        while ($row = $bookedDatesResult->fetch_assoc()) {
            $bookedDays[] = (int)date('d', strtotime($row['event_date']));
        }
        $bookedDays = array_unique($bookedDays);
    }
    $bookedDaysJson = json_encode($bookedDays);
    $daysInMonth = date('t');
    $currentMonthName = date('F Y');
    $currentYearMonthFull = $currentYearMonth;

    // --- Preload bookings JSON for calendar modal ---
    $allMonthlyBookingsResult = $conn->query("
        SELECT u.full_name AS user_name, b.event_date, b.event_time, b.hall_name, b.event_type, b.status
        FROM booking b
        JOIN users u ON b.user_id = u.id
        WHERE b.event_date LIKE '{$currentYearMonth}-%'
        ORDER BY b.event_date ASC, b.event_time ASC
    ");
    $allMonthlyBookings = [];
    if ($allMonthlyBookingsResult && $allMonthlyBookingsResult->num_rows > 0) {
        while ($row = $allMonthlyBookingsResult->fetch_assoc()) {
            $date = $row['event_date'];
            if (!isset($allMonthlyBookings[$date])) {
                $allMonthlyBookings[$date] = [];
            }
            $row['event_time'] = date('h:i A', strtotime($row['event_time']));
            $allMonthlyBookings[$date][] = $row;
        }
    }
    $allMonthlyBookingsJson = json_encode($allMonthlyBookings);
}

// --- FUNCTION TO DISPLAY PAGE CONTENT ---
function display_page_content($page) {
    echo '<div class="content-block">';
    echo '<h2 class="overview-title">' . ucwords(str_replace('_', ' ', $page)) . '</h2>';

    switch ($page) {
        case 'gallery_management':
            // Use absolute paths for gallery data and upload directory
            $upload_dir = __DIR__ . '/uploads/gallery/';

            function updateGalleryData($new_data_entry = null, $delete_id = null) {
                $data_file = __DIR__ . '/gallery_data.json'; 

                // Read data from JSON file
                if (file_exists($data_file)) {
                    $json_data = file_get_contents($data_file);
                    $data = json_decode($json_data, true) ?: [];
                } else {
                    $data = [];
                }

                if (!is_array($data)) $data = []; // Safety check

                if ($new_data_entry) {
                    array_unshift($data, $new_data_entry);
                }

                if ($delete_id) {
                    $data = array_filter($data, function($item) use ($delete_id) {
                        return $item['id'] !== $delete_id;
                    });
                    $data = array_values($data);
                }

                if ($new_data_entry || $delete_id) {
                    $json_content = json_encode($data, JSON_PRETTY_PRINT);
                    if (file_put_contents($data_file, $json_content) === false) {
                        echo '<p class="error-message">❌ Failed to write gallery data. Check file permissions.</p>';
                        return false;
                    }
                }

                return $data;
            }

            // Handle uploaded image saving and deletion
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
                if ($_POST['action'] === 'add_photo') {
                    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                        $ext = pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION);
                        $new_id = uniqid();
                        $new_filename = $new_id . '.' . $ext;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['photo_file']['tmp_name'], $upload_path)) {
                            $relative_url = 'uploads/gallery/' . $new_filename;
                            $new_image = [
                                'id' => $new_id,
                                'url' => $relative_url,
                                'alt' => htmlspecialchars($_POST['photo_alt']),
                            ];
                            updateGalleryData($new_image);
                            echo '<p class="success-message">🎉 Photo added successfully!</p>';
                        } else {
                            echo '<p class="error-message">⚠️ Upload failed. Check folder permissions.</p>';
                        }
                    } else {
                        echo '<p class="error-message">⚠️ No file selected or upload error.</p>';
                    }
                }

                if ($_POST['action'] === 'delete_photo' && isset($_POST['image_id'])) {
                    $delete_id = htmlspecialchars($_POST['image_id']);
                    updateGalleryData(null, $delete_id);
                    // Optional: unlink physical image file if needed
                }
            }

            // Load current images
            $hall_images = updateGalleryData();

            // Upload form
            echo '<div class="gallery-management-panel">';
            echo '<h3 class="panel-title">Add New Hall Photo/Video</h3>';
            echo '<form action="admin_dashboard.php?page=gallery_management" method="POST" enctype="multipart/form-data">';
            echo '<input type="file" name="photo_file" accept="image/*" required>';
            echo '<input type="text" name="photo_alt" placeholder="Image/Video Description (Alt Text)" required>';
            echo '<button type="submit" name="action" value="add_photo" class="add-btn">Add Media</button>';
            echo '</form>';
            echo '</div>';

            // Display images with delete button
            if ($hall_images) {
                echo '<div class="gallery-grid">';
                foreach ($hall_images as $img) {
                    echo '<div class="gallery-item-container">';
                    echo '<img class="gallery-img" src="' . htmlspecialchars($img['url']) . '" alt="' . htmlspecialchars($img['alt']) . '" />';
                    echo '<form method="POST" action="admin_dashboard.php?page=gallery_management" class="delete-photo-form" onsubmit="return confirm(\'Are you sure you want to delete this photo?\');">';
                    echo '<input type="hidden" name="image_id" value="' . htmlspecialchars($img['id']) . '">';
                    echo '<button type="submit" name="action" value="delete_photo" class="delete-btn">Delete</button>';
                    echo '</form>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>No photos in gallery yet.</p>';
            }
            break;

        case 'bookings_events':
            global $conn;
            $current_view = $_GET['view'] ?? 'pending';

// Base SQL query
$sql = "
SELECT 
    b.id,
    COALESCE(u.full_name, 'Unknown User') AS user_name, 
    b.event_date, 
    b.event_time, 
    b.hall_name, 
    b.event_type, 
    b.status
FROM booking b
LEFT JOIN users u ON b.user_id = u.id
";

// Modify WHERE clause based on view
if ($current_view === 'completed') {
    // Completed view: all bookings except pending
    $sql .= " WHERE b.status <> 'pending'";
} else {
    // Pending or approved: filter by specific status
    $sql .= " WHERE b.status = ?";
}

// Add ordering
$sql .= " ORDER BY b.event_date DESC, b.event_time DESC";

// Prepare statement
$stmt_bookings = $conn->prepare($sql);

// Bind parameter only for pending or approved
if ($current_view !== 'completed') {
    $stmt_bookings->bind_param("s", $current_view);
}

$stmt_bookings->execute();
$result_bookings = $stmt_bookings->get_result();
$stmt_bookings->close();

            echo '<div style="padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px;">';
            echo '<h2>📅 Bookings & Events Management</h2>';
            echo '<p style="font-size: 16px;">Manage all pending, approved, and completed bookings.</p>';
            echo '</div>';

            $nav_styles = [
                'pending' => ['bg' => '#f0ad4e', 'label' => 'Pending Bookings'],
                'approved' => ['bg' => '#5cb85c', 'label' => 'Approved Bookings'],
                'completed' => ['bg' => '#337ab7', 'label' => 'Completed History'],
            ];

            echo '<div class="tab-navigation" style="margin-bottom: 20px;">';
            foreach ($nav_styles as $view_key => $data) {
                $style = 'padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;';
                $style .= ($view_key === $current_view) ? "background: {$data['bg']}; color: #fff; font-weight: bold;" : "background: #eee; color: #333;";
                echo '<a href="?page=bookings_events&view=' . $view_key . '" class="button" style="' . $style . '">' . $data['label'] . '</a>';
            }
            echo '</div>';

            echo '<div class="bookings-list" style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">';
            echo '<h3>' . ucwords($current_view) . ' Bookings List (' . $result_bookings->num_rows . ' found)</h3>';

            if ($result_bookings->num_rows === 0) {
                echo '<p>No ' . $current_view . ' bookings found at this time.</p>';
            } else {
                echo '<table style="width: 100%; border-collapse: collapse; text-align: left;">';
                echo '<thead><tr><th style="border-bottom: 2px solid #ddd; padding: 10px;">ID</th>
                <th style="border-bottom: 2px solid #ddd; padding: 10px;">Date & Time</th>
                <th style="border-bottom: 2px solid #ddd; padding: 10px;">Event Type</th>
                <th style="border-bottom: 2px solid #ddd; padding: 10px;">Hall</th>
                <th style="border-bottom: 2px solid #ddd; padding: 10px;">Client</th>
                <th style="border-bottom: 2px solid #ddd; padding: 10px;">Status</th></tr></thead>';
                echo '<tbody>';

                while ($booking = $result_bookings->fetch_assoc()) {
                    $status_color = ($booking['status'] == 'pending') ? 'orange' : (($booking['status'] == 'approved') ? 'green' : 'red');
                    $display_time = date('h:i A', strtotime($booking['event_time']));

                    echo '<tr>';
                    echo '<td style="border-bottom: 1px solid #eee; padding: 10px;">' . htmlspecialchars($booking['id']) . '</td>';
                    echo '<td style="border-bottom: 1px solid #eee; padding: 10px;">' . htmlspecialchars($booking['event_date']) . ' @ ' . $display_time . '</td>';
                    echo '<td style="border-bottom: 1px solid #eee; padding: 10px;">' . htmlspecialchars($booking['event_type']) . '</td>';
                    echo '<td style="border-bottom: 1px solid #eee; padding: 10px;">' . htmlspecialchars($booking['hall_name']) . '</td>';
                    echo '<td style="border-bottom: 1px solid #eee; padding: 10px;">' . htmlspecialchars($booking['user_name']) . '</td>';
                    echo '<td style="border-bottom: 1px solid #eee; padding: 10px;">';

                    if ($current_view === 'pending') {
                        echo '<form method="POST" action="admin_dashboard.php?page=bookings_events" style="display:inline-block; margin-right:5px;">';
                        echo '<input type="hidden" name="id" value="' . $booking['id'] . '">';
                        echo '<button type="submit" name="action" value="approve" class="action-btn" style="background:#5cb85c;">Approve</button>';
                        echo '</form>';

                        echo '<form method="POST" action="admin_dashboard.php?page=bookings_events" style="display:inline-block;">';
                        echo '<input type="hidden" name="id" value="' . $booking['id'] . '">';
                        echo '<button type="submit" name="action" value="reject" class="action-btn" style="background:#d9534f;">Reject</button>';
                        echo '</form>';
                    } else {
                        echo '<span style="color: ' . $status_color . '; font-weight: bold;">' . ucwords($booking['status']) . '</span>';
                    }

                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
            }

            echo '</div>';
            break;

        case 'log out':
            break;

        default:
            echo '<p>No content available for this page.</p>';
            break;
    }

    echo '</div>'; // content-block
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Hallzen Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><i class="fas fa-lotus"></i><span>Hallzen</span></div>
    <nav><ul>
        <li class="<?= $page === 'dashboard' ? 'active' : '' ?>"><a href="?page=dashboard"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
        <li class="<?= $page === 'gallery_management' ? 'active' : '' ?>"><a href="?page=gallery_management"><i class="fas fa-warehouse"></i><span>Gallery Management</span></a></li>
        <li class="<?= $page === 'bookings_events' ? 'active' : '' ?>"><a href="?page=bookings_events"><i class="fas fa-calendar-check"></i><span>Bookings & Events</span></a></li>
        <li class="<?= $page === 'log out' ? 'active' : '' ?>"><a href="?page=log out"><i class="fas fa-cog"></i><span>Log Out</span></a></li>
    </ul></nav>
</div>

<div class="main-content">
<header class="top-header">
    <div class="header-title"><?= ucwords(str_replace('_', ' ', $page)) ?></div>
    
    <div class="header-profile" style="position: relative;"> 
        <div id="profile-trigger" style="cursor: pointer; display: flex; align-items: center;"> 
            <img src="https://i.pravatar.cc/40?img=3" alt="Admin"/>
            <div class="profile-info">
                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        
        <div id="profile-dropdown" class="profile-dropdown">
            <div class="dropdown-info">
                <p class="name"><?php echo htmlspecialchars($name); ?></p>
                <p><strong>Admin ID:</strong> <?php echo htmlspecialchars($admin_id); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <hr>
                <a href="logout.php" style="color: #d9534f; text-decoration: none; display: block; padding-top: 5px;">
                    🚪 Logout
                </a>
            </div>
        </div>
    </div>
</header>

<div class="dashboard-container">
    <?php 
    // Set the class to make the main panel full width if not on the dashboard
    $main_panel_class = ($page === 'dashboard') ? 'dashboard-main-panel' : 'dashboard-main-panel full-width-content';
    ?>
    <div class="<?= $main_panel_class ?>">
        <?php if ($page === 'dashboard'): // --- DASHBOARD CONTENT BLOCK (with stats and booking list) --- ?>

            <h2 class="overview-title">Overview</h2>
            <div class="stats-grid">
                <!-- Stat 1: Total Users -->
                <div class="stat-card"><i class="fas fa-users"></i><p class="value"><?= $totalUsers ?></p><p class="label">Total Users</p></div>
                <!-- Stat 2: Total Bookings (All statuses) -->
                <div class="stat-card"><i class="fas fa-calendar-alt"></i><p class="value"><?= $totalBookings ?></p><p class="label">Total Bookings</p></div>
            </div>

            <div class="booked-info">
                <div class="booked-title">User Booked Info (All Bookings)</div>
                <div class="booking-list">
                    <?php if ($result_all_bookings && $result_all_bookings->num_rows > 0): ?>
                        <?php while ($row = $result_all_bookings->fetch_assoc()): 
                            $status_class = 'status-' . htmlspecialchars($row['status']);
                        ?>
                            <div class="booking-item <?= $status_class ?>">
                                <div class="booking-info">
                                    <span class="user">Booked by: **<?= htmlspecialchars($row['user_name']) ?>**</span>
                                    <span class="details">Hall: **<?= htmlspecialchars($row['hall_name']) ?>** | Type: **<?= htmlspecialchars($row['event_type']) ?>** || Date: **<?= htmlspecialchars($row['event_date']) ?>** | Time: **<?= htmlspecialchars(date('h:i A', strtotime($row['event_time']))) ?>**</span>
                                    <span class="status-tag <?= $status_class . '-tag' ?>"><?= ucwords(htmlspecialchars($row['status'])) ?></span>
                                </div>
                                <div class="booking-actions">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="accept-btn">Accept</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:12px; color:#999;">Already <?= ucwords($row['status']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color:#555; padding: 10px;">No bookings found.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: // --- OTHER PAGE CONTENT BLOCK --- ?>

            <?php display_page_content($page); ?>

        <?php endif; ?>
    </div>

    <?php 
    // Set the class to hide the side panel if not on the dashboard
    $side_panel_class = ($page === 'dashboard') ? 'calendar-side' : 'calendar-side hidden-side';
    ?>
    <div class="<?= $side_panel_class ?>">
        <?php if ($page === 'dashboard'): // --- CALENDAR ONLY ON DASHBOARD --- ?>
            <div class="dashboard-calendar">
                <h3>Booking Calendar</h3>
                <div class="calendar-header">
                    <p>📅 Current: **<?= $currentMonthName ?>**</p>
                </div>
                <div id="calendar"></div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Calendar Detail Modal -->
<div id="bookingDetailModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="modalTitle">Bookings on [Date]</h4>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div id="modalBookingList" class="modal-booking-list">
            <!-- Booking details will be injected here -->
        </div>
    </div>
</div>
<!-- End Modal -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('profile-trigger');
        const dropdown = document.getElementById('profile-dropdown');

        // 1. Toggle visibility when the trigger is clicked
        trigger.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior if applicable
            // Toggle the 'show' class, making the dropdown visible/invisible
            dropdown.classList.toggle('show');
        });

        // 2. Close the dropdown if the user clicks anywhere else on the page
        document.addEventListener('click', function(event) {
            const isClickInside = trigger.contains(event.target) || dropdown.contains(event.target);
            
            if (!isClickInside) {
                dropdown.classList.remove('show');
            }
        });
    });
    // --- 1. Define global variables to hold PHP data ---
    // Use var to define these in the global scope so showBookings can access them
    var globalAllMonthlyBookings = {};
    
    document.addEventListener('DOMContentLoaded', () => {
        // Only run calendar script on the dashboard page
        if ('<?= $page ?>' !== 'dashboard') return; 

        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        // --- PHP Variables passed to JavaScript ---
        const daysInMonth = <?= $daysInMonth ?>;
        // bookedDays is used locally, so const is fine
        const bookedDays = <?= $bookedDaysJson ?>; // Array of approved day numbers 
        const currentYearMonthFull = '<?= $currentYearMonthFull ?>'; // YYYY-MM
        const todayDay = parseInt('<?= date('d') ?>');
        
        // --- FIX IMPLEMENTED HERE: Assign parsed data to the global variable ---
        // Parse the JSON string from PHP and assign it to the global variable
        globalAllMonthlyBookings = JSON.parse('<?= $allMonthlyBookingsJson ?>');
        
        // Calculate the index of the first day of the month (Saturday=0)
        // PHP date('w') returns 0 (Sunday) to 6 (Saturday).
        // To make Saturday=0 for your array, we need (PHP_w + 1) % 7 
        // Example: Sun (0) -> (0+1)%7 = 1. Sat (6) -> (6+1)%7 = 0. This looks correct.
        const firstDayOfWeekIndex = (parseInt('<?= date('w', strtotime(date('Y-m-01'))) ?>') + 1) % 7;
        // ---

        const daysOfWeek = ['Sat','Sun','Mon','Tue','Wed','Thu','Fri'];
        let html = '<table class="calendar-table"><thead><tr>';
        daysOfWeek.forEach(day => html += `<th>${day}</th>`);
        html += '</tr></thead><tbody><tr>';

        // Add blank cells for days before the 1st
        const blankDays = firstDayOfWeekIndex;
        for (let i = 0; i < blankDays; i++) {
            html += '<td></td>';
        }

        // Add day cells
        let currentDayIndex = blankDays;
        for (let day = 1; day <= daysInMonth; day++) {
            let className = 'available';
            if (bookedDays.includes(day)) {
                className = 'booked';
            }

            // Add 'today' class if it's the current day
            if (day === todayDay) {
                className += ' today';
            }
            
            // Check for past dates (optional, but good practice)
            const dateToCheck = new Date(currentYearMonthFull + '-' + String(day).padStart(2, '0'));
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Normalize today's date

            if (dateToCheck < today && day !== todayDay) {
                className += ' past-day'; 
            }

            // Add a data attribute to store the full date for easy lookup
            const fullDate = currentYearMonthFull + '-' + String(day).padStart(2, '0');

            // The onclick call is correct
            html += `<td class="${className.trim()}" data-date="${fullDate}" onclick="showBookings('${fullDate}')">${day}</td>`;

            // Start a new row every 7 days (Saturday is the first column)
            currentDayIndex++;
            if (currentDayIndex % 7 === 0) {
                html += '</tr><tr>';
            }
        }

        // Add blank cells at the end of the last week (if needed)
        const remainingCells = 7 - (currentDayIndex % 7);
        if (remainingCells < 7) {
            for (let i = 0; i < remainingCells; i++) {
                html += '<td></td>';
            }
        }

        html += '</tr></tbody></table>';
        calendarEl.innerHTML = html;
    });

    // --- Modal Functions ---

    // This function can now safely access globalAllMonthlyBookings
    function showBookings(date) {
        const modal = document.getElementById('bookingDetailModal');
        const titleEl = document.getElementById('modalTitle');
        const listEl = document.getElementById('modalBookingList');
        
        // FIX: Access the global variable directly
        const bookingsData = globalAllMonthlyBookings; 

        // Format date for display in the modal title (e.g., 2025-11-14 -> Nov 14, 2025)
        const dateObj = new Date(date + 'T00:00:00'); 
        const displayDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        titleEl.textContent = `Bookings on ${displayDate}`;

        listEl.innerHTML = ''; // Clear previous content

        const bookings = bookingsData[date];

        if (bookings && bookings.length > 0) {
            let listHtml = '';
            bookings.forEach(booking => {
                const statusColor = getStatusColor(booking.status);
                listHtml += `
                    <div class="modal-booking-item" style="border-left: 5px solid ${statusColor}; padding: 10px; margin-bottom: 8px; background: #fafafa; border-radius: 4px;">
                        <p style="margin: 0;"><strong>Hall:</strong> ${booking.hall_name}</p>
                        <p style="margin: 0;"><strong>Time:</strong> ${booking.event_time}</p>
                        <p style="margin: 0;"><strong>Type:</strong> ${booking.event_type}</p>
                        <p style="margin: 0;"><strong>User:</strong> ${booking.user_name} 
                            <span class="modal-status" style="
                                display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; 
                                background: ${statusColor}; color: ${booking.status === 'pending' ? '#444' : '#fff'};
                            ">
                                ${booking.status.toUpperCase()}
                            </span>
                        </p>
                    </div>
                `;
            });
            listEl.innerHTML = listHtml;
        } else {
            listEl.innerHTML = '<p style="padding: 10px; text-align: center; color: #777;">No bookings found for this date.</p>';
        }
        modal.style.display = 'flex'; // Show the modal
    }

    function getStatusColor(status) {
        switch(status) {
            case 'pending': return '#fdd835'; // Yellow
            case 'approved': return '#34a853'; // Green
            case 'rejected': return '#e74c3c'; // Red
            default: return '#7d5fff';
        }
    }

    function closeModal() {
        document.getElementById('bookingDetailModal').style.display = 'none';
    }

</script>
</body>
</html>