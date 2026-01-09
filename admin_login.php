<?php
// Start the session to store user login status
session_start();

// =========================================================================
// 1. Include the Database Connection File
// =========================================================================
// This assumes db.php contains the line: $conn = new mysqli(...);
require_once 'db.php'; 

// Check if $conn was successfully created in db.php
if (!isset($conn) || $conn->connect_error) {
    error_log("DB Connection failed: " . ($conn->connect_error ?? 'Connection object not defined.'));
    die("<script>alert('A database connection error occurred. Please try again later.');</script>");
}

// =========================================================================
// 2. Form Submission Handling
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check if fields are empty (basic server-side validation)
    if (empty($email) || empty($password)) {
         echo "<script>alert('Please enter both email and password!');</script>";
    } else {
        
        // --- Database Authentication Logic ---
        
        // 1. Prepare the SQL query to fetch the password hash and other data for the given email
        $sql = "SELECT admin_id, name, password FROM admin WHERE email = ?";
        
        $stmt = $conn->prepare($sql);
        // 's' indicates the email parameter is a string
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Found a user with that email
            $user = $result->fetch_assoc();
            
            // 2. Verify the password hash
            if (password_verify($password, $user['password'])) {
                
                // Success: Password matches!
                // 3. Set session variables for authenticated status
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['admin_id'];
                $_SESSION['admin_name'] = $user['name'];
                
                // 4. Redirect to the Admin Dashboard
                $stmt->close();
                $conn->close();
                header("Location: admin_dashboard.php");
                exit;
                
            } else {
                // Failure: Password does not match the hash
                echo "<script>alert('Invalid Admin email or password!');</script>";
            }
        } else {
            // Failure: No user found with that email
            echo "<script>alert('Invalid Admin email or password!');</script>";
        }

        $stmt->close();
    }
}

// Optionally close the connection here if you don't need it later on the page
if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title> <style>
/* --- START OF SHARED CSS --- */
body {
    margin: 24px;
    font-family: Arial, Helvetica, sans-serif;
    background-color: #F8F8F8;
}

form {
    background: #F8F8F8;
    margin: 48px auto;
    padding: 40px 40px;
    border-radius: 38px;
    box-sizing: border-box;
    max-width: 400px;
    border: 2px solid #2f2f2f2c;
}
h2 {
    text-align: center;
    margin: 24px;
}
label {
    font-weight: bold;
    display: block;
    margin: 8px 0 8px;
}
input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: 2px solid #2f2f2f2c;
    box-sizing: border-box;
    font-size: 14px;
}
.opiton label {
    font-weight: normal;
    font-size: 14px;
}
.opiton {
    margin: 15px 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.opiton a {
    text-decoration: none;
    color: #2A4DFF;
    font-size: 14px;
}
button[type="submit"] {
    width: 100%;
    background-color: #E74C3C;
    border: none;
    padding: 14px;
    color: #fff;
    font-size: 18px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 500;
}
.signup-link {
    text-align: center;
    margin-top: 14px;
    font-size: 14px;
}
.signup-link a {
    text-decoration: none;
    color: #2A4DFF;
}
/* --- END OF SHARED CSS --- */
</style>
</head>
<body>
    <form method="POST" action="admin_login.php"> 
        <h2>Admin Login</h2> <label for="email">Admin E-mail:</label> <input type="email" name="email" id="email" placeholder="Enter Admin Email" required>

        <label for="password">Admin Password:</label> <input type="password" name="password" id="password" placeholder="Enter Admin Password" required>

        <div class="opiton">
            <label><input type="checkbox">Remember me</label>
            <a href="#">Forgot password?</a>
        </div>

        <button type="submit">Admin Login</button> <div class="signup-link">
            Need an Admin account? <a href="admin_signup.php">Sign up here</a> </div>
    </form>

    <script>
    // **Client-side validation (identical to original)**
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("form");
        const email = document.getElementById("email");
        const password = document.getElementById("password");

        form.addEventListener("submit", function(e) {
            if (email.value.trim() === "" || password.value.trim() === "") {
                alert("Please fill in both email and password.");
                e.preventDefault();
                return false;
            }
            const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;
            if (!emailPattern.test(email.value.trim())) {
                alert("Please enter a valid email address.");
                e.preventDefault();
                return false;
            }
        });
    });
    </script>
</body>
</html>