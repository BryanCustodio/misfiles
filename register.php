<?php
session_start();
require_once './db/file_system.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_level = isset($_POST['user_level']) ? (int)$_POST['user_level'] : 1; // Default to regular user (level 1)

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif ($user_level < 1 || $user_level > 4) { // Updated to allow level 4
        $error = "Invalid user level.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Insert new user with level
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password, user_level) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("ssi", $username, $hashed_password, $user_level);
            if ($insert_stmt->execute()) {
                $success = "Registration successful. You can now <a href='./index.php'>login</a>.";
                
                // Create user directory
                $user_dir = "./uploads/" . $username;
                if (!file_exists($user_dir)) {
                    mkdir($user_dir, 0777, true);
                }
            } else {
                $error = "Registration failed. Please try again. Error: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-sm p-4" style="min-width: 300px;">
        <h2 class="mb-4 text-center">Register</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="mb-3">
                <label for="user_level" class="form-label">User Level</label>
                <select class="form-select" id="user_level" name="user_level">
                    <option value="1">View user</option>
                    <option value="2">Edit User</option>
                    <option value="3">Admin User</option>
                    <option value="4">Super Admin (All Access)</option>
                </select>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
        </form>
        <p class="mt-3 text-center">Already have an account? <a href="./index.php">Login here</a>.</p>
    </div>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
