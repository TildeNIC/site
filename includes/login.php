<?php
require_once 'initdb.php';

session_start();

// Function to check user credentials
function checkCredentials($username, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user && password_verify($password, $user['password']);
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (checkCredentials($username, $password, $pdo)) {
        $_SESSION['username'] = $username;
        header("Location: /?page=user_domains");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Login</h1>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" name="login" value="Login">
        <!-- Registration Button -->
        <input type="button" onclick="location.href='/?page=register';" value="Register">
    </form>
    <?php if (!empty($error)): ?>
        <p><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>
