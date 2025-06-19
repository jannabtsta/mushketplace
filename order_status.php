<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$consumer_id = $_SESSION["user_id"];

// Confirm delivery
if (isset($_GET["confirm"])) {
    $order_id = $_GET["confirm"];
    $conn->query("UPDATE orders SET status='delivered' WHERE id=$order_id AND consumer_id=$consumer_id");
}

// Fetch orders for this consumer
$result = $conn->query("
    SELECT orders.*, users.name AS farmer_name
    FROM orders
    JOIN users ON orders.farmer_id = users.id
    WHERE consumer_id = $consumer_id
    ORDER BY orders.created_at DESC
");
?>

<<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Jua&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Jua', sans-serif;
            background-color: #fceeee;
            padding: 40px;
            color: #4a1a1a;
        }

        h2 {
            font-size: 32px;
            text-align: center;
            margin-bottom: 30px;
            color: #8b1e1e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        th, td {
            padding: 12px 18px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #ffb3b3;
            color: #4a1a1a;
        }

        tr:nth-child(even) {
            background-color: #fff6f6;
        }

        a.button {
            display: inline-block;
            background-color: #ff8080;
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        a.button:hover {
            background-color: #ff5e5e;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            background-color: #4a1a1a;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
        }

        .back-link:hover {
            background-color: #6a2a2a;
        }
    </style>
</head>
<body>

<h2>🍄 My Orders</h2>

<table>
    <tr>
        <th>Order ID</th>
        <th>Farmer</th>
        <th>Quantity (kg)</th>
        <th>Status</th>
        <th>Rider Info</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['farmer_name']) ?></td>
            <td><?= $row['quantity_kg'] ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= $row['rider_info'] ?? 'N/A' ?></td>
            <td>
                <?php if ($row['status'] == 'out for delivery') { ?>
                    <a class="button" href="?confirm=<?= $row['id'] ?>">Confirm Received</a>
                <?php } else { echo '-'; } ?>
            </td>
        </tr>
    <?php } ?>
</table>

<div style="text-align: center;">
    <a class="back-link" href="consumer.php">← Back to Dashboard</a>
</div>

</body>
</html>
