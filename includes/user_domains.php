<?php
require_once 'initdb.php';

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: https://tildenic.org/?page=login");
    exit;
}

// Function to get user ID
function getUserId($username, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn();
}

// Function to get user's domains
function getUserDomains($userId, $pdo) {
    $stmt = $pdo->prepare("SELECT id, domain_name, ip_address FROM domains WHERE user_id = ?"); // Fetching ip_address
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Function to remove a domain
function removeDomain($domainId, $pdo) {
    $stmt = $pdo->prepare("DELETE FROM domains WHERE id = ?");
    $stmt->execute([$domainId]);
}

// Function to update domain's IP address
function updateDomainIP($domainId, $ipAddress, $pdo) {
    $stmt = $pdo->prepare("UPDATE domains SET ip_address = ? WHERE id = ?"); // Updating ip_address
    $stmt->execute([$ipAddress, $domainId]);
}

// Handle domain removal
if (isset($_GET['remove'])) {
    removeDomain($_GET['remove'], $pdo);
    header("Location: https://tildenic.org/?page=user_domains");
    exit;
}

// Handle IP address update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_ip'])) {
    $domainId = $_POST['domain_id'];
    $ipAddress = $_POST['ip_address'];
    updateDomainIP($domainId, $ipAddress, $pdo);
    header("Location: https://tildenic.org/?page=user_domains");
    exit;
}
// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: https://tildenic.org/?page=login");
    exit;
}
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $domainId = $_POST['domain_id'];

    if (isset($_POST['update_ip'])) {
        // Update IP address
        $ipAddress = $_POST['ip_address'];
        updateDomainIP($domainId, $ipAddress, $pdo);
    } elseif (isset($_POST['remove_domain'])) {
        // Remove domain
        removeDomain($domainId, $pdo);
    }

    header("Location: https://tildenic.org/?page=user_domains");
    exit;
}

$userId = getUserId($_SESSION['username'], $pdo);
$domains = getUserDomains($userId, $pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
      <header>
        <nav>
            <?php if (!isset($_SESSION['username'])): ?>
                <a href="https://tildenic.org/?page=login">Login</a> |
                <a href="https://tildenic.org/?page=register">Register</a>
            <?php else: ?>
                <a href="https://tildenic.org/?page=main">Home</a> |
                <a href="https://tildenic.org/?page=user_domains" active>My Account</a> |
                <a href="https://tildenic.org/?page=domain_register">Register Domain</a> |
                <a href="https://tildenic.org/?page=main&action=logout">Logout</a><br><br>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
      </nav>
    </header>
      <h2>Your Domains</h2>
    <ul>
        <?php foreach ($domains as $domain): ?>
            <li>
                <?php echo htmlspecialchars($domain['domain_name']); ?>
                <form method="post" class="domain-form">
                    <input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
                    <input type="text" name="ip_address" placeholder="Default IP Address" value="<?php echo htmlspecialchars($domain['ip_address'] ?? ''); ?>">
                    <input type="submit" name="update_ip" value="Update IP">
                    <!-- Remove button -->
                    <input type="submit" name="remove_domain" value="Remove" class="remove-button">
                </form>
            </li>
        <?php endforeach; ?>
  </ul>
</body>
</html>
