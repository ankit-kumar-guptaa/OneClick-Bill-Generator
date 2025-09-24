<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$search = $_GET['search'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';
$partner = $_GET['partner'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(b.invoice_number LIKE ? OR p.partner_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($month) {
    $where_conditions[] = "MONTH(b.invoice_date) = ?";
    $params[] = $month;
}

if ($year) {
    $where_conditions[] = "YEAR(b.invoice_date) = ?";
    $params[] = $year;
}

if ($partner) {
    $where_conditions[] = "b.partner_id = ?";
    $params[] = $partner;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "
    SELECT 
        b.id,
        b.invoice_number,
        DATE_FORMAT(b.invoice_date, '%d-%m-%Y') as invoice_date,
        DATE_FORMAT(b.invoice_date, '%Y-%m-%d') as invoice_date_input,
        b.commission_amount,
        b.total_gst,
        b.total_amount,
        b.description,
        b.cgst_amount,
        b.sgst_amount,
        b.igst_amount,
        p.partner_name,
        p.id as partner_id
    FROM bills b 
    LEFT JOIN partners p ON b.partner_id = p.id 
    $where_clause
    ORDER BY b.created_at DESC
    LIMIT 100
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bills = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'bills' => $bills,
        'count' => count($bills)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
