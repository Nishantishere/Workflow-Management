<?php
session_start(); //  [1]

// Database Connection (same as in admin 2.txt)
$host = 'localhost'; //  [2]
$dbname = 'task_management'; //  [2]
$user = 'root'; //  [2]
$pass = ''; //  [2]

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass); //  [2]
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //  [2]
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //  [2]
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage()); //  [3]
    die("Failed to connect to the database."); //  [4]
}

// Check session variables
if (!isset($_SESSION['workspace_id']) || !isset($_SESSION['user_id'])) { //  [4]
    // Redirect to login or show error
    header("Location: Login.php"); //  [5]
    exit;
}

$workspace_id = $_SESSION['workspace_id']; //  [8]
$message = ''; // For success/error messages

// Handle Create Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_status'])) {
    $name = trim($_POST['name']);
    $color = $_POST['color'];
    // For sort_order, you might want to get the current max sort_order for this workspace and add 1
    $stmt_max_order = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM statuses WHERE workspace_id = ?");
    $stmt_max_order->execute([$workspace_id]);
    $max_order_row = $stmt_max_order->fetch();
    $sort_order = ($max_order_row && $max_order_row['max_order'] !== null) ? $max_order_row['max_order'] + 1 : 0;

    if (!empty($name) && !empty($color)) {
        try {
            $sql = "INSERT INTO statuses (name, color, sort_order, workspace_id, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $color, $sort_order, $workspace_id]);
            $message = "<div class='bg-green-100 border-green-500 text-green-700 p-3 mb-3'>Status created successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='bg-red-100 border-red-500 text-red-700 p-3 mb-3'>Error creating status: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='bg-yellow-100 border-yellow-500 text-yellow-700 p-3 mb-3'>Name and color are required.</div>";
    }
}

// Handle Update Status (you'll need a way to trigger this, e.g., an edit form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status_id = $_POST['status_id'];
    $name = trim($_POST['name']);
    $color = $_POST['color'];
    $sort_order = (int)$_POST['sort_order']; // Make sure it's an integer

    if (!empty($name) && !empty($color) && !empty($status_id)) {
        try {
            $sql = "UPDATE statuses SET name = ?, color = ?, sort_order = ?, updated_at = NOW() WHERE id = ? AND workspace_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $color, $sort_order, $status_id, $workspace_id]);
            $message = "<div class='bg-green-100 border-green-500 text-green-700 p-3 mb-3'>Status updated successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='bg-red-100 border-red-500 text-red-700 p-3 mb-3'>Error updating status: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='bg-yellow-100 border-yellow-500 text-yellow-700 p-3 mb-3'>Status ID, Name, and color are required for update.</div>";
    }
}

// Handle Delete Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_status'])) {
    $status_id_to_delete = $_POST['status_id_delete'];
    // Before deleting, check if any tasks are using this status.
    // If tasks are using it, you might want to prevent deletion or reassign tasks.
    // For now, we'll just delete.
    try {
        // You might want to prompt for confirmation (e.g., "Are you sure? This status might be in use.")
        // Also, consider what happens to tasks using this status if you deleted their `status_id` foreign key with ON DELETE RESTRICT
        $sql = "DELETE FROM statuses WHERE id = ? AND workspace_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status_id_to_delete, $workspace_id]);
        $message = "<div class='bg-green-100 border-green-500 text-green-700 p-3 mb-3'>Status deleted successfully!</div>";
    } catch (PDOException $e) {
        // Catch foreign key constraint violation if tasks are using this status and ON DELETE is RESTRICT
        if ($e->getCode() == '23000') { // Integrity constraint violation
            $message = "<div class='bg-red-100 border-red-500 text-red-700 p-3 mb-3'>Error deleting status: This status is currently in use by one or more tasks. Please reassign tasks before deleting.</div>";
        } else {
            $message = "<div class='bg-red-100 border-red-500 text-red-700 p-3 mb-3'>Error deleting status: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch existing statuses for the workspace
$stmt_statuses = $pdo->prepare("SELECT id, name, color, sort_order FROM statuses WHERE workspace_id = ? ORDER BY sort_order ASC");
$stmt_statuses->execute([$workspace_id]);
// ADDED DEBUGGING
if (!$stmt_statuses->execute([$workspace_id])) {
    print_r($stmt_statuses->errorInfo());
}
$statuses = $stmt_statuses->fetchAll();

// Logic for fetching a single status to edit (if 'edit_id' is in GET request)
$status_to_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt_edit = $pdo->prepare("SELECT id, name, color, sort_order FROM statuses WHERE id = ? AND workspace_id = ?");
    $stmt_edit->execute([$edit_id, $workspace_id]);
    $status_to_edit = $stmt_edit->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Workflow Statuses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Basic modal styles (optional for edit) */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen relative">
          
    <button id="sidebarToggle" class="fixed top-4 left-4 z-40 bg-blue-600 text-white p-2 rounded-full shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

        <aside id="sidebar" class="w-64 bg-white shadow p-4 h-screen fixed left-0 top-0 z-30 flex flex-col">
            <h2 class="text-xl font-semibold mb-6 mt-12">Admin Panel</h2>
            <ul class="space-y-4 flex-grow">
                <li><a href="admin_dashboard.php" class="hover:text-blue-600 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Dashboard</a></li>
                <li><a href="admin_dashboard.php#task-list" class="hover:text-blue-600 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Tasks</a></li>
                <li><a href="admin_dashboard.php#user-list" class="hover:text-blue-600 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Users</a></li>
                <li><a href="admin_workflows.php" class="text-blue-600 hover:text-blue-800 transition-colors block py-2 px-4 rounded hover:bg-gray-100">Workflows</a></li>
            </ul>
            <ul class="mb-4">
                <li><a href="Login.php" class="bg-red-600 text-white block py-2 px-4 rounded hover:bg-red-700 transition-colors text-center">Logout</a></li>
            </ul>
        </aside>

        <main class="flex-1 p-6 ml-64">
            <h1 class="text-2xl font-semibold mb-6">Manage Workflow Statuses</h1>

            <?php echo $message; // Display success/error messages ?>

            <div class="bg-white p-6 shadow rounded mb-6">
                <h2 class="text-xl font-semibold mb-4"><?php echo $status_to_edit ? 'Edit Status' : 'Create New Status'; ?></h2>
                <form method="POST" action="admin_workflows.php">
                    <?php if ($status_to_edit): ?>
                        <input type="hidden" name="status_id" value="<?php echo htmlspecialchars($status_to_edit['id']); ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Status Name</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($status_to_edit['name'] ?? ''); ?>" required class="w-full p-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <input type="color" name="color" id="color" value="<?php echo htmlspecialchars($status_to_edit['color'] ?? '#cccccc'); ?>" required class="w-full p-1 border rounded h-10">
                    </div>
                    <?php if ($status_to_edit): ?>
                    <div class="mb-4">
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" id="sort_order" value="<?php echo htmlspecialchars($status_to_edit['sort_order'] ?? '0'); ?>" required class="w-full p-2 border rounded">
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-end">
                        <?php if ($status_to_edit): ?>
                            <button type="submit" name="update_status" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update Status</button>
                            <a href="admin_workflows.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 ml-2">Cancel Edit</a>
                        <?php else: ?>
                            <button type="submit" name="create_status" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Create Status</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 shadow rounded">
    <h2 class="text-xl font-semibold mb-4">Existing Statuses</h2>
    <?php
    // Debugging: Output workspace ID and statuses array
    echo "Workspace ID: " . htmlspecialchars($workspace_id) . "<br>";
    var_dump($statuses);
    ?>
    <?php if (empty($statuses)): ?>
        <p class="text-gray-500">No statuses found for this workspace. Create one above!</p>
    <?php else: ?>
        <table class="min-w-full table-auto">
            <thead>
                <tr>
                    <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                    <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($statuses as $status): ?>
                    <tr>
                        <td class="p-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($status['sort_order']); ?></td>
                        <td class="p-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($status['name']); ?></td>
                        <td class="p-3 whitespace-nowrap">
                            <span style="background-color: <?php echo htmlspecialchars($status['color']); ?>; padding: 2px 8px; border-radius: 4px; color: <?php echo getContrastColor($status['color']); ?>;">
                                <?php echo htmlspecialchars($status['color']); ?>
                            </span>
                        </td>
                        <td class="p-3 whitespace-nowrap text-sm font-medium">
                            <a href="admin_workflows.php?edit_id=<?php echo $status['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <form method="POST" action="admin_workflows.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this status?');">
                                <input type="hidden" name="status_id_delete" value="<?php echo $status['id']; ?>">
                                <button type="submit" name="delete_status" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="mt-4 text-sm text-gray-600">Tip: To re-order statuses, edit each status and set its "Sort Order" value accordingly. Lower numbers appear first.</p>
    <?php endif; ?>
</div>

        </main>
    </div>

    <?php
        if (!function_exists('getContrastColor')) {
            function getContrastColor($hexcolor) {
                $hexcolor = ltrim($hexcolor, '#');
                if (strlen($hexcolor) == 3) {
                    $r = hexdec(substr($hexcolor, 0, 1) . substr($hexcolor, 0, 1));
                    $g = hexdec(substr($hexcolor, 1, 1) . substr($hexcolor, 1, 1));
                    $b = hexdec(substr($hexcolor, 2, 1) . substr($hexcolor, 2, 1));
                } else {
                    $r = hexdec(substr($hexcolor, 0, 2));
                    $g = hexdec(substr($hexcolor, 2, 2));
                    $b = hexdec(substr($hexcolor, 4, 2));
                }
                $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                return ($yiq >= 128) ? '#000000' : '#FFFFFF';
            }
        }
    ?>

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
</script>
</body>
</html>