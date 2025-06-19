<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verificationId = $_POST['id'];
    $action = $_POST['action'];
    $adminId = $_SESSION['user_id'];
    $notes = $_POST['notes'] ?? '';
    $email = $_POST['email'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE vendor_verification SET verification_status = 'approved', reviewed_at = NOW(), reviewed_by = ?, approval_notes = ? WHERE id = ?");
        $stmt->bind_param("isi", $adminId, $notes, $verificationId);
        $stmt->execute();

        
        $conn->query("UPDATE users SET role = 'farmer' WHERE id = (SELECT user_id FROM vendor_verification WHERE id = $verificationId)");

        
        require_once 'send_otp_mail.php';
        sendOTPEmail($email, "✅ Your vendor verification has been approved! You can now list mushrooms on Mushketplace.");

    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE vendor_verification SET verification_status = 'rejected', reviewed_at = NOW(), reviewed_by = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("isi", $adminId, $notes, $verificationId);
        $stmt->execute();
    }
}


$result = $conn->query("SELECT vv.*, u.email FROM vendor_verification vv JOIN users u ON vv.user_id = u.id WHERE verification_status = 'pending'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>🍄 Admin Verification Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Jua', sans-serif;
            background: #fceeee;
            padding: 30px;
            margin: 0;
        }

        h1 {
            text-align: center;
            color: #6b1d1d;
            margin-bottom: 30px;
            font-size: 36px;
            text-shadow: 1px 1px 2px #fff;
        }

        .verification-box {
            background: #fff;
            padding: 25px 30px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h2 {
            color: #a33;
            font-size: 22px;
            margin-top: 0;
        }

        p {
            margin: 6px 0;
        }

        img {
            max-width: 180px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            font-family: 'Jua', sans-serif;
            margin-top: 12px;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: scale(1.03);
        }

        .approve {
            background-color: #28a745;
            color: white;
            margin-right: 10px;
        }

        .reject {
            background-color: #dc3545;
            color: white;
        }

        .top-links {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            align-items: center;
        }

        .top-links a {
            text-decoration: none;
            color: #6b1d1d;
            font-weight: bold;
            background: #ffd1d1;
            padding: 10px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
        }

        .top-links a:hover {
            background-color: #ffc2c2;
        }
    </style>
</head>
<body>

    <h1>🍄 Admin Verification Panel</h1>

    <div class="top-links">
        <a href="index.php">← Back to Login</a>
        <a href="security_dashboard.php">📊 View Login Logs</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="verification-box">
            <h2><?= htmlspecialchars($row['business_name']) ?></h2>
            <p><strong>Address:</strong> <?= htmlspecialchars($row['business_address']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($row['business_phone']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($row['business_email']) ?></p>

            <p><strong>Valid ID 1:</strong><br>
                <img src="uploads/verification_docs/<?= $row['id_document1'] ?>" alt="ID 1">
            </p>

            <p><strong>Valid ID 2:</strong><br>
                <img src="uploads/verification_docs/<?= $row['id_document2'] ?>" alt="ID 2">
            </p>

            <form method="POST">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="hidden" name="email" value="<?= $row['email'] ?>">
                <label>Approval Notes / Rejection Reason:</label>
                <textarea name="notes"></textarea><br>
                <button class="btn approve" name="action" value="approve">Approve</button>
                <button class="btn reject" name="action" value="reject">Reject</button>
            </form>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center; font-size:18px; color:#888;">✅ No pending verifications at the moment.</p>
<?php endif; ?>

</body>
</html>

