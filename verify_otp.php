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
        // OTP is valid, complete login
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["name"] = $user["name"];
        unset($_SESSION["pending_user_id"]);

        // Clear OTP
        $conn->query("UPDATE users SET otp_code=NULL, otp_expiry=NULL WHERE id=$user_id");

        header("Location: farmer.php");
        exit();
    } else {
        echo "❌ Invalid or expired OTP.";
    }
}
?>

<h2>Enter OTP</h2>
<form method="POST">
    OTP Code: <input type="text" name="otp" maxlength="6" required><br>
    <button type="submit">Verify</button>
</form>
<a href="index.php">Back to Login</a>
