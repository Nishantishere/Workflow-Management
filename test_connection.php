<?php
// Display all PHP errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your config file
require_once 'includes/config.php';

// Test the connection
try {
    // Check if $conn exists
    if (isset($conn) && $conn instanceof PDO) {
        echo "<h2>Database connection is working!</h2>";
        
        // Try a simple query to further verify
        $stmt = $conn->query("SELECT 1");
        if ($stmt) {
            echo "<p>Successfully executed a test query.</p>";
        }
        
        // Check if tables exist
        $tables = ['admins', 'users'];
        echo "<h3>Checking tables:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<li>Table '$table' exists ✅</li>";
            } else {
                echo "<li>Table '$table' does NOT exist ❌</li>";
            }
        }
        echo "</ul>";
        
        // Display database name for verification
        echo "<p>Connected to database: <strong>{$db}</strong></p>";
    } else {
        echo "<h2>Error: Database connection variable (\$conn) is not available.</h2>";
        echo "<p>Check your config.php file.</p>";
    }
} catch (PDOException $e) {
    echo "<h2>Error testing database connection:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>