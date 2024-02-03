<?php
require_once 'initdb.php';
session_start();

// Function to get domain information
function getDomainInfo($domain, $pdo) {
    $stmt = $pdo->prepare("SELECT domains.domain_name, users.username, domains.ip_address, domains.created_at FROM domains JOIN users ON domains.user_id = users.id WHERE domains.domain_name = ?");
    $stmt->execute([$domain]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
$searchError = '';
$domainInfo = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $domain = $_POST['domain'];

    // Adjusted regex to allow for '.tilde' extension
    if (!preg_match('/^[a-zA-Z0-9-]+\.tilde$/', $domain)) {
        $searchError = "Invalid domain format. Only letters, numbers, and hyphens followed by '.tilde' are allowed.";
    } else {
        $domainInfo = getDomainInfo($domain, $pdo);
        if (!$domainInfo) {
            $searchError = "Domain not found.";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>.tilde Whois Lookup</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
      <header>
        <nav>
            <?php if (!isset($_SESSION['username'])): ?>
                <a href="/?page=main">Home</a> |
                <a href="/?page=login">Login</a> |
                <a href="/?page=register">Register</a> |
                <a href="/?page=whois">WHOIS</a>
            <?php else: ?>
                <a href="/?page=main">Home</a> |
                <a href="/?page=user_domains">My Account</a> |
                <a href="/?page=domain_register">Register Domain</a> |
                <a href="/?page=whois">WHOIS</a> |
                <a href="/?page=main&action=logout">Logout</a><br><br>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
        </nav>
    </header>
    <h1>.tilde Whois Lookup</h1>
    <form method="post">
        <label for="domain">Domain Name:</label>
        <input type="text" id="domain" name="domain" required>
        <input type="submit" name="search" value="Search">
    </form>

    <?php if ($searchError): ?>
        <p><?php echo $searchError; ?></p>
    <?php elseif ($domainInfo): ?>
        <h2>Domain Information</h2>
        <p>Domain: <?php echo htmlspecialchars($domainInfo['domain_name']); ?></p>
        <p>Owner: <?php echo htmlspecialchars($domainInfo['username']); ?></p>
        <p>IP Address: <?php echo htmlspecialchars($domainInfo['ip_address']); ?></p>
        <p>Registered On: <?php echo htmlspecialchars($domainInfo['created_at']); ?></p>
    <?php endif; ?>
</body>
</html>

