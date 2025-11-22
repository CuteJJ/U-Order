<?php
// Added session start check here too, in case functions.php is included standalone
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
?>