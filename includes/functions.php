<?php
/**
 * OneClick Insurance - Utility Functions
 * Version: 2.0
 * Updated: September 2025
 */

// Input Sanitization
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Generate Unique Invoice Number
function generateInvoiceNumber() {
    $prefix = 'OCI';
    $year = date('Y');
    $month = date('m');
    $random = sprintf('%04d', rand(1000, 9999));
    return $prefix . $year . $month . $random;
}

// Enhanced Number to Words (Indian System)
function numberToWords($number) {
    $number = (int) $number;
    
    if ($number == 0) return 'Zero';
    
    $words = array(
        0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
        11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
        15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty',
        50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
    );

    if ($number < 21) {
        return ucfirst($words[$number]);
    } elseif ($number < 100) {
        $tens = intval($number / 10) * 10;
        $units = $number % 10;
        return ucfirst($words[$tens] . ($units ? ' ' . $words[$units] : ''));
    } elseif ($number < 1000) {
        $hundreds = intval($number / 100);
        $remainder = $number % 100;
        return ucfirst($words[$hundreds] . ' hundred' . ($remainder ? ' ' . numberToWords($remainder) : ''));
    } elseif ($number < 100000) {
        $thousands = intval($number / 1000);
        $remainder = $number % 1000;
        return ucfirst(numberToWords($thousands) . ' thousand' . ($remainder ? ' ' . numberToWords($remainder) : ''));
    } elseif ($number < 10000000) {
        $lakhs = intval($number / 100000);
        $remainder = $number % 100000;
        return ucfirst(numberToWords($lakhs) . ' lakh' . ($remainder ? ' ' . numberToWords($remainder) : ''));
    } elseif ($number < 1000000000) {
        $crores = intval($number / 10000000);
        $remainder = $number % 10000000;
        return ucfirst(numberToWords($crores) . ' crore' . ($remainder ? ' ' . numberToWords($remainder) : ''));
    }
    return 'Number too large';
}

// Indian Currency Formatting
function formatIndianCurrency($amount, $showSymbol = true) {
    $symbol = $showSymbol ? '₹' : '';
    
    // Format for Indian numbering system
    if ($amount >= 10000000) { // 1 crore
        return $symbol . number_format($amount / 10000000, 2) . ' Cr';
    } elseif ($amount >= 100000) { // 1 lakh
        return $symbol . number_format($amount / 100000, 2) . ' L';
    } else {
        return $symbol . number_format($amount, 2);
    }
}

// Standard Currency Format
function formatCurrency($amount, $showSymbol = true) {
    $symbol = $showSymbol ? '₹' : '';
    return $symbol . number_format($amount, 2);
}

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function redirectIfNotLoggedIn($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect_url);
        exit;
    }
}

function getLoggedInUser() {
    if (isLoggedIn()) {
        return array(
            'id' => $_SESSION['admin_id'] ?? null,
            'name' => $_SESSION['admin_name'] ?? 'Admin',
            'username' => $_SESSION['admin_username'] ?? 'admin'
        );
    }
    return null;
}

// Date Formatting Functions
function formatIndianDate($date, $format = 'd-m-Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd-m-Y H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

// GST Calculation Functions
function calculateGST($amount, $rate = 18) {
    return round(($amount * $rate) / 100, 2);
}

function calculateCGSTSGST($amount, $rate = 9) {
    $cgst = round(($amount * $rate) / 100, 2);
    $sgst = round(($amount * $rate) / 100, 2);
    return array('cgst' => $cgst, 'sgst' => $sgst, 'total' => $cgst + $sgst);
}

function calculateIGST($amount, $rate = 18) {
    return round(($amount * $rate) / 100, 2);
}

// File Upload Functions
function uploadFile($file, $uploadDir = '../uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return array('success' => false, 'message' => 'Invalid file parameters');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('success' => false, 'message' => 'Upload failed with error code ' . $file['error']);
    }

    if ($file['size'] > 5000000) { // 5MB limit
        return array('success' => false, 'message' => 'File size exceeds 5MB limit');
    }

    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);

    if (!in_array($extension, $allowedTypes)) {
        return array('success' => false, 'message' => 'File type not allowed');
    }

    $fileName = uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return array('success' => false, 'message' => 'Failed to move uploaded file');
    }

    return array('success' => true, 'filename' => $fileName, 'path' => $targetPath);
}

// Database Helper Functions
function executeQuery($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

function fetchSingle($db, $query, $params = []) {
    $stmt = executeQuery($db, $query, $params);
    return $stmt ? $stmt->fetch() : false;
}

function fetchAll($db, $query, $params = []) {
    $stmt = executeQuery($db, $query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^[6-9]\d{9}$/', $phone);
}

function validateGSTIN($gstin) {
    $gstin = strtoupper(trim($gstin));
    return preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin);
}

function validatePAN($pan) {
    $pan = strtoupper(trim($pan));
    return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan);
}

// Alert/Message Functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = array('type' => $type, 'message' => $message);
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function displayFlashMessage() {
    $message = getFlashMessage();
    if ($message) {
        $alertClass = 'alert-' . ($message['type'] === 'error' ? 'danger' : $message['type']);
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Security Functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Utility Functions
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $length)), 0, $length);
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 1) return 'just now';
    
    $conditions = array(
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($conditions as $secs => $str) {
        $d = $time / $secs;
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
    return false;
}

// PDF Helper Functions
function generatePDFFilename($invoiceNumber) {
    return 'OneClick_Invoice_' . $invoiceNumber . '_' . date('Y-m-d') . '.pdf';
}

function createPDFDirectory($path = '../pdf/generated/') {
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

// Export Functions
function exportToCSV($data, $filename = 'export.csv', $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// State Code Mapping
function getStateName($stateCode) {
    $states = array(
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab',
        '04' => 'Chandigarh', '05' => 'Uttarakhand', '06' => 'Haryana',
        '07' => 'Delhi', '08' => 'Rajasthan', '09' => 'Uttar Pradesh',
        '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram',
        '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam',
        '19' => 'West Bengal', '20' => 'Jharkhand', '21' => 'Odisha',
        '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
        '25' => 'Daman and Diu', '26' => 'Dadra and Nagar Haveli',
        '27' => 'Maharashtra', '28' => 'Andhra Pradesh', '29' => 'Karnataka',
        '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala',
        '33' => 'Tamil Nadu', '34' => 'Puducherry', '35' => 'Andaman and Nicobar Islands',
        '36' => 'Telangana', '37' => 'Andhra Pradesh'
    );
    
    return isset($states[$stateCode]) ? $states[$stateCode] : 'Unknown';
}

// Invoice Status Functions
function getInvoiceStatus($invoiceDate) {
    $days = floor((time() - strtotime($invoiceDate)) / (60 * 60 * 24));
    
    if ($days <= 15) {
        return array('status' => 'current', 'class' => 'success', 'text' => 'Current');
    } elseif ($days <= 30) {
        return array('status' => 'due', 'class' => 'warning', 'text' => 'Due Soon');
    } else {
        return array('status' => 'overdue', 'class' => 'danger', 'text' => 'Overdue');
    }
}

// Log Functions
function logActivity($message, $type = 'info') {
    $logFile = '../logs/activity_' . date('Y-m-d') . '.log';
    $logEntry = date('Y-m-d H:i:s') . ' [' . strtoupper($type) . '] ' . $message . PHP_EOL;
    
    if (!file_exists('../logs/')) {
        mkdir('../logs/', 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Debug Functions (Remove in production)
function debugPrint($data, $exit = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($exit) exit;
}

function getDBConnectionInfo($db) {
    try {
        $version = $db->query('SELECT VERSION()')->fetchColumn();
        return 'MySQL ' . $version;
    } catch (Exception $e) {
        return 'Database connection error';
    }
}

// End of functions.php
?>
