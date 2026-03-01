<?php
require 'config.php';

// Create sample admin & voter if they don't exist
function createUserIfNotExists($conn, $username, $fullName, $passwordPlain, $role) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
        $stmtInsert = $conn->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("ssss", $username, $fullName, $hash, $role);
        $stmtInsert->execute();
        $stmtInsert->close();
        echo "Created user: $username ($role)<br>";
    } else {
        echo "User $username already exists.<br>";
    }
}

createUserIfNotExists($conn, 'admin1', 'System Administrator', 'admin123', 'admin');
createUserIfNotExists($conn, 'voter1', 'Test Voter', 'voter123', 'voter');

// Create one sample election if none exists
$check = $conn->query("SELECT id FROM elections LIMIT 1");
if ($check && $check->num_rows === 0) {
    $now = date('Y-m-d H:i:s');
    $end = date('Y-m-d H:i:s', strtotime('+7 days'));
    $stmt = $conn->prepare("INSERT INTO elections (title, description, start_time, end_time, status) VALUES (?, ?, ?, ?, 'ongoing')");
    $title = "Student Council Election";
    $desc  = "Vote for your preferred student council representative.";
    $stmt->bind_param("ssss", $title, $desc, $now, $end);
    $stmt->execute();
    $electionId = $stmt->insert_id;
    $stmt->close();

    // Add sample candidates
    $candStmt = $conn->prepare("INSERT INTO candidates (election_id, name, manifesto) VALUES (?, ?, ?)");
    $candStmt->bind_param("iss", $electionId, $name, $manifesto);

    $name = "Alice Mwangi";
    $manifesto = "Improve Wi-Fi, more study spaces, longer library hours.";
    $candStmt->execute();

    $name = "Brian Otieno";
    $manifesto = "Increase clubs, sports, and campus events.";
    $candStmt->execute();

    $name = "Cynthia Njeri";
    $manifesto = "Focus on mental health, counseling and peer support.";
    $candStmt->execute();

    $candStmt->close();

    echo "Created sample election with three candidates.<br>";
} else {
    echo "Election already exists.<br>";
}

echo "<br>Done. You can now delete seed.php.";