<?php
// Display all PHP errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database and Tables Setup</h1>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = ''; // use your MySQL password here if any
$dbname = 'task_management';

try {
    // Connect without selecting a database first
    $conn = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>Connected to MySQL server successfully.</p>";
    
    // Check if database exists, if not create it
    try {
        $conn->query("USE `$dbname`");
        echo "<p>Database '$dbname' already exists.</p>";
    } catch (PDOException $e) {
        // Create the database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        echo "<p>Database '$dbname' created successfully.</p>";
        
        // Select the new database
        $conn->query("USE `$dbname`");
    }
    
    // Now create tables
    
    // Create admins table
    $conn->query("
    CREATE TABLE IF NOT EXISTS `admins` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `email` varchar(100) NOT NULL,
      `password` varchar(255) NOT NULL,
      `workspace_id` varchar(8) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`),
      UNIQUE KEY `workspace_id` (`workspace_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>Table 'admins' created or already exists.</p>";
    
    // Create users table
    $conn->query("
    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `email` varchar(100) NOT NULL,
      `password` varchar(255) NOT NULL,
      `role` varchar(20) NOT NULL DEFAULT 'user',
      `workspace_id` varchar(8) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>Table 'users' created or already exists.</p>";
    
    echo "<div style='color: green; font-weight: bold;'>✅ Database and tables setup completed successfully!</div>";
    
    // Check if config.php exists and create it if not
    $configDir = __DIR__ . '/includes';
    $configFile = $configDir . '/config.php';
    
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
        echo "<p>Created 'includes' directory.</p>";
    }
    
    if (!file_exists($configFile)) {
        $config = '<?php
// Database configuration
$host = \'localhost\';
$db = \'' . $dbname . '\';
$user = \'' . $user . '\';
$pass = \'' . $pass . '\'; // use your MySQL password here if any

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display a user-friendly message
    die("Database connection failed. Please try again later.");
}
?>';
        file_put_contents($configFile, $config);
        echo "<p>Created 'config.php' file.</p>";
    } else {
        echo "<p>'config.php' file already exists.</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</div>";
}

// Include a link to go back to the register page
echo "<p><a href='register.php'>Go to Registration Page</a></p>";
echo "<p><a href='test_connection.php'>Test Database Connection</a></p>";
?>