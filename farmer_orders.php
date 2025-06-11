<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$farmer_id = $_SESSION["user_id"];
$today = date('Y-m-d');

// Handle delivery form submission
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

// Fetch today's orders
$result = $conn->query("
    SELECT orders.*, users.name AS consumer_name
    FROM orders
    JOIN users ON orders.consumer_id = users.id
    JOIN stock ON orders.stock_id = stock.id
    WHERE orders.farmer_id = $farmer_id AND DATE(stock.date) = '$today'
    ORDER BY orders.created_at DESC
");
?>

<h2>Orders Received Today</h2>
<table border="1">
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
            <td><?= $row['status'] ?></td>
            <td><?= $row['created_at'] ?></td>
            <td>
                <?php if ($row['status'] == 'pending') { ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                        Rider Name: <input type="text" name="rider_name" required><br>
                        Contact No: <input type="text" name="rider_contact" required><br>
                        <button type="submit" name="mark_delivery">Mark as Out for Delivery</button>
                    </form>
                <?php } else { echo '-'; } ?>
            </td>
        </tr>
    <?php } ?>
</table>

<a href="farmer.php">Back</a>
