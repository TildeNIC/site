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
function removeDomain($domainId, $userId, $pdo) {
    // First, verify that the domain belongs to the user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM domains WHERE id = ? AND user_id = ?");
    $stmt->execute([$domainId, $userId]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // The domain does not belong to the user
        return false;
    }

    // Proceed with deletion since the domain belongs to the user
    $stmt = $pdo->prepare("DELETE FROM domains WHERE id = ?");
    $stmt->execute([$domainId]);
    return true;
}


// Function to update domain's IP address
function updateDomainIP($domainId, $userId, $ipAddress, $pdo) {
    // Validate the IP address
    if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
        // The IP address is not valid
        return false;
    }

    // Verify that the domain belongs to the user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM domains WHERE id = ? AND user_id = ?");
    $stmt->execute([$domainId, $userId]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // The domain does not belong to the user
        return false;
    }

    // Proceed with IP address update since the domain belongs to the user
    $stmt = $pdo->prepare("UPDATE domains SET ip_address = ? WHERE id = ?");
    $stmt->execute([$ipAddress, $domainId]);
    return true;
}

// Handle domain removal
if (isset($_GET['remove'])) {
    $userId = getUserId($_SESSION['username'], $pdo);
    $domainId = $_GET['remove'];

    $result = removeDomain($domainId, $userId, $pdo);
    if ($result !== true) {
        $_SESSION['error_messages'][] = "Error: You do not have permission to delete this domain.";
    } else {
        header("Location: https://tildenic.org/?page=user_domains");
        exit;
    }
}


// Handle IP address update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_ip'])) {
    $domainId = $_POST['domain_id'];
    $userId = getUserId($_SESSION['username'], $pdo);
    $ipAddress = $_POST['ip_address'];

    $result = updateDomainIP($domainId, $userId, $ipAddress, $pdo);
    if ($result !== true) {
        $_SESSION['error_messages'][] = "Error: Invalid IP address or you do not have permission to update the IP address for this domain.";
    } else {
        header("Location: https://tildenic.org/?page=user_domains");
        exit;
    }
}
// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: https://tildenic.org/?page=login");
    exit;
}
// Handle form submission for domain removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_domain'])) {
    $domainId = $_POST['domain_id'];
    $userId = getUserId($_SESSION['username'], $pdo);

    if (!removeDomain($domainId, $userId, $pdo)) {
        $_SESSION['error_messages'][] = "Error: You do not have permission to delete this domain.";
    } else {
        header("Location: https://tildenic.org/?page=user_domains");
        exit;
    }
}

// Redirect to the user domains page after processing the form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header("Location: https://tildenic.org/?page=user_domains");
    exit;
}

// Function to validate and update IP addresses for a user's domains
function validateAndUpdateIPs($userId, $pdo) {
    // Fetch all domains for the user
    $stmt = $pdo->prepare("SELECT id, ip_address FROM domains WHERE user_id = ?");
    $stmt->execute([$userId]);
    $domains = $stmt->fetchAll();

    $invalidIPs = [];

    foreach ($domains as $domain) {
        $domainId = $domain['id'];
        $ipAddress = $domain['ip_address'];

        // Check if the IP address is valid
        if (!empty($ipAddress) && !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            // IP address is invalid, update the domain to remove the IP address
            $updateStmt = $pdo->prepare("UPDATE domains SET ip_address = NULL WHERE id = ?");
            $updateStmt->execute([$domainId]);

            // Add to the list of domains with invalid IPs
            $invalidIPs[] = $domainId;
        }
    }

    return $invalidIPs;
}


// When the user accesses their domain management page
$userId = getUserId($_SESSION['username'], $pdo);
$invalidIPDomains = validateAndUpdateIPs($userId, $pdo);

if (!empty($invalidIPDomains)) {
    // Inform the user that some IP addresses were invalid and have been removed
    echo "Invalid IP addresses were found and removed from the following domains: " . implode(", ", $invalidIPDomains) . ". Please update them.";
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
                <a href="https://tildenic.org/?page=register">Register</a> |
                <a href="/?page=whois">WHOIS</a>
            <?php else: ?>
                <a href="https://tildenic.org/?page=main">Home</a> |
                <a href="https://tildenic.org/?page=user_domains" active>My Account</a> |
                <a href="https://tildenic.org/?page=domain_register">Register Domain</a> |
                <a href="/?page=whois">WHOIS</a> |
                <a href="https://tildenic.org/?page=main&action=logout">Logout</a><br><br>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
      </nav>
    </header>
  <!-- Error message display -->
<?php if (!empty($_SESSION['error_messages'])): ?>
    <div class="error-messages">
        <?php foreach ($_SESSION['error_messages'] as $message): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endforeach; ?>
        <?php $_SESSION['error_messages'] = []; // Clear error messages after displaying ?>
    </div>
<?php endif; ?><br>
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
