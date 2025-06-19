<?php
session_start();
require_once 'vendor_verification.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$verification = new VendorVerification($conn);
$userId = $_SESSION['user_id'];

$currentStatus = $verification->getVerificationStatus($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'business_name' => $_POST['business_name'],
        'business_address' => $_POST['business_address'],
        'business_phone' => $_POST['business_phone'],
        'business_email' => $_POST['business_email']
    ];

    $result = $verification->submitVerificationRequest($userId, $data, $_FILES);
    if ($result['success']) {
    header("Location: farmer.php");
    exit();
}
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍄 Vendor Verification</title>

    <!-- Jua Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #ffe5ec, #fadadd);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 100%;
            animation: fadeIn 0.7s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            font-family: 'Jua', sans-serif;
            text-align: center;
            font-size: 34px;
            color: #7c2d12;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
            font-size: 15px;
            color: #444;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            background-color: #a8323e;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 17px;
            font-family: 'Jua', sans-serif;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
            margin-top: 10px;
        }
        

        button:hover {
            background-color: #d23d3d;
            transform: scale(1.02);
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-size: 14px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        p {
            font-size: 14px;
            color: #444;
            margin-top: 15px;
            text-align: center;
        }
            a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background-color: #6c757d;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
        }

        a:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🍄 Vendor Verification</h2>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$currentStatus || $currentStatus['verification_status'] === 'rejected'): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="business_name">Business Name *</label>
                <input type="text" id="business_name" name="business_name" required>
            </div>

            <div class="form-group">
                <label for="business_address">Business Address *</label>
                <textarea id="business_address" name="business_address" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="business_phone">Phone Number *</label>
                <input type="tel" id="business_phone" name="business_phone" required>
            </div>

            <div class="form-group">
                <label for="business_email">Business Email *</label>
                <input type="email" id="business_email" name="business_email" required>
            </div>

            <div class="form-group">
                <label for="id_document1">Valid ID #1 *</label>
                <input type="file" id="id_document1" name="id_document1" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <div class="form-group">
                <label for="id_document2">Valid ID #2 *</label>
                <input type="file" id="id_document2" name="id_document2" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <button type="submit">Submit Verification</button>
        </form>
    <?php else: ?>
        <p><strong>Status:</strong> <?php echo strtoupper($currentStatus['verification_status']); ?></p>
        <p><strong>Validation Notes:</strong> <?php echo htmlspecialchars($currentStatus['validation_notes'] ?? 'N/A'); ?></p>
    <?php endif; ?>
    <a href="index.php">← Back to Dashboard</a>
</div>
</body>
</html>
