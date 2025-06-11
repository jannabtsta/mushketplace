<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$consumer_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selection = explode("-", $_POST["selection"]);
    $stock_id = intval($selection[0]);
    $farmer_id = intval($selection[1]);
    $order_qty = $_POST["quantity"];
    $consumer_id = $_SESSION["user_id"];

    // Get current available stock
    $stmt = $conn->prepare("SELECT available_kg FROM stock WHERE id = ?");
    $stmt->bind_param("i", $stock_id);
    $stmt->execute();
    $stmt->bind_result($available);
    $stmt->fetch();
    $stmt->close();

    if ($order_qty > $available) {
        echo "❌ Not enough stock available.";
    } else {
        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (consumer_id, farmer_id, stock_id, quantity_kg) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $consumer_id, $farmer_id, $stock_id, $order_qty);
        $stmt->execute();
        $stmt->close();

        // Update stock
        $stmt = $conn->prepare("UPDATE stock SET available_kg = available_kg - ? WHERE id = ?");
        $stmt->bind_param("di", $order_qty, $stock_id);
        $stmt->execute();
        $stmt->close();

        echo "✅ Order placed successfully.";
    }
}

// Fetch all farmers with available stock today
$today = date('Y-m-d');
$result = $conn->query("
    SELECT stock.id as stock_id, users.id as farmer_id, users.name, stock.available_kg
    FROM stock
    JOIN users ON stock.user_id = users.id
    WHERE stock.date = '$today' AND stock.available_kg > 0
");
?>

<h2>Welcome, <?php echo $_SESSION["name"]; ?> (Consumer)</h2>
<h3>Available Farmers Today</h3>

<form method="POST">
     <label>Select Farmer:</label><br>
    <select name="selection" required>
        <?php
        // Reset result pointer if needed
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) { 
            $value = $row['stock_id'] . "-" . $row['farmer_id'];
        ?>
            <option value="<?= $value ?>">
                <?= $row['name'] ?> — <?= $row['available_kg'] ?> kg available
            </option>
        <?php } ?>
    </select><br>
   Quantity to Order (kg): 
    <input type="number" name="quantity" step="0.1" min="0.1" required><br>
    <button type="submit">Order</button>
</form>

<a href="order_status.php">Check My Orders</a><br>
<a href="logout.php">Logout</a>
