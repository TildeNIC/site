<?php
require_once 'initdb.php';

session_start();

// Function to register user
function registerUser($username, $password, $pdo) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);
}

// Function to check if username exists
function doesUserExist($username, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn() > 0;
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!doesUserExist($username, $pdo)) {
        registerUser($username, $password, $pdo);
        $_SESSION['username'] = $username;
        header("Location: https://tildenic.org/?page=domain_register");
        exit;
    } else {
        $error = "Username already exists.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Register</h1>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" name="register" value="Register">
    </form>
    <?php if (!empty($error)): ?>
        <p><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>
