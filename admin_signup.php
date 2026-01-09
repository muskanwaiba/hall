<?php
// =========================================================================
// 1. Include the Database Connection File
// =========================================================================
// **IMPORTANT:** This assumes db.php contains the line: $conn = new mysqli(...);
require_once 'db.php'; 

// Check if $conn was successfully created in db.php
if (!isset($conn) || $conn->connect_error) {
    // If connection failed in db.php, stop execution and show error
    error_log("DB Connection failed: " . ($conn->connect_error ?? 'Connection object not defined.'));
    die("<script>alert('A database connection error occurred. Please check the server configuration.');</script>");
}

// =========================================================================
// 2. Form Submission Handling
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Input Sanitization and Retrieval ---
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Server-side Validation ---
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        echo "<script>alert('All fields are required!');</script>";
    } elseif ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        
        // --- Prepare and Execute the INSERT Query ---
        
        // 1. Hash the password securely BEFORE storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // 2. SQL to insert into the 'admin' table (columns: name, email, password)
        // Note: The admin_id column is typically AUTO_INCREMENTed by the database.
        $sql = "INSERT INTO admin (name, email, password) VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        // 'sss' indicates the three parameters are strings
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            echo "<script>alert('Admin account created successfully! Redirecting to login...');</script>";
            // Redirect the user to the login page after success
            header("Refresh: 2; URL=admin_login.php"); 
            exit;
        } else {
            // Log the error for debugging
            error_log("Admin insert failed: " . $stmt->error);
            echo "<script>alert('Error creating account. Please check the server logs.');</script>";
        }

        // 3. Close statement (connection closing can be done at the end of the script or managed via db.php)
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
    <title>Admin Signup</title> <style>
/* --- START OF SHARED CSS --- */
/* The CSS is identical to maintain the look */
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
/* Extended to cover all input types needed for signup */
input[type="text"], 
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
    <form method="POST" action="admin_signup.php"> 
        <h2>Admin Signup</h2> <label for="name">Name:</label>
        <input type="text" name="name" id="name" placeholder="Enter your Name" required>

        <label for="email">Admin E-mail:</label>
        <input type="email" name="email" id="email" placeholder="Enter Admin Email" required>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" placeholder="Choose a Password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>

        <div style="margin: 20px 0;"></div>

        <button type="submit">Create Admin Account</button> <div class="signup-link">
            Already have an Admin account? <a href="admin_login.php">Login here</a> </div>
    </form>
    
    <script>
    // **Client-side validation adapted for signup**
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("form");
        const name = document.getElementById("name");
        const email = document.getElementById("email");
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirm_password");

        form.addEventListener("submit", function(e) {
            // Basic check for required fields
            if (name.value.trim() === "" || email.value.trim() === "" || password.value.trim() === "" || confirmPassword.value.trim() === "") {
                alert("Please fill in all required fields.");
                e.preventDefault();
                return false;
            }
            
            // Email pattern check
            const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;
            if (!emailPattern.test(email.value.trim())) {
                alert("Please enter a valid email address.");
                e.preventDefault();
                return false;
            }

            // Password match check
            if (password.value !== confirmPassword.value) {
                alert("The passwords you entered do not match.");
                e.preventDefault();
                return false;
            }
        });
    });
    </script>
</body>
</html>