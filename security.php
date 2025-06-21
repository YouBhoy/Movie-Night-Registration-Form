<?php
// Security configuration and functions
class SecurityManager {
    
    // Rate limiting storage (file-based for InfinityFree)
    private static $rateLimitFile = 'data/.rate_limits';
    private static $logFile = 'data/.security_log';
    
    // Initialize security
    public static function init() {
        // Ensure data directory exists
        if (!file_exists('data')) {
            mkdir('data', 0755, true);
        }
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Start secure session
        self::startSecureSession();
    }
    
    // Secure session configuration
    private static function startSecureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    // Rate limiting
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $rateLimits = self::loadRateLimits();
        $now = time();
        $key = md5($identifier);
        
        // Clean old entries
        foreach ($rateLimits as $k => $data) {
            if ($now - $data['time'] > $timeWindow) {
                unset($rateLimits[$k]);
            }
        }
        
        // Check current attempts
        if (isset($rateLimits[$key])) {
            if ($rateLimits[$key]['attempts'] >= $maxAttempts) {
                self::logSecurityEvent('RATE_LIMIT_EXCEEDED', $identifier);
                return false;
            }
            $rateLimits[$key]['attempts']++;
        } else {
            $rateLimits[$key] = ['attempts' => 1, 'time' => $now];
        }
        
        self::saveRateLimits($rateLimits);
        return true;
    }
    
    // Input sanitization
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        $input = trim($input);
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Validate input
    public static function validateInput($input, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $input[$field] ?? null;
            
            // Required check
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Field {$field} is required";
                continue;
            }
            
            if (empty($value)) continue;
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format";
                        }
                        break;
                    case 'int':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field] = "Must be a valid integer";
                        }
                        break;
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = "Must be a valid string";
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Minimum length is {$rule['min_length']} characters";
            }
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Maximum length is {$rule['max_length']} characters";
            }
            
            // Range validation
            if (isset($rule['min']) && $value < $rule['min']) {
                $errors[$field] = "Minimum value is {$rule['min']}";
            }
            if (isset($rule['max']) && $value > $rule['max']) {
                $errors[$field] = "Maximum value is {$rule['max']}";
            }
            
            // Pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = $rule['pattern_message'] ?? "Invalid format";
            }
        }
        
        return $errors;
    }
    
    // Generate CSRF token
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF token
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Log security events
    public static function logSecurityEvent($event, $details = '') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    // Check if IP is suspicious
    public static function checkSuspiciousActivity($ip) {
        // Simple IP validation
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        
        // Check for common attack patterns in user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $suspiciousPatterns = [
            '/bot/i', '/crawler/i', '/spider/i', '/scraper/i',
            '/curl/i', '/wget/i', '/python/i', '/perl/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                self::logSecurityEvent('SUSPICIOUS_USER_AGENT', $userAgent);
                return true;
            }
        }
        
        return false;
    }
    
    // Load rate limits from file
    private static function loadRateLimits() {
        if (file_exists(self::$rateLimitFile)) {
            $data = file_get_contents(self::$rateLimitFile);
            return json_decode($data, true) ?: [];
        }
        return [];
    }
    
    // Save rate limits to file
    private static function saveRateLimits($rateLimits) {
        file_put_contents(self::$rateLimitFile, json_encode($rateLimits), LOCK_EX);
    }
}

// Initialize security on every request
SecurityManager::init();
?>
