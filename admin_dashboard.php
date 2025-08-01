<?php
// Start the session at the very beginning
session_start();

// Database Connection
$host = 'localhost';
$dbname = 'task_management';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Error handling
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Fetch associative arrays
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Failed to connect to the database.");
}

// Check if workspace_id and user_id are set in session
if (!isset($_SESSION['workspace_id'])) {
    die("<div style='display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f3f4f6;'>
            <div style='background-color: #fff; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); text-align: center;'>
                <p style='color: red; margin-bottom: 1rem;'>Workspace not found. Please <a href='Login.php' style='background-color: #3b82f6; color: white; display: inline-block; padding: 0.5rem 1rem; border-radius: 0.25rem; text-align: center; text-decoration: none; transition: background-color 0.15s ease-in-out;'>log in</a>.</p>
            </div>
        </div>");
}
if (!isset($_SESSION['user_id'])) {
    die("<div style='display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f3f4f6;'>
            <div style='background-color: #fff; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); text-align: center;'>
                <p style='color: red; margin-bottom: 1rem;'>User not found. Please <a href='Login.php' style='background-color: #3b82f6; color: white; display: inline-block; padding: 0.5rem 1rem; border-radius: 0.25rem; text-align: center; text-decoration: none; transition: background-color 0.15s ease-in-out;'>log in</a> again.</p>
            </div>
        </div>");
}

$workspace_id = $_SESSION['workspace_id'];  // Get the workspace ID from session
$user_id = $_SESSION['user_id'];  // Get the user ID from session

// Ensure workspace_id is treated correctly (it might be a string or integer)
$workspace_id = (string) $workspace_id;  // Convert to string if necessary (e.g., if it's alphanumeric like 'QD40')

// Handle form submission for creating a new task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $task_name = $_POST['task_name'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';
    $status = $_POST['status'] ?? 'in_progress';
    $due_date = $_POST['due_date'] ?? '';
    $description = $_POST['description'] ?? '';
    // IMPORTANT:  Always use the session value for assigned_by.  Remove the hidden input.
    $assigned_by = $user_id;
    
    // Basic validation
    if (!empty($task_name)) {
        try {
            $sql = "INSERT INTO tasks (task_name, assigned_to, assigned_by, status, due_date, description, workspace_id, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$task_name, $assigned_to, $assigned_by, $status, $due_date, $description, $workspace_id]);
            
            if ($result) {
                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (PDOException $e) {
            $error_message = "Error creating task: " . $e->getMessage();
        }
    } else {
        $error_message = "Task name is required.";
    }
}

// Handle form submission for updating a task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $task_id = $_POST['task_id'] ?? '';
    $task_name = $_POST['task_name'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';
    $status = $_POST['status'] ?? 'in_progress';
    $due_date = $_POST['due_date'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Basic validation
    if (!empty($task_name) && !empty($task_id)) {
        try {
            $sql = "UPDATE tasks SET 
                    task_name = ?, 
                    assigned_to = ?, 
                    status = ?, 
                    due_date = ?, 
                    description = ?, 
                    updated_at = NOW() 
                    WHERE id = ? AND workspace_id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$task_name, $assigned_to, $status, $due_date, $description, $task_id, $workspace_id]);
            
            if ($result) {
                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (PDOException $e) {
            $error_message = "Error updating task: " . $e->getMessage();
        }
    } else {
        $error_message = "Task ID and name are required.";
    }
}

// Handle task deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id_to_delete = $_POST['task_id'];
    
    try {
        $sql = "DELETE FROM tasks WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$task_id_to_delete]);
        
        if ($result) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error_message = "Failed to delete task.";
        }
    } catch (PDOException $e) {
        $error_message = "Error deleting task: " . $e->getMessage();
    }
}

// Filtering logic for tasks (if needed)
$where = [];
$params = [];

if (!empty($_GET['q'])) {
    $where[] = "task_name LIKE ?";
    $params[] = "%" . $_GET['q'] . "%";
}

if (!empty($_GET['status'])) {
    $where[] = "status = ?";
    $params[] = $_GET['status'];
}

$whereSQL = $where ? "WHERE workspace_id = ? AND " . implode(" AND ", $where) : "WHERE workspace_id = ?";

// Fetch tasks from the database
$sql = "SELECT * FROM tasks $whereSQL ORDER BY updated_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$workspace_id], $params));  // Execute the query
$tasks = $stmt->fetchAll();

// Fetch the 4 most recent tasks for the top cards
$sql = "SELECT * FROM tasks WHERE workspace_id = ? ORDER BY created_at DESC LIMIT 4";
$stmt = $pdo->prepare($sql);
$stmt->execute([$workspace_id]);
$recent_tasks = $stmt->fetchAll();

// Helper function for counting tasks by status
function countTasksByStatus(PDO $pdo, string $status, $workspace_id): int {
    $sql = "SELECT COUNT(*) FROM tasks WHERE status = ? AND workspace_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $workspace_id]);
    $count = (int)$stmt->fetchColumn();
    return $count;
}

// Get task counts
$total = count($tasks);
$done = countTasksByStatus($pdo, 'completed', $workspace_id); // matches ENUM value
$working = countTasksByStatus($pdo, 'in_progress', $workspace_id);
$stuck = countTasksByStatus($pdo, 'stuck', $workspace_id); // Get the count for 'stuck' tasks

// Fetch users for the workspace to populate the assigned_to dropdown
$sql = "SELECT id, username, email, joined_at FROM users WHERE workspace_id = ? AND is_approved = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$workspace_id]);
$users = $stmt->fetchAll();

// Get task completion data for the last 7 days for chart
$sql = "SELECT DATE(created_at) as date, COUNT(*) as total, 
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM tasks 
        WHERE workspace_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date";
$stmt = $pdo->prepare($sql);
$stmt->execute([$workspace_id]);
$chart_data = $stmt->fetchAll();

// Format chart data for JavaScript
$dates = [];
$completed_tasks = [];
$total_tasks = [];

foreach ($chart_data as $data) {
    $dates[] = $data['date'];
    $completed_tasks[] = (int)$data['completed'];
    $total_tasks[] = (int)$data['total'];
}

// Get login dates for the calendar (simulated for demo)
// In a real app, you would fetch actual login data from your database
$login_dates = [];
for ($i = 1; $i <= 28; $i++) {
    // Randomly mark some days as logged in
    if (rand(0, 2) > 0) { // 2/3 chance of being marked
        $login_dates[] = date('Y-m-d', strtotime("-$i days"));
    }
}

// Function to get task by ID
function getTaskById(PDO $pdo, $task_id, $workspace_id) {
    $sql = "SELECT * FROM tasks WHERE id = ? AND workspace_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$task_id, $workspace_id]);
    return $stmt->fetch();
}

// If editing a task, get the task data
$task_to_edit = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $task_to_edit = getTaskById($pdo, $_GET['edit'], $workspace_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body {
        font-family: 'Inter', sans-serif;
      }
      
      /* Modal styles */
      .modal {
        display: none;
        position: fixed;
        z-index: 50;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
      }
      
      .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
      }
      
      /* Calendar styles */
      .calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
      }
      
      .calendar-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
      }
      
      .day-header {
        font-weight: bold;
        text-align: center;
        padding: 4px 0;
      }
      
      /* Hide scrollbar for Chrome, Safari and Opera */
      .no-scrollbar::-webkit-scrollbar {
        display: none;
      }
      
      /* Hide scrollbar for IE, Edge and Firefox */
      .no-scrollbar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
      }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex min-h-screen relative">
    <!-- Toggle sidebar button -->
    <button id="sidebarToggle" class="fixed top-4 left-4 z-40 bg-blue-600 text-white p-2 rounded-full shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow p-4 h-screen fixed left-0 top-0 z-30 transition-transform duration-300 transform flex flex-col">
    <h2 class="text-xl font-semibold mb-6 mt-12">Admin Panel</h2>
    <ul class="space-y-4 flex-grow">
        <li><a href="#" class="text-blue-600 hover:text-blue-800 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Dashboard</a></li>
        <li><a href="#task-list" class="hover:text-blue-600 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Tasks</a></li>
        <li><a href="#user-list" class="hover:text-blue-600 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Users</a></li>
        <li><a href="admin_workflows.php" class="hover:text-blue-600 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Workflows</a></li>
    </ul>
    <ul class="mb-4">
        <li><a href="Login.php" class="bg-red-600 text-white block py-2 px-4 rounded hover:bg-red-700 transition-colors text-center">Logout</a></li>
    </ul>
</aside>

    <!-- Main content -->
    <main id="mainContent" class="flex-1 p-6 ml-64 transition-all duration-300">
        <h1 class="text-2xl font-semibold mb-4 mt-8">Task Overview</h1>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap">
            <!-- Left content area (75%) -->
            <div class="w-full lg:w-3/4 pr-0 lg:pr-4">
                <!-- Status cards -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white p-4 shadow rounded text-center">
                        <p class="text-sm text-gray-500">Total Tasks</p>
                        <p class="text-2xl font-bold"><?php echo $total; ?></p>
                    </div>
                    <div class="bg-white p-4 shadow rounded text-center">
                        <p class="text-sm text-gray-500">Completed</p>
                        <p class="text-2xl text-green-600 font-bold"><?php echo $done; ?></p>
                    </div>
                    <div class="bg-white p-4 shadow rounded text-center">
                        <p class="text-sm text-gray-500">In Progress</p>
                        <p class="text-2xl text-yellow-500 font-bold"><?php echo $working; ?></p>
                    </div>
                    <div class="bg-white p-4 shadow rounded text-center">
                        <p class="text-sm text-gray-500">Stuck</p>
                        <p class="text-2xl text-red-600 font-bold"><?php echo $stuck; ?></p>
                    </div>
                </div>

                <!-- Recent tasks cards -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold mb-4">Recent Tasks</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php foreach ($recent_tasks as $index => $task): ?>
                            <div class="bg-white p-4 shadow rounded-lg border-l-4 
                                <?php 
                                switch ($task['status']) {
                                    case 'completed': echo 'border-green-500'; break;
                                    case 'in_progress': echo 'border-yellow-500'; break;
                                    case 'stuck': echo 'border-red-500'; break;
                                    default: echo 'border-gray-300';
                                }
                                ?>">
                                <h3 class="font-semibold mb-2 truncate" title="<?php echo htmlspecialchars($task['task_name']); ?>">
                                    <?php echo htmlspecialchars($task['task_name']); ?>
                                </h3>
                                <p class="text-sm text-gray-500 mb-2">
                                    Due: <?php echo !empty($task['due_date']) ? htmlspecialchars($task['due_date']) : 'Not set'; ?>
                                </p>
                                <div class="flex justify-between items-center">
                                    <span class="inline-block px-2 py-1 text-xs rounded
                                        <?php
                                        switch ($task['status']) {
                                            case 'completed': echo 'bg-green-100 text-green-700'; break;
                                            case 'in_progress': echo 'bg-yellow-100 text-yellow-700'; break;
                                            case 'stuck': echo 'bg-red-100 text-red-700'; break;
                                            default: echo 'bg-gray-200 text-gray-600';
                                        }
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                    <button onclick="editTask(<?php echo $task['id']; ?>)" class="text-blue-500 hover:text-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($recent_tasks) === 0): ?>
                            <div class="col-span-4 bg-white p-4 shadow rounded text-center">
                                <p class="text-gray-500">No recent tasks found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button onclick="toggleTaskModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-4 transition-colors">
                    + Create Task
                </button>

                <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
                    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-2">
                        <label for="q" class="text-sm font-medium block mb-1 sm:mb-0">Search:</label>
                        <input name="q" id="q" type="text" placeholder="Search by task..." class="p-2 border rounded w-full sm:w-auto" value="<?php echo $_GET['q'] ?? ''; ?>">
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-2">
                        <label for="status" class="text-sm font-medium block mb-1 sm:mb-0">Status:</label>
                        <select name="status" id="status" class="p-2 border rounded w-full sm:w-auto">
                            <option value="">All Status</option>
                            <option value="in_progress" <?php echo ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="stuck" <?php echo ($_GET['status'] ?? '') === 'stuck' ? 'selected' : ''; ?>>Stuck</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition-colors">Filter</button>
                </form>

                <div id="task-list" class="bg-white p-4 shadow rounded overflow-x-auto mb-8">
                    <h2 class="text-xl font-semibold mb-4">Task List</h2>
                    <table class="min-w-full table-auto rounded-md shadow-md">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 text-left">Task Name</th>
                                <th class="p-3 text-left">Assigned To</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Due Date</th>
                                <th class="p-3 text-left">Last Updated</th>
                                <th class="p-3 text-left">Actions</th> </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php if (count($tasks) > 0): ?>
                                <?php foreach ($tasks as $task): ?>
                                    <tr class="border-t hover:bg-gray-50 transition-colors">
                                        <td class="p-3"><?php echo htmlspecialchars($task['task_name']); ?></td>
                                        <td class="p-3">
                                            <?php
                                            // Look up the username based on the assigned_to id
                                            if (!empty($task['assigned_to'])) {
                                                $found_user = false;
                                                foreach ($users as $user) {
                                                    if ($user['id'] == $task['assigned_to']) {
                                                        echo htmlspecialchars($user['username']);
                                                        $found_user = true;
                                                        break;
                                                    }
                                                }
                                                if (!$found_user) {
                                                    echo htmlspecialchars($task['assigned_to']);
                                                }
                                            } else {
                                                echo "Not Assigned";
                                            }
                                            ?>
                                        </td>
                                        <td class="p-3">
                                            <span class="inline-block px-2 py-1 text-xs rounded
                                                <?php
                                                switch ($task['status']) {
                                                    case 'completed': echo 'bg-green-100 text-green-700'; break;                                            
                                                    case 'in_progress': echo 'bg-yellow-100 text-yellow-700'; break;
                                                    case 'stuck': echo 'bg-red-100 text-red-700'; break;
                                                    default: echo 'bg-gray-200 text-gray-600';
                                                }
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="p-3"><?php echo htmlspecialchars($task['due_date']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($task['updated_at']); ?></td>
                                        <td class="p-3 flex gap-2">
                                            <button onclick="editTask(<?php echo $task['id']; ?>)" class="bg-blue-500 hover:bg-blue-700 text-white px-2 py-1 rounded transition-colors text-xs">Edit</button>
                                            <form method="POST" action="" style="display: inline-block;">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" name="delete_task" class="bg-red-500 hover:bg-red-700 text-white px-2 py-1 rounded transition-colors text-xs" onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-3 text-center text-gray-500">No tasks found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="user-list" class="bg-white p-4 shadow rounded overflow-x-auto mb-8">
                    <h2 class="text-xl font-semibold mb-4">Users in this Workspace</h2>
                    <table class="min-w-full table-auto rounded-md shadow-md">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 text-left">Username</th>
                                <th class="p-3 text-left">Email</th>
                                <th class="p-3 text-left">Joined At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php foreach ($users as $user): ?>
                                <tr class="border-t hover:bg-gray-50 transition-colors">
                                    <td class="p-3"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($user['joined_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pending Users Section -->
<div id="pending-users" class="mt-10 p-6 bg-white shadow rounded">
    <h2 class="text-xl font-semibold mb-4">Pending User Approvals</h2>

    <?php
    // Handle approval or rejection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'])) {
        $action_user_id = (int)$_POST['user_id'];
        $action = $_POST['user_action'];

        if ($action_user_id && in_array($action, ['approve', 'reject'])) {
            if ($action === 'approve') {
                $updateSql = "UPDATE users SET is_approved = 1 WHERE id = ?";
            } else {
                $updateSql = "DELETE FROM users WHERE id = ?";
            }

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$action_user_id]);

            echo '<div class="mb-4 text-green-600">User ' . htmlspecialchars($action) . 'd successfully.</div>';
        }
    }

    // Fetch unapproved users
    $pendingUsersSql = "SELECT id, username, email, joined_at FROM users WHERE is_approved = 0 AND workspace_id = ?";
    $pendingStmt = $pdo->prepare($pendingUsersSql);
    $pendingStmt->execute([$workspace_id]);
    $pendingUsers = $pendingStmt->fetchAll();
    ?>

    <?php if (count($pendingUsers) > 0): ?>
        <table class="min-w-full table-auto rounded-md shadow-md">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Username</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Joined At</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php foreach ($pendingUsers as $user): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($user['joined_at']); ?></td>
                        <td class="p-3 flex gap-2">
                            <form method="POST" onsubmit="return confirm('Approve this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="user_action" value="approve" class="bg-green-500 text-white px-3 py-1 rounded">Approve</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Reject and remove this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="user_action" value="reject" class="bg-red-500 text-white px-3 py-1 rounded">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-gray-500">No pending users to approve.</p>
    <?php endif; ?>
</div>
            </div>

            
            
            <!-- Right sidebar (25%) -->
            <div class="w-full lg:w-1/4 mt-6 lg:mt-0">
                <div class="bg-white shadow rounded p-4 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Calendar</h2>
                    <div class="text-center mb-2">
                        <span id="currentMonth" class="font-semibold"></span>
                    </div>
                    <div class="calendar">
                        <div class="day-header">Su</div>
                        <div class="day-header">Mo</div>
                        <div class="day-header">Tu</div>
                        <div class="day-header">We</div>
                        <div class="day-header">Th</div>
                        <div class="day-header">Fr</div>
                        <div class="day-header">Sa</div>
                        <!-- Calendar days will be filled by JavaScript -->
                    </div>
                </div>

                <div class="bg-white shadow rounded p-4">
                    <h2 class="text-lg font-semibold mb-4">Task Completion</h2>
                    <div class="relative" style="height: 200px;">
                        <canvas id="taskCompletionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        
    </main>

    <!-- Create/Edit Task Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold" id="modalTitle">Create New Task</h2>
                <button onclick="toggleTaskModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form method="POST" action="" id="taskForm">
                <input type="hidden" id="task_id" name="task_id" value="">
                <div class="space-y-4">
                    <div>
                        <label for="task_name" class="block text-sm font-medium text-gray-700 mb-1">Task Name <span class="text-red-500">*</span></label>
                        <input type="text" id="task_name" name="task_name" required class="w-full p-2 border rounded">
                    </div>
                    
                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                        <select id="assigned_to" name="assigned_to" class="w-full p-2 border rounded">
                            <option value="">Not Assigned</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full p-2 border rounded">
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>  
                            <option value="stuck">Stuck</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input type="date" id="due_date" name="due_date" class="w-full p-2 border rounded">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" class="w-full p-2 border rounded"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="toggleTaskModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">Cancel</button>
                        <button type="submit" id="taskSubmitBtn" name="create_task" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">Create Task</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    function toggleSidebar() {
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            mainContent.classList.remove('ml-0');
            mainContent.classList.add('ml-64');
        } else {
            sidebar.classList.add('-translate-x-full');
            mainContent.classList.remove('ml-64');
            mainContent.classList.add('ml-0');
        }
    }
    
    sidebarToggle.addEventListener('click', toggleSidebar);
    
    // Check if sidebar should be hidden on mobile by default
    function checkMobileView() {
        if (window.innerWidth < 768) {
            sidebar.classList.add('-translate-x-full');
            mainContent.classList.remove('ml-64');
            mainContent.classList.add('ml-0');
        } else {
            sidebar.classList.remove('-translate-x-full');
            mainContent.classList.add('ml-64');
            mainContent.classList.remove('ml-0');
        }
    }
    
    // Call on page load
    checkMobileView();
    
    // Call when window is resized
    window.addEventListener('resize', checkMobileView);
    
    // Modal toggle function
    const taskModal = document.getElementById('taskModal');
    const taskForm = document.getElementById('taskForm');
    const modalTitle = document.getElementById('modalTitle');
    const taskSubmitBtn = document.getElementById('taskSubmitBtn');
    
    function toggleTaskModal() {
        if (taskModal.style.display === 'block') {
            taskModal.style.display = 'none';
        } else {
            taskModal.style.display = 'block';
        }
    }
    
    // Close modal if clicked outside
    window.onclick = function(event) {
        if (event.target === taskModal) {
            taskModal.style.display = 'none';
        }
    }
    
    // Edit task function - now actually loads the task data into the form
    function editTask(taskId) {
        // Fetch task data using AJAX
        fetch(`?edit=${taskId}`)
            .then(response => {
                // For demo purposes, we'll just reload the page with the edit parameter
                // In a real application, you would handle this with AJAX
                window.location.href = `?edit=${taskId}`;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Initialize the calendar
    function initCalendar() {
        const now = new Date();
        const currentMonth = now.toLocaleString('default', { month: 'long', year: 'numeric' });
        document.getElementById('currentMonth').textContent = currentMonth;
        
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).getDay();
        const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
        
        // Login dates from PHP (converted to JS array)
        const loginDates = <?php echo json_encode($login_dates); ?>;
        
        // Clear existing calendar days
        const calendarEl = document.querySelector('.calendar');
        const dayHeaders = calendarEl.querySelectorAll('.day-header');
        while (calendarEl.childElementCount > 7) {
            calendarEl.removeChild(calendarEl.lastChild);
        }
        
        // Add empty spaces for days before the 1st of the month
        for (let i = 0; i < firstDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day bg-gray-100';
            calendarEl.appendChild(emptyDay);
        }
        
        // Add the days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayEl = document.createElement('div');
            dayEl.textContent = day;
            dayEl.className = 'calendar-day border';
            
            // Check if this day is today
            const currentDate = new Date(now.getFullYear(), now.getMonth(), day);
            const dateString = currentDate.toISOString().split('T')[0];
            
            if (day === now.getDate()) {
                dayEl.classList.add('bg-blue-100', 'font-bold');
            }
            
            // Check if this day has a login record
            if (loginDates.includes(dateString)) {
                dayEl.classList.add('bg-green-100');
            }
            
            calendarEl.appendChild(dayEl);
        }
    }
    
    // Initialize the task completion chart
    function initTaskChart() {
        const ctx = document.getElementById('taskCompletionChart').getContext('2d');
        
        // Data from PHP
        const dates = <?php echo json_encode($dates); ?>;
        const completedTasks = <?php echo json_encode($completed_tasks); ?>;
        const totalTasks = <?php echo json_encode($total_tasks); ?>;
        
        // Use dummy data if we don't have enough real data
        const chartDates = dates.length > 0 ? dates : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const chartCompleted = completedTasks.length > 0 ? completedTasks : [3, 4, 2, 5, 6, 3, 4];
        const chartTotal = totalTasks.length > 0 ? totalTasks : [5, 7, 4, 8, 9, 5, 7];
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartDates,
                datasets: [
                    {
                        label: 'Total Tasks',
                        data: chartTotal,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Completed Tasks',
                        data: chartCompleted,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Initialize page components
    document.addEventListener('DOMContentLoaded', function() {
        initCalendar();
        initTaskChart();
        
        // If we're in edit mode, populate the form and change the modal
        <?php if ($task_to_edit): ?>
        // Set the form for editing
        document.getElementById('task_id').value = "<?php echo $task_to_edit['id']; ?>";
        document.getElementById('task_name').value = "<?php echo addslashes($task_to_edit['task_name']); ?>";
        document.getElementById('assigned_to').value = "<?php echo $task_to_edit['assigned_to']; ?>";
        document.getElementById('status').value = "<?php echo $task_to_edit['status']; ?>";
        document.getElementById('due_date').value = "<?php echo $task_to_edit['due_date']; ?>";
        document.getElementById('description').value = "<?php echo addslashes($task_to_edit['description']); ?>";
        
        // Change button and title
        modalTitle.textContent = "Edit Task";
        taskSubmitBtn.textContent = "Update Task";
        taskSubmitBtn.name = "update_task";
        
        // Show the modal
        taskModal.style.display = "block";
        <?php endif; ?>
    });
</script>
</body>
</html>