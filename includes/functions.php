<?php
// Added session start check here too, in case functions.php is included standalone
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        
        // --- CONFIGURATION ---
        $durationSeconds = 8; // Change this to 8 seconds
        $durationMs = $durationSeconds * 1000;
        
        // Generate unique ID for JS targeting
        $id = 'flash-' . uniqid();
        
        echo '
        <div id="' . $id . '" class="flash-toast ' . $class . '">
            <div class="flash-content">
                ' . $flash['message'] . '
                <span class="flash-close" onclick="closeFlash(\'' . $id . '\')">&times;</span>
            </div>
            <div class="flash-progress">
                <div class="flash-progress-bar" style="animation-duration: ' . $durationSeconds . 's;"></div>
            </div>
        </div>
        
        <script>
            // Auto-dismiss logic
            (function(){
                const flashId = "' . $id . '";
                const duration = ' . $durationMs . '; // Synced with PHP variable
                
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
 * Fix asset URL to ensure correct path
 */
function fixAssetUrl($path)
{
    if (!$path) return "https://thumbs.dreamstime.com/b/food-stall-line-icon-food-stall-line-icon-outline-vector-sign-linear-style-pictogram-isolated-white-symbol-logo-illustration-100240476.jpg";
    if (strpos($path, 'http') === 0) return $path;
    $clean = ltrim($path, '/');
    return "../assets/" . $clean;
}

/**
 * Fix image URL to ensure correct path
 */
function fixImageUrl($path)
{
    if (!$path) return "https://thumbs.dreamstime.com/b/food-stall-line-icon-food-stall-line-icon-outline-vector-sign-linear-style-pictogram-isolated-white-symbol-logo-illustration-100240476.jpg"; // Placeholder
    if (strpos($path, 'http') === 0) return $path;
    $clean = ltrim($path, '/');
    return "../assets/" . $clean;
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

/**
 * Synchronizes the Main Order Status based on its individual Items.
 * 1. If ANY item is 'pending'   -> Order is 'pending'
 * 2. If ANY item is 'preparing' -> Order is 'preparing' (unless one is pending)
 * 3. If ALL items are 'ready'   -> Order is 'ready'
 */
function syncOrderStatus($db, $orderId) {
    // 1. Get all statuses for this order's items
    $stmt = $db->prepare("SELECT Status FROM orderitems WHERE OrderId = ?");
    $stmt->execute([$orderId]);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($statuses)) return;

    // 2. Logic: The "Slowest Runner" Rule
    $newStatus = 'ready'; // Start optimistic
    
    if (in_array('pending', $statuses)) {
        $newStatus = 'pending';
    } elseif (in_array('preparing', $statuses)) {
        $newStatus = 'preparing';
    }

    // 3. Update the main Order
    // Only update if not already complete/cancelled to be safe
    $check = $db->prepare("SELECT Status FROM orders WHERE OrderId = ?");
    $check->execute([$orderId]);
    $current = $check->fetchColumn();

    if ($current !== 'complete' && $current !== 'cancelled') {
        $update = $db->prepare("UPDATE orders SET Status = ? WHERE OrderId = ?");
        $update->execute([$newStatus, $orderId]);
    }
}

?>