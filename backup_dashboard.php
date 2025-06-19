<?php
session_start();
require_once 'backup_manager.php';
require_once 'backup_scheduler.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$backupManager = new BackupManager();
$scheduler = new BackupScheduler();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_backup'])) {
        $result = $backupManager->createDatabaseBackup();
        $message = $result ? "Backup created successfully!" : "Backup failed!";
    } elseif (isset($_POST['clean_old_backups'])) {
        $deleted = $backupManager->cleanOldBackups();
        $message = "Cleaned $deleted old backup files.";
    } elseif (isset($_POST['backup_user_data'])) {
        $userId = $_SESSION['user_id'];
        $result = $backupManager->backupUserData($userId);
        $message = $result ? "User data backup created!" : "User backup failed!";
    }
}

$backups = $backupManager->getBackupList();
$scheduleStatus = $scheduler->getScheduleStatus();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Backup Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .backup-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 10px 15px; margin: 5px; background-color: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #005a87; }
        .message { padding: 10px; margin: 10px 0; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
    </style>
</head>
<body>
    <h1>Backup Management</h1>
    
    <?php if (isset($message)): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="backup-section">
        <h2>Create Backups</h2>
        <form method="POST" style="display: inline;">
            <button type="submit" name="create_backup">Create Full Database Backup</button>
        </form>
        
        <form method="POST" style="display: inline;">
            <button type="submit" name="backup_user_data">Backup My User Data</button>
        </form>
    </div>
    
    <div class="backup-section">
        <h2>Backup Files</h2>
        <?php if (empty($backups)): ?>
            <p>No backup files found.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Created</th>
                </tr>
                <?php foreach ($backups as $backup): ?>
                <tr>
                    <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                    <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                    <td><?php echo htmlspecialchars($backup['created']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="backup-section">
        <h2>Maintenance</h2>
        <form method="POST" style="display: inline;">
            <button type="submit" name="clean_old_backups" class="warning">Clean Old Backups (30+ days)</button>
        </form>
        
        <div class="warning" style="margin-top: 10px; padding: 10px;">
            <strong>Note:</strong> All backups are automatically encrypted using AES-256-CBC encryption.
            The encryption key is stored securely on the server.
        </div>
    </div>
    
    <div class="backup-section">
        <h2>Backup Schedule</h2>
        <table>
            <tr>
                <th>Task</th>
                <th>Frequency</th>
                <th>Next Run</th>
                <th>Last Run</th>
            </tr>
            <?php foreach ($scheduleStatus as $task => $status): ?>
            <tr>
                <td><?php echo htmlspecialchars($task); ?></td>
                <td><?php echo htmlspecialchars($status['frequency']); ?></td>
                <td><?php echo htmlspecialchars($status['next_run']); ?></td>
                <td><?php echo htmlspecialchars($status['last_run']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="backup-section">
        <h2>How Automatic Backups Work</h2>
        <div style="background-color: #e7f3ff; padding: 15px; border-radius: 5px;">
            <p><strong>🔄 Automatic Schedule:</strong></p>
            <ul>
                <li>Backups run automatically when users visit the site</li>
                <li>Daily database backups at 2:00 AM (when triggered)</li>
                <li>Weekly cleanup of old backups on Sundays</li>
                <li>No external setup required - works through web requests</li>
            </ul>
        </div>
    </div>
    
    <p><a href="security_dashboard.php">← Back to Security Dashboard</a></p>
</body>
</html>