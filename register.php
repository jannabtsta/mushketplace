<?php
$message = '';

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
        $message = "<span class='success'>✅ Registration successful. <a href='index.php'>Login here</a></span>";
    } else {
        $message = "<span class='error'>❌ Error: " . $conn->error . "</span>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍄 Register | Mushketplace</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

        .register-container {
            backdrop-filter: blur(12px);
            background: #ffb3b3;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            width: 360px;
            text-align: center;
            color: #fff;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .register-container h2 {
            font-family: "Jua", sans-serif;
            font-size: 36px;
            font-weight: 700;
            color: #5e1111;
            margin-bottom: 25px;
        }

        .register-container input[type="text"],
        .register-container input[type="email"],
        .register-container input[type="password"],
        .register-container select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 10px;
            outline: none;
            font-size: 14px;
        }

        .register-container input:focus,
        .register-container select:focus {
            box-shadow: 0 0 6px #7affd4;
        }

        .register-container button {
            width: 100%;
            padding: 12px;
            background-color: #5e1111;
            border: none;
            border-radius: 10px;
            color: white;
            font-family: "Jua", sans-serif;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .register-container button:hover {
            background-color: #ff9898;
            transform: scale(1.02);
        }

        .register-container a {
            display: block;
            margin-top: 20px;
            color: #fffdfd;
            text-decoration: none;
            font-weight: bold;
        }

        .register-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    
    <div class="register-container">
        <form method="POST">
            <h2>🍄 Register</h2>
<?php if ($message): ?>
    <div class="status-message">
        <?= $message ?>
    </div>
<?php endif; ?>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="address" placeholder="Home Address" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="farmer">Farmer</option>
                <option value="consumer">Consumer</option>
            </select>
            <button type="submit">Register</button>
            <a href="index.php">← Back to Login</a>
        </form>
    </div>
</body>
</html>

