<?php
require_once 'includes/initdb.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Open a log file for writing
$logFile = fopen("webchangelog.log", "a") or die("Unable to open file!");

// Function to write to log
function writeToLog($message, $logFile) {
    fwrite($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n");
}

// Function to fetch all domain names
function getAllDomainNames($pdo) {
    $stmt = $pdo->query("SELECT domain_name FROM domains");
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Function to fetch all domains
function getAllDomains($pdo) {
    $stmt = $pdo->query("SELECT domain_name, ip_address FROM domains");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to generate BIND DNS file content
function generateDnsFileContent($domain, $ipAddress) {
    $content = "; BIND data file for $domain\n";
    $content .= "\$TTL    604800\n";
    $content .= "@       IN      SOA     ns1.master.tilde. root.$domain. (\n";
    $content .= "                 " . date('Ymd') . "01 ; Serial\n"; // Date-based serial
    $content .= "                 604800     ; Refresh\n";
    $content .= "                 86400      ; Retry\n";
    $content .= "                 2419200    ; Expire\n";
    $content .= "                 604800 )   ; Negative Cache TTL\n";
    $content .= ";\n";
    $content .= "@       IN      NS      ns1.master.tilde.\n";
    $content .= "@       IN      NS      ns2.master.tilde.\n";
    $content .= "www     IN      CNAME   $domain\n";
    $content .= "@       IN      A       $ipAddress\n";
    $content .= "*       IN      A       $ipAddress\n";  // Wildcard A record
    // Add more DNS records as needed
    return $content;
}

// Change to the Git repository directory
chdir('/home/retrodig/dottilde');

// Perform a git pull to ensure the repository is up to date
 exec('git pull');

// Fetch domain names from the database
$databaseDomains = getAllDomainNames($pdo);

// Fetch domains and generate/update DNS files
$domains = getAllDomains($pdo);
$currentFiles = glob('db.*'); // Get all current db files
$changes = false;

foreach ($domains as $domain) {
    $filename = "db." . $domain['domain_name'];
    $content = generateDnsFileContent($domain['domain_name'], $domain['ip_address']);

    if (!file_exists($filename) || $content !== file_get_contents($filename)) {
        file_put_contents($filename, $content);
        $changes = true;
        writeToLog("Updated or created DNS file: $filename", $logFile);
    }

    // Remove filename from the list of current files if it's in the database
    if (($key = array_search($filename, $currentFiles)) !== false) {
        unset($currentFiles[$key]);
    }
}


// Function to update named.conf.local file
function updateNamedConfLocal($domains, $namedConfPath, $logFile) {
    $confContent = "// Dynamic BIND configuration\n\n";

    foreach ($domains as $domain) {
        $zoneEntry = "zone \"" . $domain['domain_name'] . "\" {\n";
        $zoneEntry .= "\ttype master;\n";
        $zoneEntry .= "\tfile \"/etc/bind/db." . $domain['domain_name'] . "\";\n";
        $zoneEntry .= "};\n\n";
        $confContent .= $zoneEntry;
    }

    // Write the new configuration to the file
    file_put_contents($namedConfPath, $confContent);
    writeToLog("Updated named.conf.local", $logFile);
}

// Define the path to named.conf.local
$namedConfPath = '/home/retrodig/dottilde/named.conf.local';

// Update named.conf.local with current domains
updateNamedConfLocal($domains, $namedConfPath, $logFile);

// List of DNS files that should never be deleted
$protectedFiles = ['db.master.tilde', 'db.tilde.tilde', 'db.nic.tilde']; // Add your protected filenames here

// Delete any remaining files that are no longer in the database
foreach ($currentFiles as $file) {
    $domainName = substr($file, 3); // Extract domain name from filename
    if (!in_array($domainName, $databaseDomains) && !in_array($file, $protectedFiles)) {
        unlink($file);
        $changes = true;
        writeToLog("Deleted orphaned DNS file: $file", $logFile);
    }
}

// Close the log file
fclose($logFile);

// Git commit and push if there are changes
if ($changes) {
    exec('git add .');
    exec('git commit -m "Updated DNS files"');
    exec('git push origin master');
}

