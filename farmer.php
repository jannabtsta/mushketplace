<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = $_POST["quantity"];
    $today = date('Y-m-d');

    // Check if the farmer already has stock today
    $check = $conn->prepare("SELECT id FROM stock WHERE user_id = ? AND date = ?");
    $check->bind_param("is", $user_id, $today);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "❌ You've already submitted stock for today.";
    } else {
        $stmt = $conn->prepare("INSERT INTO stock (user_id, quantity_kg, available_kg, date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idds", $user_id, $quantity, $quantity, $today);
        $stmt->execute();
        echo "✅ Stock submitted successfully.";
        $stmt->close();
    }
    $check->close();
}
?>

<h2>Welcome, <?php echo $_SESSION["name"]; ?> (Farmer)</h2>
<form method="POST">
    Quantity of Oyster Mushrooms Available Today (kg): 
    <input type="number" name="quantity" step="0.1" min="0.1" required><br>
    <button type="submit">Submit Stock</button>
</form>

<a href="farmer_orders.php">View Today's Orders</a>
<a href="logout.php">Logout</a>
