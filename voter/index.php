<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

requireLogin('voter');

$pageTitle = "Voter Dashboard · Online Voting";
include __DIR__ . '/../includes/header.php';

$fullName = $_SESSION['full_name'] ?? 'Voter';
?>

<div class="app-header">
    <div>
        <div class="app-title">Voter Dashboard</div>
        <div class="app-subtitle">
            Hi <?php echo htmlspecialchars($fullName); ?>, welcome to the voting portal.
        </div>
    </div>
    <div class="row">
        <span class="badge">Voter</span>
        <a href="/online_voting/logout.php" class="btn btn-outline" style="text-decoration:none;">Logout</a>
    </div>
</div>

<div class="alert" style="margin-bottom:16px;">
    You can view the active election and cast your vote. You are allowed to vote only once.
</div>

<div class="app-footer">
    <a href="/online_voting/voter/vote.php" class="btn btn-primary" style="text-decoration:none;">
        Go to Voting Page
    </a>
    <span style="font-size:0.8rem;">Your vote is recorded securely.</span>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>