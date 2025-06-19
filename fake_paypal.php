<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mushket");

// Safety check: confirm user is a logged-in consumer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fake Payment Processing</title>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Jua', sans-serif;
            background-color: #fff3f3;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .confirmation-box {
            background: #ffe2e2;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 90%;
            max-width: 500px;
            animation: fadeIn 0.5s ease;
        }

        h2 {
            color: #8c2e2e;
            font-size: 30px;
        }

        p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .buttons {
            margin-top: 20px;
        }

        .buttons a {
            text-decoration: none;
            display: inline-block;
            background: #d43f3f;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            margin: 0 10px;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .buttons a:hover {
            background: #b53030;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="confirmation-box">
    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $selection = explode("-", $_POST["selection"]);
        $stock_id = intval($selection[0]);
        $farmer_id = intval($selection[1]);
        $quantity = floatval($_POST["quantity"]);
        $consumer_id = intval($_POST["consumer_id"]);

        // Fetch available stock
        $stmt = $conn->prepare("SELECT available_kg FROM stock WHERE id = ?");
        $stmt->bind_param("i", $stock_id);
        $stmt->execute();
        $stmt->bind_result($available);
        $stmt->fetch();
        $stmt->close();

        if ($quantity > $available) {
            echo "<h2>❌ Not enough stock available.</h2>";
            echo "<div class='buttons'><a href='consumer.php'>← Go back</a></div>";
            exit();
        }

        // Simulate "successful payment"
        echo "<h2>✅ Payment Simulated Successfully!</h2>";
        echo "<p>Processing your order...</p>";

        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (consumer_id, farmer_id, stock_id, quantity_kg) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $consumer_id, $farmer_id, $stock_id, $quantity);
        $stmt->execute();
        $stmt->close();

        // Subtract from stock
        $stmt = $conn->prepare("UPDATE stock SET available_kg = available_kg - ? WHERE id = ?");
        $stmt->bind_param("di", $quantity, $stock_id);
        $stmt->execute();
        $stmt->close();

        echo "<p>✅ Order placed and stock updated.</p>";
        echo "<div class='buttons'>
                <a href='order_status.php'>📦 Check Order Status</a>
                <a href='consumer.php'>🏡 Back to Dashboard</a>
              </div>";
    } else {
        echo "<h2>⚠️ Invalid access.</h2>";
        echo "<p>Please go through the order form.</p>";
        echo "<div class='buttons'><a href='consumer.php'>← Go back</a></div>";
    }
    ?>
</div>

</body>
</html>
