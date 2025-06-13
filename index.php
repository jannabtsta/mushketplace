<?php
require_once 'send_otp_mail.php';

session_start();
$conn = new mysqli("localhost", "root", "", "mushket");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["name"] = $user["name"];
if ($user['role'] === 'farmer') {
    $otp = rand(100000, 999999);
$otp_str = strval($otp); // convert to string to match VARCHAR(6)
$expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
$user_id = intval($user['id']); // just to be explicit

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
