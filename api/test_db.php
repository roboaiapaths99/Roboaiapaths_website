<?php
// RoboAIAPaths Database Diagnostics Utility
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== DIAGNOSTICS START ===\n";

echo "PHP Version: " . PHP_VERSION . "\n";

// Check files
$files = ['config.php', 'db_connect.php', 'secrets.php', 'roboaiapaths_knowledge.txt'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "File [{$file}]: EXISTS (size: " . filesize($path) . " bytes)\n";
    } else {
        echo "File [{$file}]: MISSING!\n";
    }
}

// Try db_connect.php safely
echo "\n--- Database Connection Test ---\n";
try {
    if (file_exists(__DIR__ . '/db_connect.php')) {
        // We will temporarily override error reporting to catch mysqli exception
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        include __DIR__ . '/db_connect.php';
        
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "DB Connection Error: " . $conn->connect_error . "\n";
            } else {
                echo "DB Connection: SUCCESS!\n";
                echo "Server Info: " . $conn->server_info . "\n";
                
                // Check tables
                $tables = ['orders', 'chatbot_leads', 'chatbot_logs'];
                foreach ($tables as $table) {
                    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
                    if ($res && $res->num_rows > 0) {
                        echo "Table [{$table}]: EXISTS\n";
                    } else {
                        echo "Table [{$table}]: MISSING!\n";
                    }
                }
            }
        } else {
            echo "DB Connection Error: \$conn variable is not defined or is not a mysqli object.\n";
        }
    } else {
        echo "DB connection test skipped (db_connect.php missing).\n";
    }
} catch (Throwable $e) {
    echo "Exception occurred during DB connection: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "=== DIAGNOSTICS END ===\n";
?>
