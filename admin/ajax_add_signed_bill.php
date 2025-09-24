<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = (int)$_POST['bill_id'];
    $invoice_number = sanitizeInput($_POST['invoice_number']);
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if entry already exists
    $stmt = $db->prepare("SELECT id FROM signed_bills WHERE bill_id = ?");
    $stmt->execute([$bill_id]);
    
    if (!$stmt->fetch()) {
        // Add new entry
        $stmt = $db->prepare("
            INSERT INTO signed_bills (bill_id, invoice_number, original_pdf_path, upload_status) 
            VALUES (?, ?, '', 'pending')
        ");
        $stmt->execute([$bill_id, $invoice_number]);
    }
    
    echo json_encode(['success' => true]);
}
?>
