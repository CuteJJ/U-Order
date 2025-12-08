<?php
// Ensure functions.php is loaded so we can use get_mail()
require_once __DIR__ . '/functions.php';

if (!function_exists('sendReceipt')) {
    function sendReceipt($db, $userId, $paymentId, $totalAmount, $items) {
        // 1. Fetch User Email
        $stmt = $db->prepare("SELECT Email, Name FROM users WHERE UserId = :uid");
        $stmt->execute([':uid' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['Email'])) {
            return false; // No email found
        }
        
        // 2. Construct Link
        // Detect current domain/host automatically
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST']; // e.g. localhost
        // Assuming standard path structure /U-Order/pages/... based on your project
        $link = "$protocol://$host/U-Order/pages/view_receipt.php?payment_id=" . $paymentId;
        
        // 3. Build Simple Notification Body (Like Reset Password)
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f4f7f6; padding: 20px; }
                .container { max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; }
                .header { margin-bottom: 20px; }
                .header h2 { color: #6772e5; margin: 0; }
                .btn { 
                    display: inline-block; 
                    background-color: #6772e5; 
                    color: #ffffff; 
                    text-decoration: none; 
                    padding: 12px 24px; 
                    border-radius: 5px; 
                    font-weight: bold; 
                    margin-top: 20px; 
                    margin-bottom: 20px;
                }

                .btn { padding: 12px 25px; background: #6772e5; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.2s; }
                .btn:hover { background-color: #5469d4; }
                .footer { font-size: 12px; color: #999; margin-top: 20px; }
                .link-text { font-size: 12px; color: #666; word-break: break-all; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Payment Successful</h2>
                </div>
                
                <p>Hi <strong>' . htmlspecialchars($user['Name']) . '</strong>,</p>
                <p>Thank you for your payment of <strong>RM ' . number_format($totalAmount, 2) . '</strong>.</p>
                <p>Your order <strong>#' . $paymentId . '</strong> has been confirmed.</p>
                
                <a href="' . $link . '" class="btn">View Receipt</a>
                
                <p class="footer">
                    If the button above does not work, please copy and paste the following link into your browser:<br>
                    <a href="' . $link . '" class="link-text">' . $link . '</a>
                </p>
            </div>
        </body>
        </html>';
        
        // 4. Send Mail
        try {
            $mail = get_mail();
            $mail->addAddress($user['Email'], $user['Name']);
            $mail->isHTML(true);
            $mail->Subject = "Payment Receipt - Order #" . $paymentId;
            $mail->Body    = $body;
            // Simple text fallback
            $mail->AltBody = "Payment Successful. View your receipt here: " . $link;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
?>