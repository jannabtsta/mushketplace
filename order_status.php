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

<h2>My Orders</h2>
<table border="1">
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
            <td><?= $row['status'] ?></td>
            <td><?= $row['rider_info'] ?? 'N/A' ?></td>
            <td>
                <?php if ($row['status'] == 'out for delivery') { ?>
                    <a href="?confirm=<?= $row['id'] ?>">Confirm Received</a>
                <?php } else { echo '-'; } ?>
            </td>
        </tr>
    <?php } ?>
</table>

<a href="consumer.php">Back</a>
