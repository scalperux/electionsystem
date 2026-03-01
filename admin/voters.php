<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

requireLogin('admin');

$message = '';
$messageType = '';

$currentUserId = $_SESSION['user_id'] ?? 0;

// Handle create user
// Handle create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
    $username  = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'voter';

    if ($username === '' || $full_name === '' || $password === '') {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!in_array($role, ['admin', 'voter'], true)) {
        $message = "Invalid role selected.";
        $messageType = "error";
    } else {
        // Check if username exists
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCheck->bind_param("s", $username);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $message = "Username already taken. Choose a different username.";
            $messageType = "error";
        } else {
            // Create the user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $conn->prepare("
                INSERT INTO users (username, full_name, password, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmtInsert->bind_param("ssss", $username, $full_name, $hash, $role);
            if ($stmtInsert->execute()) {
                $message = "User created successfully as " . htmlspecialchars($role) . ".";
                $messageType = "success";
            } else {
                $message = "Error creating user.";
                $messageType = "error";
            }
            $stmtInsert->close();
        }

        // ✅ Close only once, here
        $stmtCheck->close();
    }
}
// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $userIdToDelete = (int) ($_POST['user_id'] ?? 0);

    if ($userIdToDelete === $currentUserId) {
        $message = "You cannot delete your own account while logged in.";
        $messageType = "error";
    } else {
        $stmtDel = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmtDel->bind_param("i", $userIdToDelete);
        if ($stmtDel->execute()) {
            $message = "User deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Error deleting user.";
            $messageType = "error";
        }
        $stmtDel->close();
    }
}

// Fetch all users
$users = [];
$res = $conn->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
}

$pageTitle = "Manage Users · Online Voting";
include __DIR__ . '/../includes/header.php';
$fullName = $_SESSION['full_name'] ?? 'Admin';
?>

<div class="app-header">
    <div>
        <div class="app-title">Manage Users</div>
        <div class="app-subtitle">
            Create admin and voter accounts, and manage existing users.
        </div>
    </div>
    <div class="row">
        <span class="badge">Admin</span>
        <a href="/online_voting/admin/index.php" class="btn btn-outline" style="text-decoration:none;">Back to Dashboard</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Create User Form -->
<form method="post" class="form" style="margin-bottom:18px;">
    <input type="hidden" name="action" value="create_user">

    <div class="form-group" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px;">
        <div>
            <label class="label" for="username">Username</label>
            <input class="input" type="text" id="username" name="username" placeholder="e.g., admin_jane" required>
        </div>
        <div>
            <label class="label" for="full_name">Full Name</label>
            <input class="input" type="text" id="full_name" name="full_name" placeholder="Jane Doe" required>
        </div>
        <div>
            <label class="label" for="role">Role</label>
            <select class="input" id="role" name="role">
                <option value="admin">Admin</option>
                <option value="voter" selected>Voter</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="label" for="password">Password</label>
        <div class="input-wrapper">
            <input class="input" type="password" id="password" name="password" placeholder="Set a password" required>
            <span class="input-toggle" data-toggle="password">Show</span>
        </div>
    </div>

    <button class="btn btn-primary" type="submit">Create User</button>
</form>

<hr style="border-color:rgba(148,163,184,0.3); margin:16px 0;">

<div class="app-title" style="font-size:1.05rem; margin-bottom:6px;">Existing Users</div>
<div class="app-subtitle" style="margin-bottom:10px;">Be careful deleting admin accounts.</div>

<?php if (empty($users)): ?>
    <div class="alert">No users found.</div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:8px; font-size:0.85rem;">
        <?php foreach ($users as $u): ?>
            <div class="alert" style="border-radius:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                    <div>
                        <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                        <div style="font-size:0.8rem; color:#9ca3af;">
                            @<?php echo htmlspecialchars($u['username']); ?> · Role: <?php echo htmlspecialchars($u['role']); ?> ·
                            Created: <?php echo htmlspecialchars($u['created_at']); ?>
                        </div>
                    </div>
                    <?php if ($u['id'] != $currentUserId): ?>
                        <form method="post" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                            <button class="btn btn-outline" type="submit" style="font-size:0.78rem; padding:6px 12px;">
                                Delete
                            </button>
                        </form>
                    <?php else: ?>
                        <span style="font-size:0.78rem; color:#4ade80;">(You)</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>