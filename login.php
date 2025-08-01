<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $workspaceId = trim($_POST['workspace_id'] ?? '');

    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND workspace_id = ?");
        $stmt->execute([$email, $workspaceId]);
    }

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($role === 'user' && !$user['is_approved']) {
            echo "<script>alert('❌ Your account is not approved yet.'); window.location.href='login.php';</script>";
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $role;
        $_SESSION['workspace_id'] = $user['workspace_id'];

        $redirect = ($role === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
        header("Location: $redirect");
        exit;
    } else {
        echo "<script>alert('❌ Invalid credentials'); window.location.href='login.php';</script>";
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles/login.css">
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="flex min-h-screen">
        <!-- Left Section: Login Form -->
        <div class="LeftSection flex-1 flex items-center justify-center bg-white p-8">
            <div class="Box w-full max-w-sm">
                <h2 class="text-6xl font-bold text-center mb-6 select-none">Login</h2>
                
                <!-- Replace onsubmit + JS login with normal POST -->
<form action="login.php" method="POST" class="space-y-4">
    <!-- Email -->
    <input type="email" name="email" placeholder="Email" required class="text-black w-full p-3 border rounded-md"/>

    <!-- Password -->
    <input type="password" name="password" placeholder="Password" required class="text-black w-full p-3 border rounded-md"/>

    <!-- Workspace ID (only for users) -->
    <div id="workspace-id-container" class="hidden">
        <input type="text" name="workspace_id" placeholder="Workspace ID" class="text-black w-full p-3 border rounded-md"/>
    </div>

    <!-- Role Selection -->
    <div class="mb-4">
        <label class="mr-2 font-semibold">Role:</label>
        <label class="inline-flex items-center">
            <input type="radio" name="role" value="admin" required onclick="toggleWorkspace(false)">
            <span class="ml-2">Admin</span>
        </label>
        <label class="inline-flex items-center ml-4">
            <input type="radio" name="role" value="user" required onclick="toggleWorkspace(true)">
            <span class="ml-2">User</span>
        </label>
    </div>


    <!-- Message -->
    <p class="text-sm">Don't have an account? <a href="register.php" class="text-blue-500">Register</a></p>


    <input type="submit" value="Login" class="w-full py-3 bg-blue-500 text-white font-semibold rounded hover:bg-blue-600"/>
</form>

            </div>
        </div>

        <!-- Right Section: Welcome Message + Image -->
        <div class="RightSection flex-1 relative bg-blue-500 text-white">
            <!-- Welcome Text -->
            <div class="absolute select-none top-10 left-1/2 transform -translate-x-1/2 -translate-y-1/2 mt-60 text-6xl font-semibold z-10">
                Welcome
            </div>
            
            <!-- Full Image Background -->
            <img src="./img/bgSubscription.jpg" alt="Welcome Image" class="w-full h-full object-cover"/>
        </div>
    </div>

    <!-- Theme Switcher -->
    <div class="theme-switch-wrapper">
        <label class="theme-switch" for="checkbox">
            <input type="checkbox" id="checkbox">
            <div class="slider round">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
            </div>
        </label>
    </div>

    <script src = "scripts/login.js">
       
    </script>
</body>
</html>

