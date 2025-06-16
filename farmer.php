<?php
session_start();
require_once 'vendor_verification.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: index.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$verification = new VendorVerification($conn);
$verificationStatus = $verification->getVerificationStatus($_SESSION['user_id']);

// Check if vendor is verified
if (!$verificationStatus || $verificationStatus['verification_status'] !== 'approved') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Verification Required</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; }
            .warning-box { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 5px; margin: 20px 0; }
            button { padding: 12px 24px; background-color: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        </style>
    </head>
    <body>
        <h1>Verification Required</h1>
        <div class="warning-box">
            <h2>⚠️ Vendor Verification Needed</h2>
            <p>Before you can list products and access farmer features, you need to complete vendor verification.</p>
            <p>This process helps ensure the security and trustworthiness of our marketplace.</p>
        </div>
        
        <?php if ($verificationStatus): ?>
            <p>Current Status: <strong><?php echo ucfirst($verificationStatus['verification_status']); ?></strong></p>
            <?php if ($verificationStatus['verification_status'] === 'pending'): ?>
                <p>Your verification is being reviewed. We'll notify you once it's complete.</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="vendor_verification_form.php" style="text-decoration: none;">
            <button>Complete Verification</button>
        </a>
        
        <p><a href="consumer.php">← Back to Dashboard</a></p>
    </body>
    </html>
    <?php
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = $_POST["quantity"];
    $today = date('Y-m-d');

    // Check if the farmer already has stock today
    $check = $conn->prepare("SELECT id FROM stock WHERE user_id = ? AND date = ?");
    $check->bind_param("is", $user_id, $today);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "❌ You've already submitted stock for today.";
    } else {
        $stmt = $conn->prepare("INSERT INTO stock (user_id, quantity_kg, available_kg, date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idds", $user_id, $quantity, $quantity, $today);
        $stmt->execute();
        echo "✅ Stock submitted successfully.";
        $stmt->close();
    }
    $check->close();
}
?>

<h2>Welcome, <?php echo $_SESSION["name"]; ?> (Farmer)</h2>
<form method="POST">
    Quantity of Oyster Mushrooms Available Today (kg): 
    <input type="number" name="quantity" step="0.1" min="0.1" required><br>
    <button type="submit">Submit Stock</button>
</form>

<a href="farmer_orders.php">View Today's Orders</a>
<a href="logout.php">Logout</a>
