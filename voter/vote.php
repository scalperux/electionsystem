<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

requireLogin('voter');

$userId   = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Voter';

$message = '';
$messageType = '';
$hasVoted = false;
$votedCandidateId = null;

// 1) Get current ongoing election (for now just the first ongoing)
$election = null;
$now = date('Y-m-d H:i:s');

$stmtElection = $conn->prepare("
    SELECT id, title, description, start_time, end_time, status
    FROM elections
    WHERE status = 'ongoing'
    ORDER BY start_time ASC
    LIMIT 1
");
$stmtElection->execute();
$resultElection = $stmtElection->get_result();

if ($resultElection && $resultElection->num_rows === 1) {
    $election = $resultElection->fetch_assoc();
    $electionId = (int) $election['id'];
} else {
    $electionId = null;
}
$stmtElection->close();

if (!$electionId) {
    $pageTitle = "Vote · Online Voting";
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="app-header">
        <div>
            <div class="app-title">No Active Election</div>
            <div class="app-subtitle">Hi <?php echo htmlspecialchars($fullName); ?>, there is currently no ongoing election.</div>
        </div>
        <div class="row">
            <span class="badge">Voter</span>
            <a href="/online_voting/logout.php" class="btn btn-outline" style="text-decoration:none;">Logout</a>
        </div>
    </div>

    <div class="alert">
        Please check back later. Once an election is opened by the admin, you will be able to vote here.
    </div>

    <?php include __DIR__ . '/../includes/footer.php';
    exit;
}

// 2) Check if user already voted
$stmtVoteCheck = $conn->prepare("
    SELECT candidate_id 
    FROM votes 
    WHERE user_id = ? AND election_id = ?
");
$stmtVoteCheck->bind_param("ii", $userId, $electionId);
$stmtVoteCheck->execute();
$stmtVoteCheck->bind_result($votedCandidateId);

if ($stmtVoteCheck->fetch()) {
    $hasVoted = true;
}
$stmtVoteCheck->close();

// 3) Handle vote submission (only if not voted yet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasVoted) {
    $candidateId = isset($_POST['candidate_id']) ? (int) $_POST['candidate_id'] : 0;

    // Check candidate belongs to this election
    $stmtCheckCandidate = $conn->prepare("
        SELECT id FROM candidates WHERE id = ? AND election_id = ?
    ");
    $stmtCheckCandidate->bind_param("ii", $candidateId, $electionId);
    $stmtCheckCandidate->execute();
    $stmtCheckCandidate->store_result();

    if ($stmtCheckCandidate->num_rows === 1) {
        $stmtCheckCandidate->close();

        // Insert vote (unique constraint enforces 1 vote per user/election)
        $stmtInsert = $conn->prepare("
            INSERT INTO votes (user_id, election_id, candidate_id)
            VALUES (?, ?, ?)
        ");
        $stmtInsert->bind_param("iii", $userId, $electionId, $candidateId);

        if ($stmtInsert->execute()) {
            $message = "Thank you! Your vote has been recorded.";
            $messageType = "success";
            $hasVoted = true;
            $votedCandidateId = $candidateId;
        } else {
            $message = "Error recording your vote. Please try again.";
            $messageType = "error";
        }
        $stmtInsert->close();
    } else {
        $message = "Invalid candidate selected.";
        $messageType = "error";
        $stmtCheckCandidate->close();
    }
}

// 4) Fetch candidates for this election
$candidates = [];
$stmtCandidates = $conn->prepare("
    SELECT id, name, manifesto, photo_url
    FROM candidates
    WHERE election_id = ?
    ORDER BY name ASC
");
$stmtCandidates->bind_param("i", $electionId);
$stmtCandidates->execute();
$resultCandidates = $stmtCandidates->get_result();

if ($resultCandidates) {
    while ($row = $resultCandidates->fetch_assoc()) {
        $candidates[] = $row;
    }
}
$stmtCandidates->close();

// 5) Fetch results (votes per candidate)
$results = [];
$totalVotes = 0;

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
$stmtResults->bind_param("ii", $electionId, $electionId);
$stmtResults->execute();
$resultResults = $stmtResults->get_result();

if ($resultResults) {
    while ($row = $resultResults->fetch_assoc()) {
        $results[] = $row;
        $totalVotes += (int) $row['votes_count'];
    }
}
$stmtResults->close();

// 6) Render UI
$pageTitle = "Vote · " . $election['title'];
include __DIR__ . '/../includes/header.php';
?>

<div class="app-header">
    <div>
        <div class="app-title">
            <?php echo htmlspecialchars($election['title']); ?>
        </div>
        <div class="app-subtitle">
            Logged in as <?php echo htmlspecialchars($fullName); ?> · Voter
        </div>
    </div>
    <div class="row">
        <span class="badge"><?php echo $hasVoted ? 'Vote Submitted' : 'Live Voting'; ?></span>
        <a href="/online_voting/logout.php" class="btn btn-outline" style="text-decoration:none;">Logout</a>
    </div>
</div>

<?php if (!empty($election['description'])): ?>
    <div class="alert" style="margin-bottom:10px;">
        <?php echo nl2br(htmlspecialchars($election['description'])); ?>
    </div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($hasVoted): ?>
    <div class="alert" style="margin-bottom: 14px;">
        You have already voted in this election. You can view the live results below.
    </div>
<?php else: ?>
    <div class="app-subtitle" style="margin-bottom:8px;">
        Tap a candidate below to select, then confirm your vote. You can only vote once.
    </div>

    <form method="post" id="voteForm">
        <input type="hidden" name="candidate_id" id="candidateInput">

        <div class="candidate-grid">
            <?php foreach ($candidates as $c): ?>
                <div class="candidate-card" data-candidate-id="<?php echo $c['id']; ?>">
                    <div class="candidate-name">
                        <?php echo htmlspecialchars($c['name']); ?>
                    </div>
                    <div class="candidate-meta">
                        Candidate · ID #<?php echo $c['id']; ?>
                    </div>
                    <div class="candidate-manifesto">
                        <?php echo nl2br(htmlspecialchars($c['manifesto'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="app-footer">
            <span class="alert" id="selectionStatus">
                No candidate selected yet.
            </span>
            <button class="btn btn-primary" type="submit" id="confirmBtn" disabled>
                Confirm Vote
            </button>
        </div>
    </form>
<?php endif; ?>

<hr style="border-color:rgba(148,163,184,0.3); margin:20px 0;">

<div class="app-header" style="margin-bottom:10px;">
    <div>
        <div class="app-title" style="font-size:1.05rem;">
            Live Results
        </div>
        <div class="app-subtitle">
            Updated when you refresh this page. Total votes: <?php echo $totalVotes; ?>
        </div>
    </div>
</div>

<?php if (!empty($results)): ?>
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
<?php else: ?>
    <div class="alert">
        No votes have been cast yet.
    </div>
<?php endif; ?>

<script>
// Interactive selection logic
const cards = document.querySelectorAll('.candidate-card');
const candidateInput = document.getElementById('candidateInput');
const confirmBtn = document.getElementById('confirmBtn');
const selectionStatus = document.getElementById('selectionStatus');

if (cards && candidateInput && confirmBtn) {
    cards.forEach(card => {
        card.addEventListener('click', () => {
            cards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const id = card.getAttribute('data-candidate-id');
            candidateInput.value = id;
            confirmBtn.disabled = false;
            if (selectionStatus) {
                selectionStatus.textContent = 'Selected candidate ID: ' + id + '. Click "Confirm Vote" to submit.';
            }
        });
    });
}

const voteForm = document.getElementById('voteForm');
if (voteForm) {
    voteForm.addEventListener('submit', (e) => {
        if (!candidateInput.value) {
            e.preventDefault();
            alert('Please select a candidate before submitting.');
            return;
        }
        if (!confirm('Are you sure? You can only vote once in this election.')) {
            e.preventDefault();
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>