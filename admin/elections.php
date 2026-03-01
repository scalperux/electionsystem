<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

requireLogin('admin');

$message = '';
$messageType = '';

// Handle create election
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_time  = trim($_POST['start_time'] ?? '');
    $end_time    = trim($_POST['end_time'] ?? '');
    $status      = $_POST['status'] ?? 'upcoming';

    if ($title === '') {
        $message = "Title is required.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO elections (title, description, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $title, $description, $start_time, $end_time, $status);
        if ($stmt->execute()) {
            $message = "Election created successfully.";
            $messageType = "success";
        } else {
            $message = "Error creating election.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $electionId = (int) ($_POST['election_id'] ?? 0);
    $status     = $_POST['status'] ?? 'upcoming';

    $stmt = $conn->prepare("UPDATE elections SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $electionId);
    if ($stmt->execute()) {
        $message = "Election status updated.";
        $messageType = "success";
    } else {
        $message = "Error updating status.";
        $messageType = "error";
    }
    $stmt->close();
}

// Fetch all elections
$elections = [];
$res = $conn->query("SELECT * FROM elections ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $elections[] = $row;
    }
}

$pageTitle = "Manage Elections · Online Voting";
include __DIR__ . '/../includes/header.php';
$fullName = $_SESSION['full_name'] ?? 'Admin';
?>

<div class="app-header">
    <div>
        <div class="app-title">Manage Elections</div>
        <div class="app-subtitle">
            Create new elections and control their status (upcoming, ongoing, closed).
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

<!-- Create Election Form -->
<form method="post" class="form" style="margin-bottom:18px;">
    <input type="hidden" name="action" value="create">

    <div class="form-group">
        <label class="label" for="title">Election Title</label>
        <input class="input" type="text" id="title" name="title" placeholder="e.g., Student Council Election 2026" required>
    </div>

    <div class="form-group">
        <label class="label" for="description">Description (optional)</label>
        <textarea
            id="description"
            name="description"
            rows="3"
            style="
                width:100%;
                padding:10px 12px;
                border-radius:16px;
                border:1px solid rgba(148,163,184,0.45);
                background:rgba(15,23,42,0.94);
                color:#f9fafb;
                font-size:0.9rem;
                resize:vertical;
            "
            placeholder="Describe the purpose of this election..."
        ></textarea>
    </div>

    <div class="form-group" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px;">
        <div>
            <label class="label" for="start_time">Start Time</label>
            <input class="input" type="datetime-local" id="start_time" name="start_time">
        </div>
        <div>
            <label class="label" for="end_time">End Time</label>
            <input class="input" type="datetime-local" id="end_time" name="end_time">
        </div>
        <div>
            <label class="label" for="status">Status</label>
            <select class="input" id="status" name="status">
                <option value="upcoming">Upcoming</option>
                <option value="ongoing">Ongoing</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>

    <button class="btn btn-primary" type="submit">Create Election</button>
</form>

<hr style="border-color:rgba(148,163,184,0.3); margin:16px 0;">

<div class="app-title" style="font-size:1.05rem; margin-bottom:6px;">Existing Elections</div>
<div class="app-subtitle" style="margin-bottom:10px;">Click status to change it.</div>

<?php if (empty($elections)): ?>
    <div class="alert">No elections created yet.</div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:10px; font-size:0.85rem;">
        <?php foreach ($elections as $e): ?>
            <div class="alert" style="border-radius:16px;">
                <div style="display:flex; justify-content:space-between; gap:8px; margin-bottom:4px;">
                    <div>
                        <strong><?php echo htmlspecialchars($e['title']); ?></strong>
                        <div style="font-size:0.8rem; color:#9ca3af;">
                            <?php echo htmlspecialchars($e['status']); ?> ·
                            <?php echo htmlspecialchars($e['start_time'] ?: 'No start'); ?> →
                            <?php echo htmlspecialchars($e['end_time'] ?: 'No end'); ?>
                        </div>
                    </div>
                    <form method="post" class="row" style="gap:6px;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="election_id" value="<?php echo (int)$e['id']; ?>">
                        <select name="status" class="input" style="padding:6px 10px; font-size:0.78rem; width:auto;">
                            <option value="upcoming" <?php if ($e['status']==='upcoming') echo 'selected'; ?>>Upcoming</option>
                            <option value="ongoing"  <?php if ($e['status']==='ongoing') echo 'selected'; ?>>Ongoing</option>
                            <option value="closed"   <?php if ($e['status']==='closed') echo 'selected'; ?>>Closed</option>
                        </select>
                        <button class="btn btn-outline" type="submit" style="font-size:0.78rem; padding:6px 12px;">
                            Update
                        </button>
                    </form>
                </div>
                <?php if (!empty($e['description'])): ?>
                    <div style="font-size:0.8rem; color:#d1d5db;">
                        <?php echo nl2br(htmlspecialchars($e['description'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>