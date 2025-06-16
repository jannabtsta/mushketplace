<?php
require_once 'send_otp_mail.php';
require_once 'login_tracker.php';

// Try to include backup scheduler, but don't crash if it fails
if (file_exists(__DIR__ . '/backup_scheduler.php')) {
    require_once 'backup_scheduler.php';
}

session_start();
$conn = new mysqli("localhost", "root", "", "mushket");

// Initialize login tracker
$loginTracker = new LoginTracker($conn);

// Check for scheduled backups (only if BackupScheduler class exists)
if (class_exists('BackupScheduler') && (!isset($_SESSION['last_backup_check']) || (time() - $_SESSION['last_backup_check']) > 3600)) {
    try {
        $scheduler = new BackupScheduler();
        $scheduler->checkAndRunScheduledBackups();
        $_SESSION['last_backup_check'] = time();
    } catch (Exception $e) {
        error_log("Backup scheduler error: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Log successful login attempt
            $loginTracker->logLoginAttempt($user['id'], true);
            
            // Check for suspicious activity
            $securityCheck = $loginTracker->checkForSuspiciousActivity($user['id']);
            
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["name"] = $user["name"];
            
            // Send security alert if new device/IP
            if ($securityCheck['should_alert']) {
                $deviceInfo = $loginTracker->getDeviceFingerprint();
                $ipAddress = $loginTracker->getRealIpAddress();
                $loginTracker->sendSecurityAlert($user['email'], $ipAddress, $deviceInfo);
            }
            
            if ($user['role'] === 'farmer') {
                $otp = rand(100000, 999999);
                $otp_str = strval($otp);
                $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                $user_id = intval($user['id']);

                $update = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
                $update->bind_param("ssi", $otp_str, $expiry, $user_id);

                if ($update->execute()) {
                    if (sendOTPEmail($user['email'], $otp)) {
                        $_SESSION["pending_user_id"] = $user["id"];
                        header("Location: verify_otp.php");
                        exit();
                    } else {
                        echo "❌ Failed to send OTP email.";
                    }
                } else {
                    echo "❌ Failed to update OTP in database.";
                }
            } else {
                header("Location: consumer.php");
            }
            exit();
        } else {
            // Log failed login attempt
            if ($user) {
                $loginTracker->logLoginAttempt($user['id'], false);
            }
            echo "❌ Invalid password.";
        }
    } else {
        echo "❌ No user found.";
    }
    $stmt->close();
}
?>

<form method="POST">
    <h2>Login</h2>
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    <button type="submit">Login</button>
</form>
<p>Don't have an account? <a href="register.php">Register here</a></p>
