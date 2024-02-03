<?php
require_once 'initdb.php';

session_start();

// Initialize error messages array if not set
if (!isset($_SESSION['error_messages'])) {
    $_SESSION['error_messages'] = [];
}

// Session timeout logic
$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Last request was more than 30 minutes ago
    session_unset(); // Unset $_SESSION variable
    session_destroy(); // Destroy session data
    header("Location: /?page=login"); // Redirect to login page
    exit;
}

$_SESSION['last_activity'] = time(); // Update last activity time

// Check if user IP or user agent has changed
if ((isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) ||
    (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'])) {
    session_unset();
    session_destroy();
    header("Location: /?page=login");
    exit;
}

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: /?page=login");
    exit;
}

// Restricted domains that cannot be registered
$restrictedDomains = ['master.tilde', 'nic.tilde', 'tilde.tilde']; // Add more as needed

// Function to register domain
function registerDomain($domain, $userId, $pdo, $restrictedDomains) {
    // Ensure '.tilde' is appended only once
    if (!str_ends_with($domain, '.tilde')) {
        $domain .= '.tilde';
    }

    // Debug: Output the full domain name
//    echo "Attempting to register domain: " . htmlspecialchars($domain) . "<br>";

    // Validate domain format (excluding the '.tilde' part)
    $domainNameWithoutSuffix = str_replace('.tilde', '', $domain);
    if (!preg_match('/^[a-zA-Z0-9\-]+$/', $domainNameWithoutSuffix)) {
//        echo "Error: Invalid domain format detected.<br>"; // Debug message
        return "Error: Invalid domain format. Only letters, numbers, and hyphens are allowed.";
    }

    if (in_array($domain, $restrictedDomains)) {
//        echo "Error: Domain is restricted.<br>"; // Debug message
        return "Error: The domain '$domain' cannot be registered.";
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO domains (user_id, domain_name) VALUES (?, ?)");
        $stmt->execute([$userId, $domain]);
 //       echo "Domain registered successfully.<br>"; // Debug message
        return "Domain registered successfully: " . htmlspecialchars($domain);
    } catch (PDOException $e) {
 //       echo "Database error occurred.<br>"; // Debug message
        if ($e->getCode() == 23000) {
            return"Error: The domain '$domain' is already registered.";
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
                <a href="https://tildenic.org/?page=register">Register</a> |
                <a href="/?page=whois">WHOIS</a>
            <?php else: ?>
                <a href="https://tildenic.org/?page=main">Home</a> |
                <a href="https://tildenic.org/?page=user_domains">My Account</a> |
                <a href="https://tildenic.org/?page=domain_register">Register Domain</a> |
                <a href="/?page=whois">WHOIS</a> |
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