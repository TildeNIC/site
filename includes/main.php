<?php
require_once 'initdb.php';
session_start();

// Logout handling
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}

// Function to get DNS server information from BIND files
function getDnsServersInfo() {
    $masterFile = '../dottilde/db.master.tilde';
    $servers = [];
    $nsFilter = ['ns1', 'ns2', 'ns3']; // Add more nameserver identifiers as needed

      // Manually assigned geographical areas for each nameserver
    $nsGeographicalAreas = [
        'ns1' => 'Quebec, Canada', // Replace with actual locations
        'ns2' => 'Frankfurt, Germany',
        'ns3' => 'Sydney, Australia',
        // Add more as needed
    ];
  
    if (file_exists($masterFile)) {
        $content = file_get_contents($masterFile);
        // Regex to match A records (IPv4)
        preg_match_all('/(\S+)\s+IN\s+A\s+(\S+)/', $content, $aMatches);
        // Regex to match AAAA records (IPv6)
        preg_match_all('/(\S+)\s+IN\s+AAAA\s+(\S+)/', $content, $aaaaMatches);

        $ipv4Records = array_combine($aMatches[1], $aMatches[2]);
        $ipv6Records = array_combine($aaaaMatches[1], $aaaaMatches[2]);

        foreach ($nsFilter as $nsName) {
            $ipv4 = isset($ipv4Records[$nsName]) ? $ipv4Records[$nsName] : 'IPv4 not found';
            $ipv6 = isset($ipv6Records[$nsName]) ? $ipv6Records[$nsName] : 'IPv6 not found';
            $geographicalArea = isset($nsGeographicalAreas[$nsName]) ? $nsGeographicalAreas[$nsName] : 'Unknown Location';

            $servers[] = [
                'hostname' => $nsName, 
                'ipv4' => $ipv4, 
                'ipv6' => $ipv6, 
                'location' => $geographicalArea
            ];
        }
    }

    return $servers;
}

$dnsServers = getDnsServersInfo();

// Function to check server status
//function checkServerStatus($server) {
    // Ping command varies depending on the operating system
    // This is an example for a Unix-like system
//    $output = [];
//    $status = null;
//    exec("ping -c 1 -W 5000 " . escapeshellarg($server), $output, $status);
//
//    return $status === 0 ? "Online" : "Offline";
//}
function checkServerStatus($server) {
    $port = 53; // DNS port, change if necessary
    $timeout = 5; // Timeout in seconds

    $fp = @fsockopen($server, $port, $errno, $errstr, $timeout);

    if ($fp) {
        fclose($fp);
        return "Online";
    } else {
        return "Offline";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>|--===TildeNIC ===--| Bringing .tilde to the Tildeverse!</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <nav>
            <?php if (!isset($_SESSION['username'])): ?>
                <a href="/?page=login">Login</a> |
                <a href="/?page=register">Register</a>
            <?php else: ?>
                <a href="/?page=main">Home</a> |
                <a href="/?page=user_domains">My Account</a> |
                <a href="/?page=domain_register">Register Domain</a> |
                <a href="/?page=main&action=logout">Logout</a><br><br>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
      </nav>
    </header>

    <div class="content">
        <h1>Welcome to TildeNIC</h1>
        <div class="info-section">
            <p>TildeNIC is where you can request your .tilde top level domain. To do so, you need to first change your DNS over to one of the resolvers we offer, or you can self-host one.</p>
            <ul>
                <li><a href="https://tildegit.org/tildenic/.tilde/wiki/Setting-up-a-.tilde-DNS-server" target="_blank">Self-host information</a></li>
            </ul>
            <p><strong>NOTE!</strong> None of the servers currently listed are functional. They are old IP addresses. New servers will be online very soon!</p>
                <h3>
        <a href="https://opennic.org/" target="_blank">OpenNIC Information</a>
      </h3>      
          <p>
        Domains offered by OpenNIC are also able to be resolved using our servers, Such as:
		<ul>
          <li>.geek</li>
          <li>.bbs</li>
          <li>.gopher and more.</li>
    	</ul>
       	Will all resolve using our dns servers.  For more information about OpenNIC you can visit <a href="https://opennic.org/" target="_blank">http://opennic.org</a>
      </p>
        </div>
        
        <div class="server-list">
            <h2>TildeNIC Available DNS Servers</h2>
            <ul>
                <?php foreach ($dnsServers as $server): ?>
                    <li>
                        <?php echo htmlspecialchars($server['hostname']); ?> - 
                        IPv4: <?php echo htmlspecialchars($server['ipv4']); ?>, 
                        IPv6: <?php echo htmlspecialchars($server['ipv6']); ?>,
                        Location: <?php echo htmlspecialchars($server['location']); ?> - 
                        <span class="status <?php echo checkServerStatus($server['hostname']) === 'Online' ? 'online' : 'offline'; ?>">
                            <?php echo checkServerStatus($server['hostname']); ?>
                        </span>
                    </li>
    <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>