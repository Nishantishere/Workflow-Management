<?php
// Connect to DB
$host = "localhost";
$user = "root";
$password = "";
$dbname = "task_management";
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get task ID from URL
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    die("No task ID provided.");
}

// Fetch task
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    die("Task not found.");
}

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $update_stmt = $conn->prepare("UPDATE tasks SET task_name = ?, description = ?, status = ? WHERE id = ?");
    $update_stmt->bind_param("sssi", $task_name, $description, $status, $task_id);
    $update_stmt->execute();

    // Optional: Redirect to dashboard after update
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: url('https://images.unsplash.com/photo-1542281286-9e0a16bb7366?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center text-white">

    <div class="glass rounded-3xl p-10 max-w-lg w-full shadow-2xl">
        <h2 class="text-3xl font-bold mb-6 text-center">Edit Task</h2>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block mb-2 text-sm font-medium">Task Name</label>
                <input type="text" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>" required class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-white">
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium">Description</label>
                <textarea name="description" required class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-white"><?php echo htmlspecialchars($task['description']); ?></textarea>
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium">Status</label>
                <select name="status" class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white focus:outline-none focus:ring-2 focus:ring-white">
                    <option value="pending" <?php if ($task['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="in_progress" <?php if ($task['status'] === 'in_progress') echo 'selected'; ?>>In Progress</option>
                    <option value="completed" <?php if ($task['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>

            <button type="submit" class="w-full py-3 bg-white/30 hover:bg-white/50 rounded-xl text-white font-semibold transition duration-300">Save Changes</button>
        </form>
    </div>

</body>
</html>
