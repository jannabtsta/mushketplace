<?php
session_start();
require_once 'login_tracker.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}
$conn = new mysqli("localhost", "root", "", "mushket");
$userId = $_SESSION['user_id'];


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
    <title>🔐 Security Dashboard | Mushketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Jua', sans-serif;
            background-color: #fff6f6;
            margin: 0;
            padding: 30px;
        }

        h2 {
            text-align: center;
            color: #6b1d1d;
            font-size: 34px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: center;
            font-size: 15px;
        }

        th {
            background-color: #ffd6d6;
            color: #6b1d1d;
        }

        tr:hover {
            background-color: #fcecec;
        }

        a {
            display: inline-block;
            margin: 15px 10px 0 0;
            padding: 10px 18px;
            text-decoration: none;
            background-color: #ffcaca;
            color: #6b1d1d;
            font-weight: bold;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: background-color 0.3s, transform 0.2s;
        }

        a:hover {
            background-color: #ffb6b6;
            transform: scale(1.03);
        }

        .links {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <h2>🛡️ Security Dashboard</h2>

    <table>
        <tr>
            <th>Date/Time</th>
            <th>IP Address</th>
            <th>Browser</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['login_time']; ?></td>
            <td><?= $row['ip_address']; ?></td>
            <td><?= $row['browser_info']; ?></td>
            <td><?= $row['is_successful'] ? '✅ Success' : '❌ Failed'; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="links">
        <a href="consumer.php">← Back to Dashboard</a>
        <a href="backup_dashboard.php">🔒 Backup Management</a>
    </div>

</body>
</html>
