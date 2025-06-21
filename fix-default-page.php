<?php
// Script to fix the default page issue
echo "<h1>Fix Default Page Issue</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

echo "<h2>Current Situation:</h2>";
echo "<p class='info'>✅ Your registration form works at: <a href='https://company-movie.great-site.net/index.html'>https://company-movie.great-site.net/index.html</a></p>";
echo "<p class='error'>❌ Root URL shows InfinityFree default page: <a href='https://company-movie.great-site.net/'>https://company-movie.great-site.net/</a></p>";

echo "<h2>Files to Check/Delete:</h2>";
echo "<p>InfinityFree creates default files that override your index.html. Check for these files in your /htdocs/ folder:</p>";

$default_files = [
    'index2.html' => 'InfinityFree default page',
    'index.php' => 'PHP default page (if exists)',
    'default.html' => 'Default HTML page (if exists)',
    'home.html' => 'Home page (if exists)'
];

echo "<ul>";
foreach ($default_files as $file => $description) {
    if (file_exists($file)) {
        echo "<li class='error'>❌ <strong>$file</strong> - $description - <strong>DELETE THIS</strong></li>";
    } else {
        echo "<li class='success'>✅ $file - Not found (good)</li>";
    }
}
echo "</ul>";

echo "<h2>Your Files Status:</h2>";
$your_files = [
    'index.html' => 'Your registration form',
    'admin-login.php' => 'Admin login page',
    'admin.php' => 'Admin dashboard',
    'config.php' => 'Database configuration'
];

echo "<ul>";
foreach ($your_files as $file => $description) {
    if (file_exists($file)) {
        echo "<li class='success'>✅ <strong>$file</strong> - $description - EXISTS</li>";
    } else {
        echo "<li class='error'>❌ $file - $description - MISSING</li>";
    }
}
echo "</ul>";

echo "<h2>🚀 How to Fix:</h2>";
echo "<ol>";
echo "<li><strong>Go to InfinityFree File Manager</strong></li>";
echo "<li><strong>Navigate to /htdocs/ folder</strong></li>";
echo "<li><strong>Look for and DELETE these files:</strong>";
echo "<ul>";
echo "<li>index2.html (InfinityFree default)</li>";
echo "<li>Any other index.* files except index.html</li>";
echo "</ul></li>";
echo "<li><strong>Keep only YOUR files:</strong>";
echo "<ul>";
echo "<li>index.html (your registration form)</li>";
echo "<li>All your .php files</li>";
echo "<li>Your images/ folder</li>";
echo "<li>Your data/ folder</li>";
echo "</ul></li>";
echo "<li><strong>Test:</strong> Visit <a href='https://company-movie.great-site.net/'>https://company-movie.great-site.net/</a></li>";
echo "</ol>";

echo "<h2>Alternative Solution - .htaccess Redirect:</h2>";
echo "<p>If deleting files doesn't work, add this to your .htaccess file:</p>";
echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ccc;'>";
echo "DirectoryIndex index.html index.php\n";
echo "RewriteEngine On\n";
echo "RewriteRule ^$ index.html [L]\n";
echo "</pre>";

echo "<h2>✅ Expected Result:</h2>";
echo "<p>After the fix:</p>";
echo "<ul>";
echo "<li class='success'>✅ <a href='https://company-movie.great-site.net/'>https://company-movie.great-site.net/</a> → Shows your registration form</li>";
echo "<li class='success'>✅ <a href='https://company-movie.great-site.net/index.html'>https://company-movie.great-site.net/index.html</a> → Also works</li>";
echo "<li class='success'>✅ <a href='https://company-movie.great-site.net/admin-login.php'>https://company-movie.great-site.net/admin-login.php</a> → Admin login</li>";
echo "</ul>";
?>
