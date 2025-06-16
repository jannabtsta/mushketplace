<?php
session_start();
require_once 'login_tracker.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$userId = $_SESSION['user_id'];

// Get recent login attempts
$stmt = $conn->prepare("
    SELECT ip_address, device_info, browser_info, login_time, is_successful 
    FROM login_attempts 
    WHERE user_id = ? 
    ORDER BY login_time DESC 
    LIMIT 10
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Security Dashboard</title>
</head>
<body>
    <h2>Recent Login Activity</h2>
    <table border="1">
        <tr>
            <th>Date/Time</th>
            <th>IP Address</th>
            <th>Browser</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['login_time']; ?></td>
            <td><?php echo $row['ip_address']; ?></td>
            <td><?php echo $row['browser_info']; ?></td>
            <td><?php echo $row['is_successful'] ? '✅ Success' : '❌ Failed'; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <p><a href="consumer.php">← Back to Dashboard</a></p>
    <p><a href="backup_dashboard.php">🔒 Backup Management</a></p>
</body>
</html>