<?php
require_once 'initdb.php';

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: https://tildenic.org/?page=login");
    exit;
}

// Restricted domains that cannot be registered
$restrictedDomains = ['master.tilde', 'nic.tilde', 'tilde.tilde']; // Add more as needed

// Function to register domain
function registerDomain($domain, $userId, $pdo, $restrictedDomains) {
    if (in_array($domain, $restrictedDomains)) {
        return "Error: The domain '$domain' cannot be registered.";
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO domains (user_id, domain_name) VALUES (?, ?)");
        $stmt->execute([$userId, $domain]);
        return "Domain registered successfully: " . htmlspecialchars($domain);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return "Error: The domain '$domain' is already registered.";
        } else {
            return "Error: An error occurred while registering the domain.";
        }
    }
}

// Function to get user ID
function getUserId($username, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn();
}

// Handle domain registration
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registerdomain'])) {
    $domain = $_POST['registerdomain'] . '.tilde';
    $userId = getUserId($_SESSION['username'], $pdo);

    $message = registerDomain($domain, $userId, $pdo, $restrictedDomains);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Domain</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        function submitForm() {
            var form = document.getElementById("domainForm");
            var domain = document.getElementById("domain").value;
            form.action = "https://tildenic.org/?page=domain_register&registerdomain=" + encodeURIComponent(domain);
            form.submit();
        }
    </script>
</head>
<body>
      <header>
        <nav>
            <?php if (!isset($_SESSION['username'])): ?>
                <a href="https://tildenic.org/?page=login">Login</a> |
                <a href="https://tildenic.org/?page=register">Register</a>
            <?php else: ?>
                <a href="https://tildenic.org/?page=main">Home</a> |
                <a href="https://tildenic.org/?page=user_domains">My Account</a> |
                <a href="https://tildenic.org/?page=domain_register">Register Domain</a> |
                <a href="https://tildenic.org/?page=main&action=logout">Logout</a><br><br>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
      </nav>
    </header>
    <h1>Register Domain</h1>
    <form id="domainForm" method="post" onsubmit="submitForm(); return false;">
        <div class="domain-input-container">
        <label for="domain">Domain Name:</label>
        <input type="text" id="domain" name="registerdomain" required>
        <span class="tilde-extension">.tilde</span>
        </div>
      <input type="submit" value="Register Domain">
    </form>
    <?php if (!empty($message)): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
</body>
</html>