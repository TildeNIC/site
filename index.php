<?php
// Define the default page
$defaultPage = 'domain_search';

// Get the requested page from the query parameter
$page = isset($_GET['page']) ? $_GET['page'] : $defaultPage;

// Sanitize the page parameter to prevent directory traversal
$page = preg_replace('/[^-a-zA-Z0-9_]/', '', $page);

// Path to the includes directory
$includesPath = './includes/';

// Check if the file exists
if (file_exists($includesPath . $page . '.php')) {
    require_once $includesPath . $page . '.php';
} else {
    // If the file doesn't exist, you can include a default page or show an error
    echo "Page not found.";
    // Or include a 404 page
    // require_once $includesPath . '404.php';
}