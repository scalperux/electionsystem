<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

requireLogin('admin');

$selectedElectionId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : 0;

// Fetch elections for dropdown
$elections = [];
$resE = $conn->query("SELECT id, title FROM elections ORDER BY created_at DESC");
if ($resE) {
    while ($row = $resE->fetch_assoc()) {
        $elections[] = $row;
    }
}

// Fetch results for selected election
$results = [];
$totalVotes = 0;
$electionTitle = '';

if ($selectedElectionId > 0) {
    // Get election title
    $stmtTitle = $conn->prepare("SELECT title FROM elections WHERE id = ?");
    $stmtTitle->bind_param("i", $selectedElectionId);
    $stmtTitle->execute();
    $stmtTitle->bind_result($electionTitle);
    $stmtTitle->fetch();
    $stmtTitle->close();

    $stmtResults = $conn->prepare("
        SELECT c.id, c.name, COUNT(v.id) AS votes_count
        FROM candidates c
        LEFT JOIN votes v 
            ON c.id = v.candidate_id
           AND v.election_id = ?
        WHERE c.election_id = ?
        GROUP BY c.id, c.name
        ORDER BY votes_count DESC, c.name ASC
    ");
    $stmtResults->bind_param("ii", $selectedElectionId, $selectedElectionId);
    $stmtResults->execute();
    $resR = $stmtResults->get_result();
    if ($resR) {
        while ($row = $resR->fetch_assoc()) {
            $results[]   = $row;
            $totalVotes += (int) $row['votes_count'];
        }
    }
    $stmtResults->close();
}

$pageTitle = "Election Results · Online Voting";
include __DIR__ . '/../includes/header.php';
$fullName = $_SESSION['full_name'] ?? 'Admin';
?>

<div class="app-header">
    <div>
        <div class="app-title">Election Results</div>
        <div class="app-subtitle">
            View vote counts and percentages for any election.
        </div>
    </div>
    <div class="row">
        <span class="badge">Admin</span>
        <a href="/online_voting/admin/index.php" class="btn btn-outline" style="text-decoration:none;">Back to Dashboard</a>
    </div>
</div>

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
        Please select an election to view its results.
    </div>
<?php else: ?>

    <div class="app-header" style="margin-bottom:10px;">
        <div>
            <div class="app-title" style="font-size:1.05rem;">
                <?php echo htmlspecialchars($electionTitle ?: 'Election'); ?>
            </div>
            <div class="app-subtitle">
                Total votes: <?php echo $totalVotes; ?> (refresh page to update)
            </div>
        </div>
    </div>

    <?php if (empty($results)): ?>
        <div class="alert">
            No candidates or votes found for this election yet.
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($results as $row):
                $count   = (int) $row['votes_count'];
                $percent = $totalVotes > 0 ? round($count * 100 / $totalVotes) : 0;
            ?>
                <div class="alert" style="border-radius:14px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:0.85rem;">
                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        <span><?php echo $count; ?> vote(s) · <?php echo $percent; ?>%</span>
                    </div>
                    <div style="width:100%; height:6px; border-radius:999px; background:rgba(15,23,42,0.9); overflow:hidden;">
                        <div style="
                            width: <?php echo $percent; ?>%;
                            height: 100%;
                            border-radius:999px;
                            background: linear-gradient(90deg, #4f46e5, #22c55e);
                            transition: width 0.3s ease;
                        "></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>