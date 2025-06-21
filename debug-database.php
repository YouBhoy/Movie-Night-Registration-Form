<?php
// Debug script to check database connection and tables
require_once 'config.php';

echo "<h1>Database Debug Information</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    $testQuery = $pdo->query("SELECT 1");
    echo "<p class='success'>✅ Database connection: SUCCESS</p>";
    echo "<p class='info'>Connected to: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection: FAILED</p>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check if tables exist
echo "<h2>2. Table Structure Check</h2>";
$requiredTables = ['event_settings', 'registrations', 'seats'];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "<p class='success'>✅ Table '$table': EXISTS</p>";
        
        // Show table structure
        echo "<details><summary>Show $table structure</summary>";
        echo "<table border='1' style='margin:10px;border-collapse:collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table></details>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Table '$table': MISSING</p>";
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
}

// Test 3: Check table data
echo "<h2>3. Table Data Check</h2>";

// Check event_settings
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM event_settings");
    $count = $stmt->fetch()['count'];
    echo "<p class='success'>✅ event_settings has $count records</p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM event_settings LIMIT 5");
        echo "<details><summary>Show sample settings</summary>";
        echo "<table border='1' style='margin:10px;border-collapse:collapse;'>";
        echo "<tr><th>Key</th><th>Value</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['setting_key']}</td><td>{$row['setting_value']}</td></tr>";
        }
        echo "</table></details>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ event_settings error: " . $e->getMessage() . "</p>";
}

// Check seats
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM seats");
    $count = $stmt->fetch()['count'];
    echo "<p class='success'>✅ seats table has $count records</p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT shift_type, COUNT(*) as count FROM seats GROUP BY shift_type");
        echo "<details><summary>Show seat distribution</summary>";
        echo "<table border='1' style='margin:10px;border-collapse:collapse;'>";
        echo "<tr><th>Shift Type</th><th>Count</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['shift_type']}</td><td>{$row['count']}</td></tr>";
        }
        echo "</table></details>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ seats error: " . $e->getMessage() . "</p>";
}

// Check registrations
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations");
    $count = $stmt->fetch()['count'];
    echo "<p class='success'>✅ registrations table has $count records</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ registrations error: " . $e->getMessage() . "</p>";
}

// Test 4: Test a sample registration query
echo "<h2>4. Registration Query Test</h2>";
try {
    // Test the exact query used in registration
    $stmt = $pdo->prepare("
        INSERT INTO registrations (staff_name, number_of_pax, selected_seats, shift_preference) 
        VALUES (?, ?, ?, ?)
    ");
    
    // Don't actually execute, just prepare
    echo "<p class='success'>✅ Registration query preparation: SUCCESS</p>";
    
    // Test seat availability query
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as occupied_count 
        FROM seats 
        WHERE CONCAT(row_letter, seat_number) IN (?) 
        AND shift_type = ? 
        AND is_occupied = 1
    ");
    echo "<p class='success'>✅ Seat availability query preparation: SUCCESS</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Registration query test: FAILED</p>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Test 5: Check MySQL version and settings
echo "<h2>5. MySQL Environment</h2>";
try {
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch()['version'];
    echo "<p class='info'>MySQL Version: $version</p>";
    
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'sql_mode'");
    $sqlMode = $stmt->fetch()['Value'];
    echo "<p class='info'>SQL Mode: $sqlMode</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ MySQL info error: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Recommendations</h2>";
echo "<p>If you see any red ❌ errors above, those need to be fixed first.</p>";
echo "<p>If everything shows green ✅, the issue might be in the registration form logic.</p>";
echo "<p><strong>Next step:</strong> Try a test registration and check the detailed error logs.</p>";
?>
