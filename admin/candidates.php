<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

requireLogin('admin');

$message = '';
$messageType = '';

$selectedElectionId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : 0;

// Fetch elections for dropdown
$elections = [];
$resE = $conn->query("SELECT id, title FROM elections ORDER BY created_at DESC");
if ($resE) {
    while ($row = $resE->fetch_assoc()) {
        $elections[] = $row;
    }
}

// Handle add candidate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_candidate') {
    $selectedElectionId = (int) ($_POST['election_id'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $manifesto  = trim($_POST['manifesto'] ?? '');
    $photo_url  = trim($_POST['photo_url'] ?? '');

    if ($selectedElectionId === 0 || $name === '') {
        $message = "Please select an election and provide candidate name.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO candidates (election_id, name, manifesto, photo_url)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $selectedElectionId, $name, $manifesto, $photo_url);
        if ($stmt->execute()) {
            $message = "Candidate added successfully.";
            $messageType = "success";
        } else {
            $message = "Error adding candidate.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Fetch candidates for selected election
$candidates = [];
if ($selectedElectionId > 0) {
    $stmtC = $conn->prepare("
        SELECT id, name, manifesto, photo_url, created_at
        FROM candidates
        WHERE election_id = ?
        ORDER BY created_at DESC
    ");
    $stmtC->bind_param("i", $selectedElectionId);
    $stmtC->execute();
    $resC = $stmtC->get_result();
    if ($resC) {
        while ($row = $resC->fetch_assoc()) {
            $candidates[] = $row;
        }
    }
    $stmtC->close();
}

$pageTitle = "Manage Candidates · Online Voting";
include __DIR__ . '/../includes/header.php';
$fullName = $_SESSION['full_name'] ?? 'Admin';
?>

<div class="app-header">
    <div>
        <div class="app-title">Manage Candidates</div>
        <div class="app-subtitle">
            Select an election, then add or view candidates.
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

<!-- Election Selector -->
<form method="get" class="form" style="margin-bottom:12px;">
    <div class="form-group">
        <label class="label" for="election_id">Select Election</label>
        <select class="input" id="election_id" name="election_id" onchange="this.form.submit()">
            <option value="0">-- Choose an election --</option>
            <?php foreach ($elections as $e): ?>
                <option value="<?php echo (int)$e['id']; ?>" <?php if ($selectedElectionId == $e['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($e['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($selectedElectionId === 0): ?>
    <div class="alert">
        Please select an election above to manage its candidates.
    </div>
<?php else: ?>

    <!-- Add Candidate Form -->
    <form method="post" class="form" style="margin-bottom:16px;">
        <input type="hidden" name="action" value="add_candidate">
        <input type="hidden" name="election_id" value="<?php echo (int)$selectedElectionId; ?>">

        <div class="form-group">
            <label class="label" for="name">Candidate Name</label>
            <input class="input" type="text" id="name" name="name" placeholder="e.g., Alice Mwangi" required>
        </div>

        <div class="form-group">
            <label class="label" for="manifesto">Manifesto</label>
            <textarea
                id="manifesto"
                name="manifesto"
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
                placeholder="What does this candidate promise to do?"
            ></textarea>
        </div>

        <div class="form-group">
            <label class="label" for="photo_url">Photo URL (optional)</label>
            <input class="input" type="text" id="photo_url" name="photo_url" placeholder="https://example.com/photo.jpg">
        </div>

        <button class="btn btn-primary" type="submit">Add Candidate</button>
    </form>

    <hr style="border-color:rgba(148,163,184,0.3); margin:16px 0;">

    <div class="app-title" style="font-size:1.05rem; margin-bottom:6px;">Existing Candidates</div>

    <?php if (empty($candidates)): ?>
        <div class="alert">
            No candidates added for this election yet.
        </div>
    <?php else: ?>
        <div class="candidate-grid">
            <?php foreach ($candidates as $c): ?>
                <div class="candidate-card">
                    <div class="candidate-name">
                        <?php echo htmlspecialchars($c['name']); ?>
                    </div>
                    <div class="candidate-meta">
                        ID #<?php echo (int)$c['id']; ?> · Added <?php echo htmlspecialchars($c['created_at']); ?>
                    </div>
                    <?php if (!empty($c['photo_url'])): ?>
                        <div style="margin:6px 0;">
                            <img src="<?php echo htmlspecialchars($c['photo_url']); ?>" alt="Photo"
                                 style="max-width:100%; border-radius:12px; border:1px solid rgba(148,163,184,0.4);">
                        </div>
                    <?php endif; ?>
                    <div class="candidate-manifesto">
                        <?php echo nl2br(htmlspecialchars($c['manifesto'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>