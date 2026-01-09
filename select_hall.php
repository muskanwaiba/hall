<?php
// Include the database connection file
require_once 'db.php'; 

// NOTE: Ideally, you would fetch this data from a 'halls' table in your database.
// For now, we will use hardcoded data to maintain the UI structure.

// Define hall data to be displayed
$hallOptions = [
    'Banquet Hall 1' => [
        'name' => 'Banquet Hall 1', 
        'image' => 'https://images.unsplash.com/photo-1519121789249-b30933a281b0?auto=format&fit=crop&w=700&q=80', 
        'description' => 'A cozy, elegant hall ideal for intimate gatherings, parties, and milestone events with top-quality services and comfort.',
        'features' => [
            'Capacity: Up to 80 guests', 
            'Floor Area: 340 m²', 
            'Private kitchen available for light catering', 
            'Smart TV/projector for digital presentations',
            'Modern air conditioning'
        ]
    ],
    'Banquet Hall 2' => [
        'name' => 'Banquet Hall 2', 
        'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=700&q=80',
        'description' => 'A grand hall with luxurious interiors, advanced facilities, and flexible layouts—perfect for large-scale weddings, conferences, and corporate galas.',
        'features' => [
            'Capacity: Up to 700 guests', 
            'Floor Area: 1600 m²', 
            'High-speed Wi-Fi & streaming support', 
            'Dynamic stage lighting & premium sound',
            'Parking for 200+ vehicles',
            'Smart electronic access & 24/7 security',
            'In-house catering & multi-cuisine options',
            'Fully accessible & VIP lounge areas'
        ]
    ]
];

// Close the connection if it was opened
if (isset($conn)) {
    // You typically close the connection only after all database queries are done.
    // Since we're not querying here, we can close it if the db.php file connects immediately.
    // $conn->close(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Banquet Halls | Hallzen</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    /* --- Light UI Overhaul --- */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f0f2f5; 
      color: #333; 
      margin: 0;
      padding: 50px 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }
    h1 {
      color: #e74c3c; 
      margin-bottom: 40px;
      font-weight: 700;
    }
    .halls-wrapper {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
      justify-content: center;
      max-width: 90%;
    }
    .hall-card {
      background-color: #ffffff; 
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); 
      max-width: 400px;
      margin-bottom: 25px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .hall-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .hall-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      background: #e0e0e0;
      border-bottom: 2px solid #e74c3c; 
    }
    .hall-info {
      padding: 25px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .hall-info h2 {
      color: #e74c3c; 
      margin-bottom: 10px;
      font-size: 24px;
      font-weight: 600;
      letter-spacing: normal;
    }
    .hall-info p {
      font-size: 14px;
      line-height: 1.5;
      color: #555;
      margin: 0 0 15px 0;
    }
    .features-list {
      list-style: none;
      padding: 0;
      margin: 0 0 20px 0;
    }
    .features-list li {
      font-size: 14px;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      color: #555;
    }
    .features-list li i {
      color: #3498db; 
      margin-right: 10px;
      font-size: 16px;
      flex-shrink: 0;
    }
    .btn-select {
      background-color: #2ecc71; 
      border: none;
      padding: 12px 0;
      border-radius: 5px;
      color: #ffffff;
      font-weight: bold;
      cursor: pointer;
      text-transform: uppercase;
      font-size: 14px;
      box-shadow: 0 2px 5px rgba(46, 204, 113, 0.5);
      transition: background-color 0.2s, transform 0.1s;
      margin-top: auto; 
    }
    .btn-select:hover { 
      background-color: #27ae60; 
      transform: translateY(-1px);
    }
    
    @media(max-width: 950px) {
      .halls-wrapper { 
        flex-direction: column; 
        align-items: center; 
      }
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <h1>Select a Hall to Book</h1>

    <form id="hallSelectionForm" method="GET" action="booknow.php">
      <input type="hidden" name="hall_name" id="selectedHallName" required>

      <div class="halls-wrapper">
        
        <?php foreach ($hallOptions as $hall): ?>
        <div class="hall-card">
          <img src="<?php echo $hall['image']; ?>"
                alt="<?php echo $hall['name']; ?> interior" class="hall-image" />
          <div class="hall-info">
            <h2><?php echo $hall['name']; ?></h2>
            <p><?php echo $hall['description']; ?></p>
            <ul class="features-list">
              <?php 
              $icons = ['fa-users', 'fa-ruler-combined', 'fa-utensils', 'fa-tv', 'fa-snowflake', 'fa-broadcast-tower', 'fa-lightbulb', 'fa-car', 'fa-shield-alt', 'fa-champagne-glasses', 'fa-wheelchair'];
              $i = 0;
              foreach ($hall['features'] as $feature): 
                $icon_class = $icons[$i % count($icons)]; // Cycle through icons
              ?>
              <li><i class="fas <?php echo $icon_class; ?>"></i><?php echo $feature; ?></li>
              <?php 
                $i++;
              endforeach; 
              ?>
            </ul>
            <button type="button" class="btn-select" data-hall-name="<?php echo $hall['name']; ?>">Select & Proceed</button>
          </div>
        </div>
        <?php endforeach; ?>
        
      </div>
    </form>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const selectButtons = document.querySelectorAll('.btn-select');
        const hallNameInput = document.getElementById('selectedHallName');
        const form = document.getElementById('hallSelectionForm');

        selectButtons.forEach(button => {
          button.addEventListener('click', function() {
            // 1. Get the hall name from the button's data attribute
            const hallName = this.getAttribute('data-hall-name');
            
            // 2. Set the value in the hidden input field
            hallNameInput.value = hallName;
            
            // 3. Submit the form to the next page (booknow.php)
            form.submit();
          });
        });
      });
    </script>
</body>
</html>