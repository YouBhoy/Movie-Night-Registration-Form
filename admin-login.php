<?php
session_start();
require_once 'config.php';

// Rate limiting for login attempts
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!SecurityManager::checkRateLimit("login_$clientIP", 5, 900)) { // 5 attempts per 15 minutes
    http_response_code(429);
    die("Too many login attempts. Please try again later.");
}

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!SecurityManager::verifyCSRFToken($csrfToken)) {
        SecurityManager::logSecurityEvent('CSRF_TOKEN_MISMATCH', $clientIP);
        $error_message = 'Security token mismatch. Please try again.';
    } else {
        // Validate and sanitize input
        $rules = [
            'username' => ['required' => true, 'type' => 'string', 'max_length' => 50],
            'password' => ['required' => true, 'type' => 'string', 'max_length' => 100]
        ];
        
        $validationErrors = SecurityManager::validateInput($_POST, $rules);
        
        if (empty($validationErrors)) {
            $username = SecurityManager::sanitizeInput($_POST['username']);
            $password = $_POST['password']; // Don't sanitize password
            
            // Check credentials with timing attack protection
            $validUsername = 'Western-Digital';
            $validPassword = 'WDAdmin123';
            
            $usernameValid = hash_equals($validUsername, $username);
            $passwordValid = hash_equals($validPassword, $password);
            
            if ($usernameValid && $passwordValid) {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['login_time'] = time();
                $_SESSION['login_ip'] = $clientIP;
                
                SecurityManager::logSecurityEvent('ADMIN_LOGIN_SUCCESS', $username);
                header('Location: admin.php');
                exit;
            } else {
                SecurityManager::logSecurityEvent('ADMIN_LOGIN_FAILED', "Username: $username, IP: $clientIP");
                $error_message = 'Invalid username or password';
                
                // Add delay to prevent brute force
                sleep(2);
            }
        } else {
            $error_message = 'Invalid input provided';
        }
    }
}

$csrfToken = SecurityManager::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WD Movie Night - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cinema-brown': '#8b4513',
                        'cinema-light': '#f4e4bc',
                        'cinema-gold': '#deb887',
                        'cinema-dark': '#654321',
                        'wd-cyan': '#00d4ff',
                        'wd-blue': '#0066cc'
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-800 via-slate-700 to-slate-600 min-h-screen flex items-center justify-center">
    
    <div class="max-w-md w-full mx-4">
        <!-- Login Card -->
        <div class="bg-cinema-light rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-cinema-brown to-cinema-dark p-8 text-center">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <img src="images/wd-logo.png" alt="Western Digital" class="h-12 w-auto">
                    <div class="text-left">
                        <h1 class="text-2xl font-bold text-cinema-light">WD Admin</h1>
                        <p class="text-amber-200 text-sm">Movie Night Dashboard</p>
                    </div>
                </div>
                <div class="bg-cinema-light/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto">
                    <i class="fas fa-shield-alt text-3xl text-cinema-light"></i>
                </div>
            </div>

            <!-- Login Form -->
            <div class="p-8">
                <h2 class="text-2xl font-bold text-cinema-brown text-center mb-6">Admin Access</h2>
                
                <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-300 text-red-700 p-3 rounded-lg mb-4 text-sm">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-cinema-brown mb-2">
                            <i class="fas fa-user mr-2"></i>Username
                        </label>
                        <input type="text" id="username" name="username" required maxlength="50"
                               class="w-full p-4 border-2 border-amber-300 rounded-xl bg-white/80 focus:bg-white focus:border-cinema-brown focus:ring-4 focus:ring-cinema-brown/20 transition-all duration-300"
                               placeholder="Enter admin username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-cinema-brown mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required maxlength="100"
                                   class="w-full p-4 border-2 border-amber-300 rounded-xl bg-white/80 focus:bg-white focus:border-cinema-brown focus:ring-4 focus:ring-cinema-brown/20 transition-all duration-300 pr-12"
                                   placeholder="Enter admin password">
                            <button type="button" onclick="togglePassword()" 
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-amber-600 hover:text-cinema-brown transition-colors">
                                <i id="passwordIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" id="loginBtn"
                            class="w-full bg-gradient-to-r from-cinema-gold to-amber-400 text-cinema-brown py-4 px-6 rounded-xl text-lg font-bold hover:from-amber-400 hover:to-cinema-gold hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-3">
                        <i class="fas fa-sign-in-alt"></i>
                        <span id="loginText">Access Admin Dashboard</span>
                        <i id="loginSpinner" class="fas fa-spinner fa-spin hidden"></i>
                    </button>
                </form>

                <!-- Back to Registration -->
                <div class="text-center mt-6">
                    <a href="index.html" class="inline-flex items-center gap-2 text-amber-700 hover:text-cinema-brown transition-colors text-sm">
                        <i class="fas fa-arrow-left"></i>
                        Back to Registration
                    </a>
                </div>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="text-center mt-6 text-slate-300 text-xs">
            <i class="fas fa-info-circle mr-1"></i>
            Authorized personnel only. All access attempts are logged.
        </div>
    </div>

    <script>
        // Client-side security and validation
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        // Form validation and security
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            
            // Basic validation
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            // Length validation
            if (username.length > 50 || password.length > 100) {
                e.preventDefault();
                alert('Input too long');
                return;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginText.textContent = 'Authenticating...';
            loginSpinner.classList.remove('hidden');
        });

        // Auto-focus username field
        document.getElementById('username').focus();

        // Prevent multiple submissions
        let submitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (submitted) {
                e.preventDefault();
                return false;
            }
            submitted = true;
        });

        // Clear form on page unload for security
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>
