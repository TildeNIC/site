<?php
require_once 'initdb.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Open a log file for writing
$logFile = fopen("wtf.log", "a") or die("Unable to open file!");

// Function to write to log
function writeToLog($message, $logFile) {
    fwrite($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n");
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
chdir('../dottilde');

// Perform a git pull to ensure the repository is up to date
exec('git pull 2>&1', $gitOutput, $gitStatus);
writeToLog("Git Pull: " . implode("\n", $gitOutput), $logFile);

$domains = getAllDomains($pdo);
$changes = false;
$currentFiles = glob('db.*');

writeToLog("Current files before processing: " . implode(', ', $currentFiles), $logFile);

foreach ($domains as $domain) {
    $filename = "db." . $domain['domain_name'];
    $content = generateDnsFileContent($domain['domain_name'], $domain['ip_address']);

    // Check if file content is different from existing file
    if (!file_exists($filename) || $content !== file_get_contents($filename)) {
        file_put_contents($filename, $content);
        $changes = true;
    }

    // Remove filename from the list of current files
    if (($key = array_search($filename, $currentFiles)) !== false) {
        unset($currentFiles[$key]);
    }
}

writeToLog("Files to be deleted: " . implode(', ', $currentFiles), $logFile);

// Delete any remaining files that are no longer in the database
foreach ($currentFiles as $file) {
    writeToLog("Deleting file: $file", $logFile);
  unlink($file);
    $changes = true;
}

if ($changes) {
    exec('git add . 2>&1', $gitAddOutput, $gitAddStatus);
    writeToLog("Git Add: " . implode("\n", $gitAddOutput), $logFile);

    exec('git commit -m "Updated DNS files" 2>&1', $gitCommitOutput, $gitCommitStatus);
    writeToLog("Git Commit: " . implode("\n", $gitCommitOutput), $logFile);

    exec('git push origin master 2>&1', $gitPushOutput, $gitPushStatus);
    writeToLog("Git Push: " . implode("\n", $gitPushOutput), $logFile);
}

fclose($logFile);
?>