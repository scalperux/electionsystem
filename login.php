<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/auth.php';

// If already logged in, don't show login
redirectLoggedInUser();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $full_name, $hash, $role);
                $stmt->fetch();

                if (password_verify($password, $hash)) {
                    // login success
                    $_SESSION['user_id'] = $id;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = $role;

                    // redirect by role
                    if ($role === 'admin') {
                        header('Location: /online_voting/admin/index.php');
                        exit;
                    } elseif ($role === 'voter') {
                        header('Location: /online_voting/voter/index.php');
                        exit;
                    } else {
                        $error = "Unknown role for this account.";
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            $stmt->close();
        } else {
            $error = "Server error. Please try again.";
        }
    }
}

// For header
$pageTitle = "Sign In · Online Voting";
include __DIR__ . '/includes/header.php';
?>

<div class="app-header">
    <div>
        <div class="app-title">Secure Online Voting</div>
        <div class="app-subtitle">Sign in as admin or voter to continue.</div>
    </div>
    <span class="badge">Role-Based Access</span>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form method="post" class="form" data-form="login">
    <div class="form-group">
        <label class="label" for="username">Username</label>
        <div class="input-wrapper">
            <input
                class="input"
                type="text"
                id="username"
                name="username"
                placeholder="Enter your username"
                autofocus
                required
            >
        </div>
    </div>

    <div class="form-group">
        <label class="label" for="password">Password</label>
        <div class="input-wrapper">
            <input
                class="input"
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                required
            >
            <span class="input-toggle" data-toggle="password">Show</span>
        </div>
    </div>

    <button class="btn btn-primary" type="submit">
        Sign In
    </button>
</form>

<div class="app-footer" style="margin-top: 20px;">
    <span style="font-size:0.78rem;">Enter your assigned username and password.</span>
    <span style="font-size:0.78rem;">Made with PHP · MySQL · JavaScript</span>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>