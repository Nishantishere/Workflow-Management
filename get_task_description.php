<?php
// Include the database connection file
$host = 'localhost';
$dbname = 'task_management';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to connect to database: " . $e->getMessage());
}

// Check if the task ID is provided in the query string
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $task_id = $_GET['id'];

    try {
        // Prepare the SQL query to fetch the task description
        $sql = "SELECT description FROM tasks WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$task_id]);
        $task = $stmt->fetch();

        // Check if the task exists
        if ($task) {
            // Output the task description
            echo htmlspecialchars($task['description']);
        } else {
            echo "Task not found.";
        }
    } catch (PDOException $e) {
        echo "Error fetching task description: " . $e->getMessage();
    }
} else {
    echo "Invalid task ID.";
}
?>
