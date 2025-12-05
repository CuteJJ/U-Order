<?php
// USE require_once TO PREVENT "CANNOT REDECLARE" ERRORS
require_once '../configs/db.php';
require_once '../includes/functions.php';

// 1. Security Checks
if (!isLoggedIn()) {
    flash('error', 'Please login to view receipts.');
    header("Location: login.php");
    exit;
}

$paymentId = $_GET['payment_id'] ?? 0;
$userId = $_SESSION['user_id'];

// 2. Fetch Payment & Order Details
// We join tables to ensure the receipt belongs to the logged-in user
$sql = "SELECT p.PaymentId, p.TotalAmount, p.CreatedAt, p.PaymentMethod, u.Name, u.Email 
        FROM payments p
        JOIN users u ON p.UserId = u.UserId
        WHERE p.PaymentId = :pid AND p.UserId = :uid";

$stmt = $db->prepare($sql);
$stmt->execute([':pid' => $paymentId, ':uid' => $userId]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    flash('error', 'Receipt not found or access denied.');
    header("Location: profile.php");
    exit;
}

// 3. Fetch Items for this Payment
$sqlItems = "SELECT oi.Quantity, oi.Subtotal, p.ProductName, p.UnitPrice 
             FROM orderitems oi
             JOIN orders o ON oi.OrderId = o.OrderId
             JOIN products p ON oi.ProductId = p.ProductId
             WHERE o.PaymentId = :pid";

$stmtItems = $db->prepare($sqlItems);
$stmtItems->execute([':pid' => $paymentId]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $paymentId; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reusing the clean receipt style */
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #525659; margin: 0; padding: 20px; display: flex; justify-content: center; min-height: 100vh; }
        .wrapper { width: 100%; max-width: 600px; background-color: #ffffff; box-shadow: 0 0 20px rgba(0,0,0,0.3); margin: auto; }
        
        /* Header */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; color: #ffffff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 1px; }
        .header p { margin: 10px 0 0; font-size: 16px; opacity: 0.9; }
        
        /* Content */
        .content { padding: 40px 30px; }
        .info-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .info-label { font-size: 11px; color: #8898aa; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 5px; display: block; }
        .info-val { font-size: 15px; color: #333; font-weight: 600; }
        
        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: #8898aa; padding: 10px 0; border-bottom: 2px solid #edf2f7; letter-spacing: 0.5px; }
        .items-table td { padding: 15px 0; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #4a5568; vertical-align: top; }
        .items-table .qty { text-align: center; width: 60px; }
        .items-table .price { text-align: right; width: 100px; font-weight: 600; }
        
        /* Totals */
        .total-block { text-align: right; padding-top: 10px; }
        .total-row { margin-bottom: 5px; }
        .total-label { font-size: 14px; color: #8898aa; margin-right: 15px; }
        .total-amount { font-size: 24px; font-weight: 800; color: #2d3748; }
        .total-amount.sub { font-size: 16px; color: #4a5568; }
        
        /* Footer */
        .footer { background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #edf2f7; font-size: 12px; color: #a0aec0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* Action Bar (Visible on screen only) */
        .action-bar { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #333; padding: 10px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: flex; gap: 10px; z-index: 100; }
        .btn { text-decoration: none; color: white; font-weight: 600; font-size: 14px; padding: 8px 16px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: background 0.2s; }
        .btn-download { background: #667eea; }
        .btn-download:hover { background: #5a6fd6; }
        .btn-close { background: #4a5568; }
        .btn-close:hover { background: #2d3748; }

        /* Print Styles */
        @media print {
            body { background-color: white; padding: 0; display: block; }
            .wrapper { box-shadow: none; max-width: 100%; width: 100%; }
            .action-bar { display: none !important; }
            .header { color: black !important; background: white !important; border-bottom: 2px solid #333; padding-top: 0; }
            .header h1 { color: #333; }
        }
    </style>
</head>
<body>

    <!-- Floating Action Bar -->
    <div class="action-bar">
        <a href="javascript:window.print()" class="btn btn-download">
            <span>⬇️</span> Download / Print PDF
        </a>
        <a href="javascript:window.close()" class="btn btn-close">
            <span>✕</span> Close
        </a>
    </div>

    <div class="wrapper">
        <div class="header">
            <h1>RECEIPT</h1>
            <p>Order #<?php echo $paymentId; ?></p>
        </div>
        
        <div class="content">
            <!-- Info Grid -->
            <table class="info-table">
                <tr>
                    <td valign="top" width="50%">
                        <span class="info-label">BILLED TO</span>
                        <div class="info-val"><?php echo htmlspecialchars($payment['Name']); ?></div>
                        <div style="font-size:13px; color:#718096"><?php echo htmlspecialchars($payment['Email']); ?></div>
                    </td>
                    <td valign="top" width="50%" style="text-align:right;">
                        <span class="info-label">DATE PAID</span>
                        <div class="info-val"><?php echo date('d M Y', strtotime($payment['CreatedAt'])); ?></div>
                        <div style="font-size:13px; color:#718096"><?php echo date('h:i A', strtotime($payment['CreatedAt'])); ?></div>
                        <div style="margin-top:5px;">
                            <span style="background:#edf2f7; color:#4a5568; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold; text-transform:uppercase;">
                                <?php echo htmlspecialchars($payment['PaymentMethod']); ?>
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
            
            <!-- Items List -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="qty">Qty</th>
                        <th class="price">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600; color:#2d3748;"><?php echo htmlspecialchars($item['ProductName']); ?></div>
                        </td>
                        <td class="qty">x<?php echo $item['Quantity']; ?></td>
                        <td class="price">RM <?php echo number_format($item['Subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Total -->
            <div class="total-block">
                <div class="total-row">
                    <span class="total-label">TOTAL PAID</span>
                    <span class="total-amount">RM <?php echo number_format($payment['TotalAmount'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>&copy; <?php echo date('Y'); ?> U-Order Canteen System</p>
        </div>
    </div>

</body>
</html>