<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
    <link rel="stylesheet" href="styles/register.css" />
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="flex min-h-screen">
        <!-- Left Section -->
        <div class="flex-1 flex items-center justify-center p-8 LeftSection">
            <div class="w-full max-w-sm">
                <h2 class="text-6xl font-bold text-center mb-6">Register</h2>

                <!-- Toggle -->
                <div class="flex mb-6 gap-6 justify-center">
                    <button id="admin-toggle" class="text-white bg-blue-500 hover:bg-blue-600 py-2 px-4 rounded-md">Admin</button>
                    <button id="user-toggle" class="text-white bg-green-500 hover:bg-green-600 py-2 px-4 rounded-md">User</button>
                </div>

                <!-- Admin Form -->
                <form id="admin-form" method="POST" action="register.php" class="space-y-4 hidden">
                    <input type="text" name="username" id="admin-name" placeholder="Admin Name" required class="w-full p-3 border border-gray-300 rounded-md" />
                    <input type="email" name="email" id="admin-email" placeholder="Email" required class="w-full p-3 border border-gray-300 rounded-md" />
                    <input type="password" name="password" id="admin-password" placeholder="Password" required class="w-full p-3 border border-gray-300 rounded-md" />
                    <input type="hidden" name="role" value="admin" />
                    <button type="submit" class="w-full py-3 bg-blue-500 text-white font-semibold rounded-md">Register as Admin</button>
                </form>

                <!-- User Form -->
                <form id="user-form" method="POST" action="register.php" class="space-y-4 hidden">
                    <input type="text" name="username" id="user-name" placeholder="User Name" required class="w-full p-3 border border-gray-300 rounded-md" />
                    <input type="email" name="email" id="user-email" placeholder="Email" required class="w-full p-3 border border-gray-300 rounded-md" />
                    <input type="password" name="password" id="user-password" placeholder="Password" required class="w-full p-3 border border-gray-300 rounded-md" />
                    <input type="text" name="workspace_id" id="user-workspace-id" placeholder="Workspace ID" required class="w-full p-3 border border-gray-300 rounded-md" />
                    <input type="hidden" name="role" value="user" />
                    <button type="submit" class="w-full py-3 bg-green-500 text-white font-semibold rounded-md">Register as User</button>
                </form>

                <p class="text-center text-sm mt-4">
                    Already have an account? <a href="login.php" class="text-blue-500">Login</a>
                </p>
            </div>
        </div>

        <!-- Right Section -->
        <div class="flex-1 relative bg-blue-500 text-white">
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-4xl font-semibold z-10">Welcome</div>
            <img src="./img/bgSubscription.jpg" alt="Welcome" class="w-full h-full object-cover" />
        </div>
    </div>

    <!-- Theme Toggle Switch -->
    <div class="theme-switch-wrapper">
        <label class="theme-switch" for="checkbox">
            <input type="checkbox" id="checkbox" />
            <div class="slider round">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
            </div>
        </label>
    </div>

 <script src="scripts/register.js"></script>
</body>
</html>
