<?php
// Fix missing columns in registrations table
require_once 'config.php';

echo "<h1>Fix Missing Columns</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    echo "<h2>Checking and Fixing Registrations Table Structure</h2>";
    
    // First, let's see the current structure
    echo "<h3>Current Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE registrations");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse;margin:10px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $existing_columns = [];
    foreach ($columns as $column) {
        $existing_columns[] = $column['Field'];
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Required Columns:</h3>";
    $required_columns = [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'staff_name' => 'VARCHAR(255) NOT NULL',
        'number_of_pax' => 'INT NOT NULL',
        'selected_seats' => 'TEXT',
        'shift_preference' => "ENUM('normal', 'crew_c') DEFAULT 'normal'",
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    
    echo "<ul>";
    foreach ($required_columns as $col => $type) {
        if (in_array($col, $existing_columns)) {
            echo "<li class='success'>✅ $col - EXISTS</li>";
        } else {
            echo "<li class='error'>❌ $col - MISSING</li>";
        }
    }
    echo "</ul>";
    
    echo "<h3>Adding Missing Columns:</h3>";
    
    // Add missing columns
    $columns_to_add = [
        'selected_seats' => 'TEXT',
        'shift_preference' => "ENUM('normal', 'crew_c') DEFAULT 'normal'"
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            try {
                $sql = "ALTER TABLE registrations ADD COLUMN $column $definition";
                $pdo->exec($sql);
                echo "<p class='success'>✅ Added column: $column</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Failed to add $column: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='info'>ℹ️ Column $column already exists</p>";
        }
    }
    
    // Verify the fix
    echo "<h3>Verification - Updated Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE registrations");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse;margin:10px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test the registration query
    echo "<h3>Testing Registration Query:</h3>";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO registrations (staff_name, number_of_pax, selected_seats, shift_preference) 
            VALUES (?, ?, ?, ?)
        ");
        echo "<p class='success'>✅ Registration query preparation: SUCCESS</p>";
        echo "<p class='success'>🎉 Your registration form should work now!</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Registration query still failing: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='https://company-movie.great-site.net/'>Test your registration form</a></li>";
    echo "<li>Try registering with a test name</li>";
    echo "<li>Check the admin panel to see if the registration appears</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
