<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mushket");

if (!isset($_SESSION["pending_user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["pending_user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_otp = $_POST["otp"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $current_time = date("Y-m-d H:i:s");

    if ($user && $user["otp_code"] === $input_otp && $current_time <= $user["otp_expiry"]) {
      
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["name"] = $user["name"];
        unset($_SESSION["pending_user_id"]);

      
        $conn->query("UPDATE users SET otp_code=NULL, otp_expiry=NULL WHERE id=$user_id");

        header("Location: farmer.php");
        exit();
    } else {
        echo "❌ Invalid or expired OTP.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍄 Verify OTP - Mushketplace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Jua', sans-serif;
            background: url('mushketBG.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .otp-container {
            background: #fff9e6;
            
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 320px;
            text-align: center;
            animation: slideIn 0.6s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            font-size: 28px;
            color: #b94a48;
            margin-bottom: 20px;
        }

        input[type="text"] {
            box-sizing: border-box;
            padding: 12px;
            width: 100%;
            margin: 15px 0;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 18px;
            text-align: center;
            letter-spacing: 4px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #b94a48;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #ff7a7a;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            color: #b94a48;
            font-weight: bold;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <h2>🔐 Enter OTP</h2>
        <form method="POST">
            <input type="text" name="otp" maxlength="6" placeholder="123456" required>
            <button type="submit">Verify</button>
        </form>
        <a href="index.php">← Back to Login</a>
    </div>
</body>
</html>

