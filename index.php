<?php
require_once 'send_otp_mail.php';
require_once 'login_tracker.php';


if (file_exists(__DIR__ . '/backup_scheduler.php')) {
    require_once 'backup_scheduler.php';
}

session_start();
$conn = new mysqli("localhost", "root", "", "mushket");


$loginTracker = new LoginTracker($conn);

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
         
            $loginTracker->logLoginAttempt($user['id'], true);
            
            
            $securityCheck = $loginTracker->checkForSuspiciousActivity($user['id']);
            
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["name"] = $user["name"];
            
            
            if ($securityCheck['should_alert']) {
                $deviceInfo = $loginTracker->getDeviceFingerprint();
                $ipAddress = $loginTracker->getRealIpAddress();
                $loginTracker->sendSecurityAlert($user['email'], $ipAddress, $deviceInfo);
            }
            
           if ($user['role'] === 'admin') {
    header("Location: admin_verify.php");
    exit();
} elseif ($user['role'] === 'farmer') {
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

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🍄MUSHKETPLACE</title>

   
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('mushketBG.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-container {
            backdrop-filter: blur(12px);
            background: #FFAAAA;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            width: 320px;
            text-align: center;
            color: #fff;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

       
        .login-container h2 {
            font-family: "Jua", sans-serif; 
            font-size: 38px;
            font-weight: 700;
            color:rgb(65, 12, 12);
            margin-bottom: 25px;
            letter-spacing: 1px;
            text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.4);
            transform: scale(1.03);
        }

        .login-container input[type="email"],
        .login-container input[type="password"] {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 10px;
            outline: none;
            font-size: 14px;
        }

        .login-container input[type="email"]:focus,
        .login-container input[type="password"]:focus {
            box-shadow: 0 0 5px #7affd4;
        }

        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: rgb(65, 12, 12);
            border: none;
            border-radius: 10px;
             color:rgb(255, 247, 247);
                 font-family: 'Jua', sans-serif;
            font-weight: bold;
            font-size: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .login-container button:hover {
            background-color: #FF9898;
            transform: scale(1.02);
        }

        .login-container p {
            color:rgb(255, 252, 248);
            margin-top: 20px;
            font-size: 14px;
        }

        .login-container a {
            color:rgb(255, 254, 252);
            text-decoration: none;
            font-weight: bold;
        }

        .login-container a:hover {
            text-decoration: underline;
        }

   
    </style>
</head>
<body>
    <div class="login-container">
        <form method="POST">
            <h2>Mushketplace</h2>
            <input type="email" name="email" placeholder="Email address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
          <h2>🍄 </h2>
    </div>
</body>
</html>
