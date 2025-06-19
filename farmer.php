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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Jua', sans-serif;
            background: #fffbea;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .verify-box {
            background: #fff9e6;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
            width: 420px;
        }
        h1 {
            color: #b94a48;
            margin-bottom: 15px;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        button {
            background-color: #b94a48;
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-family: 'Jua', sans-serif;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #ff7a7a;
        }
        a {
            color: #b94a48;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verify-box">
        <h1>🍄 Verification Required</h1>
        <div class="warning-box">
            <p><strong>Vendor verification</strong> must be completed to access farmer features.</p>
            <p>This ensures trust and safety within Mushketplace.</p>
        </div>
        <?php if ($verificationStatus): ?>
            <p>Status: <strong><?= ucfirst($verificationStatus['verification_status']) ?></strong></p>
            <?php if ($verificationStatus['verification_status'] === 'pending'): ?>
                <p>⏳ Your request is under review.</p>
            <?php endif; ?>
        <?php endif; ?>
        <a href="vendor_verification_form.php"><button>Complete Verification</button></a><br><br>
        <a href="consumer.php">← Back to Dashboard</a>
    </div>
</body>
</html>
<?php exit(); } ?>

<?php
// Form section if verified
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = $_POST["quantity"];
    $today = date('Y-m-d');

    $check = $conn->prepare("SELECT id FROM stock WHERE user_id = ? AND date = ?");
    $check->bind_param("is", $user_id, $today);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "❌ You've already submitted stock for today.";
    } else {
        $stmt = $conn->prepare("INSERT INTO stock (user_id, quantity_kg, available_kg, date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idds", $user_id, $quantity, $quantity, $today);
        $stmt->execute();
        $stmt->close();
        $message = "✅ Stock submitted successfully.";
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Farmer Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Jua', sans-serif;
            background: url('mushketBG.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 0;
        }

        .container {
            background: #fff9e6;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 400px;
            text-align: center;
        }

        h2 {
            color: #b94a48;
            margin-bottom: 20px;
        }

        input[type="number"] {
            padding: 12px;
            width: 100%;
            margin: 15px 0;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-family: 'Jua', sans-serif;
        }

        button {
            padding: 12px 20px;
            background-color: #b94a48;
            color: #fff;
            font-family: 'Jua', sans-serif;
            font-size: 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #ff7a7a;
        }

        .nav-links {
            margin-top: 20px;
        }

        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            color: #b94a48;
            text-decoration: none;
            font-weight: bold;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .message {
            margin-top: 10px;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?= $_SESSION["name"] ?> 🍄</h2>

        <?php if (isset($message)): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Enter Available Mushrooms (kg)</label>
            <input type="number" name="quantity" step="0.1" min="0.1" required>
            <button type="submit">Submit Stock</button>
        </form>

        <div class="nav-links">
            <a href="farmer_orders.php">📦 View Orders</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
</body>
</html>
