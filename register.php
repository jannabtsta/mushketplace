<?php
$conn = new mysqli("localhost", "root", "", "mushket");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $address = $_POST["address"];
    $role = $_POST["role"];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, address, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $password, $address, $role);

    if ($stmt->execute()) {
        echo "✅ Registration successful. <a href='index.php'>Login here</a>";
    } else {
        echo "❌ Error: " . $conn->error;
    }
    $stmt->close();
}
?>

<form method="POST">
    <h2>Register</h2>
    Name: <input type="text" name="name" required><br>
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    Address: <input type="text" name="address" required><br>
    Role:
    <select name="role">
        <option value="farmer">Farmer</option>
        <option value="consumer">Consumer</option>
    </select><br>
    <button type="submit">Register</button>
   <a href="index.php">Login</a>
</form>
