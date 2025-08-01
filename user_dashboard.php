<?php
session_start();

$host = 'localhost';
$dbname = 'task_management';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if (!isset($_SESSION['workspace_id'], $_SESSION['user_id'])) {
    die("Access denied.");
}

$workspace_id = $_SESSION['workspace_id'];
$user_id = $_SESSION['user_id'];

// Update task status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['task_id']) && is_numeric($_POST['task_id'])) {
        $task_id = (int)$_POST['task_id'];
        if (isset($_POST['mark_stuck'])) {
            $pdo->prepare("UPDATE tasks SET status = 'stuck', updated_at = NOW() WHERE id = ? AND assigned_to = ?")
                ->execute([$task_id, $user_id]);
        } elseif (isset($_POST['mark_done'])) {
            $pdo->prepare("UPDATE tasks SET status = 'done', updated_at = NOW() WHERE id = ? AND assigned_to = ?")
                ->execute([$task_id, $user_id]);
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch tasks
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE workspace_id = ? AND assigned_to = ? ORDER BY updated_at DESC");
$stmt->execute([$workspace_id, $user_id]);
$tasks = $stmt->fetchAll();

function countUserTasksByStatus($pdo, $status, $user_id, $workspace_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE status = ? AND assigned_to = ? AND workspace_id = ?");
    $stmt->execute([$status, $user_id, $workspace_id]);
    return (int)$stmt->fetchColumn();
}

$total   = count($tasks);
$done    = countUserTasksByStatus($pdo, 'done', $user_id, $workspace_id);
$working = countUserTasksByStatus($pdo, 'in_progress', $user_id, $workspace_id);
$stuck   = countUserTasksByStatus($pdo, 'stuck', $user_id, $workspace_id);

$userStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Inter', sans-serif; }
    .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background: rgba(0,0,0,0.4); }
    .modal-content { background: #fff; margin: 10% auto; padding: 2rem; width: 90%; max-width: 600px; border-radius: 0.5rem; }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex min-h-screen">
    
  <aside class="w-64 bg-white shadow p-4">
      <h2 class="text-xl font-semibold mb-6">User Panel</h2>
      <ul class="space-y-4">
        <li><a href="#" class="text-blue-600">Dashboard</a></li>
        <li><a href="#task-list" class="hover:text-blue-600">Tasks</a></li>
      </ul>
      <ul class="mb-8">
        <li><a href="Login.php" class="bg-red-600 text-white block py-2 px-4 rounded hover:bg-red-700 transition-colors text-center">Logout</a></li>
    </ul>
    </aside>

    <main class="flex-1 p-6">
      <h1 class="text-2xl font-semibold mb-6">Welcome, <?php echo htmlspecialchars($user['username']); ?></h1>

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

      <div id="task-list" class="bg-white p-4 shadow rounded overflow-x-auto">
        <h2 class="text-xl font-semibold mb-4">Your Tasks</h2>
        <table class="min-w-full table-auto">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-3 text-left">Task Name</th>
              <th class="p-3 text-left">Status</th>
              <th class="p-3 text-left">Due Date</th>
              <th class="p-3 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tasks as $task): ?>
              <tr class="border-t">
                <td class="p-3"><a href="#" onclick="showTaskDescription(<?php echo $task['id']; ?>)" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($task['task_name']); ?></a></td>
                <td class="p-3"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></td>
                <td class="p-3"><?php echo htmlspecialchars($task['due_date']); ?></td>
                <td class="p-3 flex gap-2">
                  <form method="POST">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>" />
                    <button name="mark_stuck" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm">Stuck</button>
                    <button name="mark_done" class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-sm"<?php if ($task['status'] === 'done') echo ' disabled'; ?>>Done</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($tasks)): ?>
              <tr><td colspan="4" class="text-center text-gray-500 py-4">No tasks assigned yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      <div id="taskDescriptionModal" class="modal">
        <div class="modal-content">
          <h2 class="text-xl font-semibold mb-2">Task Description</h2>
          <div id="taskDescriptionContent" class="mb-4 text-gray-700">Loading...</div>
          <button onclick="closeTaskDescriptionModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
        </div>
      </div>
    </main>
  </div>

  <script>
    function showTaskDescription(taskId) {
      const modal = document.getElementById('taskDescriptionModal');
      const content = document.getElementById('taskDescriptionContent');
      modal.style.display = 'block';

      fetch('get_task_description.php?id=' + taskId)
        .then(res => res.text())
        .then(data => content.innerHTML = data)
        .catch(() => content.innerHTML = 'Error loading description.');
    }

    function closeTaskDescriptionModal() {
      document.getElementById('taskDescriptionModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('taskDescriptionModal');
      if (event.target === modal) modal.style.display = 'none';
    };
  </script>
</body>
</html>
