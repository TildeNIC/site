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
        // Regenerate session ID upon successful login
        session_regenerate_id();

        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time(); // track start of session
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR']; // store user IP
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // store user agent

        header("Location: /?page=user_domains");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// Session timeout logic
$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // last request was more than 30 minutes ago
    session_unset(); // unset $_SESSION variable
    session_destroy(); // destroy session data
    header("Location: /?page=login"); // redirect to login page
    exit;
}

$_SESSION['last_activity'] = time(); // update last activity time

// Check if user IP or user agent has changed
if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR'] ||
    isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: /?page=login");
    exit;
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