<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$consumer_id = $_SESSION["user_id"];


$today = date('Y-m-d');
$result = $conn->query("
    SELECT stock.id as stock_id, users.id as farmer_id, users.name, stock.available_kg
    FROM stock
    JOIN users ON stock.user_id = users.id
    WHERE stock.date = '$today' AND stock.available_kg > 0
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍄 Consumer Dashboard</title>

  
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

        .container {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 50px auto;
            width: 90%;
            max-width: 700px;
        }

        h2 {
            font-family: 'Jua', sans-serif;
            color: #6b1e1e;
            font-size: 30px;
            margin-bottom: 10px;
            text-align: center;
        }

        h3 {
            color: #333;
            margin-top: 20px;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        select, input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 14px;
        }

        button[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Jua', sans-serif;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }

        .paypal-section {
            margin: 30px 0;
            text-align: center;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            margin: 0 10px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .vendor-box {
            background-color:rgb(252, 187, 187);
            border-radius: 8px;
            padding: 20px;
            margin-top: 40px;
            text-align: center;
            border: 1px solid #eee;
        }

        .vendor-box button {
            background-color: #d63384;
            font-family: 'Jua', sans-serif;
            font-size: 20px;
        }

        .vendor-box button:hover {
            background-color: #c2186b;
        }
.links {
    margin-top: 30px;
    text-align: center;
}

.btn-link {
    display: inline-block;
    margin: 8px;
    padding: 10px 20px;
    background-color:rgb(252, 187, 187);
    color: #4a1a1a !important;
   
    text-decoration: none;
    font-family: 'Jua', sans-serif;
    border-radius: 8px;
    font-size: 16px;
    transition: background-color 0.3s ease, transform 0.2s;
}

.btn-link:hover {
    background-color: #9c2b2b;
    transform: scale(1.03);
}

.btn-link.logout {
    background-color:rgb(252, 187, 187);
}

.btn-link.logout:hover {
    background-color:  #9c2b2b;
}
    </style>
</head>
<body>
<div class="container">
    <h2>Welcome, <?= $_SESSION["name"]; ?>!</h2>
    <h3>Available Farmers Today</h3>

    <?php if ($result->num_rows > 0): ?>
        <form method="POST" action="fake_paypal.php">
            <label>Select Farmer:</label>
            <select name="selection" required>
                <?php while ($row = $result->fetch_assoc()): 
                    $value = $row['stock_id'] . "-" . $row['farmer_id']; ?>
                    <option value="<?= $value ?>">
                        <?= $row['name'] ?> — <?= $row['available_kg'] ?> kg available
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Quantity to Order (kg):</label>
            <input type="number" name="quantity" step="0.1" min="0.1" required>

            <input type="hidden" name="consumer_id" value="<?= $_SESSION['user_id'] ?>">

            <button type="submit">Confirm Payment</button>
        </form>


        <div class="paypal-section">
            <h3>OR Pay with PayPal</h3>
            <div id="paypal-button-container"></div>
        </div>

        <script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD"></script>
        <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: { value: '100.00' }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    alert('✅ Payment completed by ' + details.payer.name.given_name);
                });
            }
        }).render('#paypal-button-container');
        </script>

    <?php else: ?>
        <p>No available stock from any farmer today.</p>
    <?php endif; ?>

   <div class="links">
    <a href="order_status.php" class="btn-link">📦 Check My Orders</a>
    <a href="logout.php" class="btn-link logout">🚪 Logout</a>
</div>

    <div class="vendor-box">
        <h3>🍄 Want to become a vendor?</h3>
        <p>Sell your products on Mushketplace by completing vendor verification.</p>
        <a href="vendor_verification_form.php">
            <button>Start Vendor Verification</button>
        </a>
    </div>
</div>
</body>
</html>
