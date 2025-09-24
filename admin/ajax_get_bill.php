<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$bill_id = (int)($_GET['id'] ?? 0);

if (!$bill_id) {
    echo json_encode(['success' => false, 'error' => 'Bill ID required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("
        SELECT 
            id,
            partner_id,
            invoice_number,
            DATE_FORMAT(invoice_date, '%Y-%m-%d') as invoice_date,
            description,
            commission_amount,
            cgst_amount,
            sgst_amount,
            igst_amount,
            total_gst,
            total_amount
        FROM bills 
        WHERE id = ?
    ");
    $stmt->execute([$bill_id]);
    $bill = $stmt->fetch();
    
    if ($bill) {
        echo json_encode([
            'success' => true,
            'bill' => $bill
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Bill not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
