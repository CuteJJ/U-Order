<?php
// Added session start check here too, in case functions.php is included standalone
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env file
function loadEnv($path) {
    if (!is_file($path)) return;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        // Skip comments
        if ($line === '' || str_starts_with($line, '#')) continue;

        // Parse KEY=VALUE
        [$key, $value] = array_map('trim', explode('=', $line, 2));

        // Remove surrounding quotes if any
        $value = trim($value, "'\"");

        // Set environment variables
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Load .env from root
loadEnv(__DIR__ . '/../.env');

// Helper to get env variable
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

/**
 * Flash Message Function
 * Outputs a toaster-style notification with progress bar and close button.
 * $type Type of message: "error", "notice", "success", "warning"
 * $message The message content
 * 
 * If called with both parameters, sets the flash message.
 * For example: flash('success', 'Stall Created Successfully!');
 */
function flash($type = null, $message = null) {
    // Set the message
    if ($type !== null && $message !== null) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    } 
    // Display the message
    elseif (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $class = '';
        
        // Map types to CSS classes
        switch ($flash['type']) {
            case 'error': $class = 'flash-error'; break;
            case 'notice': $class = 'flash-notice'; break;
            case 'success': $class = 'flash-success'; break;
            case 'warning': $class = 'flash-warning'; break;
            default: $class = 'flash-notice';
        }
        
        // Generate unique ID for JS targeting
        $id = 'flash-' . uniqid();
        
        echo '
        <div id="' . $id . '" class="flash-toast ' . $class . '">
            <div class="flash-content">
                ' . $flash['message'] . '
                <span class="flash-close" onclick="closeFlash(\'' . $id . '\')">&times;</span>
            </div>
            <div class="flash-progress">
                <div class="flash-progress-bar"></div>
            </div>
        </div>
        
        <script>
            // Auto-dismiss logic
            (function(){
                const flashId = "' . $id . '";
                const duration = 5000; // 5 seconds golden time
                
                // Set timeout to remove
                setTimeout(function() {
                    closeFlash(flashId);
                }, duration);
            })();

            // Global close function (if not already defined)
            if (typeof closeFlash !== "function") {
                function closeFlash(elementId) {
                    const el = document.getElementById(elementId);
                    if (el) {
                        // Add slide-out animation
                        el.style.animation = "slideOut 0.5s ease-in forwards";
                        // Remove from DOM after animation finishes
                        el.addEventListener("animationend", function() {
                            el.remove();
                        });
                    }
                }
            }
        </script>
        ';
        
        unset($_SESSION['flash']);
    }
}

/**
 * Helper to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Helper to check "Remember Me" cookie if session is expired
 */
function checkRememberMe($db) {
    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        // Split token
        $parts = explode(':', $_COOKIE['remember_token']);
        if (count($parts) === 2) {
            list($userId, $hash) = $parts;
            $secret = "YOUR_SECRET_KEY"; // Should match login.php secret
            
            $checkHash = hash_hmac('sha256', $userId, $secret);
            
            if ($hash === $checkHash) {
                // Restore Session
                $sql = "SELECT * FROM users WHERE UserId = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':id' => $userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $_SESSION['user_id'] = $user['UserId'];
                    $_SESSION['role'] = $user['Role'];
                    $_SESSION['name'] = $user['Name'];
                    return true;
                }
            }
        }
    }
    return false;
}

function get_mail() {   
    // Load PHPMailer classes
    require_once __DIR__ . '/../lib/PHPMailer.php';
    require_once __DIR__ . '/../lib/SMTP.php';

    // Create PHPMailer instance
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->SMTPAuth   = true;
    $m->Host       = env('SMTP_HOST');
    $m->Port       = env('SMTP_PORT');
    $m->Username   = env('SMTP_USER');
    $m->Password   = env('SMTP_PASS');
    $m->CharSet    = 'utf-8';
    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // Set Sender
    $m->setFrom($m->Username, env('SMTP_NAME'));

    return $m;
}
?>