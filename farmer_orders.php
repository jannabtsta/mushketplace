<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$farmer_id = $_SESSION["user_id"];
$today = date('Y-m-d');


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_delivery'])) {
    $order_id = intval($_POST['order_id']);
    $rider_name = $_POST['rider_name'];
    $rider_contact = $_POST['rider_contact'];
    $rider_info = "Rider: $rider_name, Contact: $rider_contact";

    $stmt = $conn->prepare("UPDATE orders SET status='out for delivery', rider_info=? WHERE id=? AND farmer_id=?");
    $stmt->bind_param("sii", $rider_info, $order_id, $farmer_id);
    $stmt->execute();
    $stmt->close();
}


$result = $conn->query("
    SELECT orders.*, users.name AS consumer_name
    FROM orders
    JOIN users ON orders.consumer_id = users.id
    JOIN stock ON orders.stock_id = stock.id
    WHERE orders.farmer_id = $farmer_id AND DATE(stock.date) = '$today'
    ORDER BY orders.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍄 Orders Received - Mushketplace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Jua', sans-serif;
            background: url('mushketBG.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 40px;
            color: #333;
        }

        .orders-container {
            background-color: #fffaf5;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            max-width: 1000px;
            margin: auto;
        }

        h2 {
            text-align: center;
            font-size: 32px;
            margin-bottom: 25px;
            color: #842029;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #e6cfcf;
            text-align: center;
            font-size: 15px;
        }

        th {
            background-color: #f9d7d7;
            color: #5c1212;
        }

        tr:nth-child(even) {
            background-color: #fff0f0;
        }

        input[type="text"] {
            padding: 6px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin: 4px 0;
            width: 90%;
        }

        button {
            background-color: #c0392b;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 5px;
            font-family: 'Jua', sans-serif;
        }

        button:hover {
            background-color: #e74c3c;
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
    <div class="orders-container">
        <h2>📦 Orders Received Today</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Consumer</th>
                <th>Quantity (kg)</th>
                <th>Status</th>
                <th>Placed At</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['consumer_name']) ?></td>
                    <td><?= $row['quantity_kg'] ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <?php if ($row['status'] == 'pending') { ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                <input type="text" name="rider_name" placeholder="Rider Name" required><br>
                                <input type="text" name="rider_contact" placeholder="Contact No" required><br>
                                <button type="submit" name="mark_delivery">Mark as Out for Delivery</button>
                            </form>
                        <?php } else { echo '-'; } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <a href="farmer.php">← Back to Dashboard</a>
    </div>
</body>
</html>
