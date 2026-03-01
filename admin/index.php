<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

requireLogin('admin');

$pageTitle = "Admin Dashboard · Online Voting";
include __DIR__ . '/../includes/header.php';

$fullName = $_SESSION['full_name'] ?? 'Admin';
?>

<div class="app-header">
    <div>
        <div class="app-title">Admin Dashboard</div>
        <div class="app-subtitle">
            Welcome back, <?php echo htmlspecialchars($fullName); ?>. Manage elections, candidates and monitor results.
        </div>
    </div>
    <div class="row">
        <span class="badge">Admin</span>
        <a href="/online_voting/logout.php" class="btn btn-outline" style="text-decoration:none;">Logout</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:14px; margin-top:10px;">
    <a href="/online_voting/admin/elections.php" style="text-decoration:none;">
        <div class="candidate-card">
            <div class="candidate-name">Elections</div>
            <div class="candidate-meta">Create and manage elections (title, dates, status).</div>
        </div>
    </a>
    <a href="/online_voting/admin/candidates.php" style="text-decoration:none;">
        <div class="candidate-card">
            <div class="candidate-name">Candidates</div>
            <div class="candidate-meta">Add candidates and manifestos to each election.</div>
        </div>
    </a>
    <a href="/online_voting/admin/results.php" style="text-decoration:none;">
        <div class="candidate-card">
            <div class="candidate-name">Results</div>
            <div class="candidate-meta">View live vote counts and percentages per election.</div>
        </div>
    </a>
    <a href="/online_voting/admin/voters.php" style="text-decoration:none;">
        <div class="candidate-card">
            <div class="candidate-name">Users</div>
            <div class="candidate-meta">Create admin and voter accounts, and manage existing users.</div>
        </div>
    </a>
</div>

<div class="app-footer" style="margin-top:18px;">
    <span style="font-size:0.8rem;">Tip: Start by creating an election, then add candidates.</span>
    <span style="font-size:0.8rem;">Role: Admin</span>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>