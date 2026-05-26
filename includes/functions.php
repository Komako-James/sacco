<?php
require_once __DIR__ . '/../config/db_connection.php';

function generateMembershipNumber() {
    $db = getDB();
    $stmt = $db->query("SELECT membership_no FROM members ORDER BY membership_no ASC");
    $existing = array_map('strval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'membership_no'));

    for ($i = 1; $i <= 700; $i++) {
        $number = str_pad($i, 3, '0', STR_PAD_LEFT);
        if (!in_array($number, $existing, true)) {
            return $number;
        }
    }

    throw new Exception('No available membership numbers. Please review the member number range.');
}

function generateSavingsAccountNumber() {
    $db = getDB();
    $stmt = $db->query('SELECT MAX(account_id) AS max_id FROM savings_accounts');
    $maxId = (int) $stmt->fetchColumn();
    $next = $maxId + 1;
    return 'SV' . str_pad($next, 6, '0', STR_PAD_LEFT);
}

function generateLoanReference() {
    return 'LN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateReceiptNumber($prefix) {
    return $prefix . date('YmdHis') . rand(100, 999);
}

function formatMoney($amount) {
    return 'UGX ' . number_format($amount, 2);
}

function calculateLoanSchedule($amount, $interestRate, $periodMonths) {
    $monthlyInterestRate = ($interestRate / 100) / 12;
    $monthlyPayment = ($amount * $monthlyInterestRate * pow(1 + $monthlyInterestRate, $periodMonths)) /
                     (pow(1 + $monthlyInterestRate, $periodMonths) - 1);

    $schedule = [];
    $balance = $amount;

    for ($i = 1; $i <= $periodMonths; $i++) {
        $interestPayment = $balance * $monthlyInterestRate;
        $principalPayment = $monthlyPayment - $interestPayment;
        $balance -= $principalPayment;

        $schedule[] = [
            'installment' => $i,
            'principal' => round($principalPayment, 2),
            'interest' => round($interestPayment, 2),
            'total' => round($monthlyPayment, 2),
            'balance' => round(max(0, $balance), 2)
        ];
    }

    return $schedule;
}

function checkMemberEligibility($memberId) {
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT COUNT(*) as savings_count FROM savings_transactions st JOIN savings_accounts sa ON st.account_id = sa.account_id WHERE sa.member_id = ? AND st.transaction_type = ? AND st.transaction_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)'
    );
    $stmt->execute([$memberId, 'deposit']);
    $result = $stmt->fetch();

    if ($result['savings_count'] == 0) {
        return ['eligible' => false, 'reason' => 'Member must save for at least 3 months before applying for a loan'];
    }

    $stmt = $db->prepare('SELECT COUNT(*) as active_loans FROM loans WHERE member_id = ? AND status NOT IN (?, ?)');
    $stmt->execute([$memberId, 'completed', 'rejected']);
    $result = $stmt->fetch();

    if ($result['active_loans'] > 0) {
        return ['eligible' => false, 'reason' => 'Member has an active loan'];
    }

    return ['eligible' => true, 'reason' => ''];
}

function getMemberGuarantorExposure($memberId) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT COALESCE(SUM(amount_guaranteed), 0) as total_exposure FROM loan_guarantors lg JOIN loans l ON lg.loan_id = l.loan_id WHERE lg.guarantor_member_id = ? AND lg.status = ? AND l.status NOT IN (?, ?)' 
    );
    $stmt->execute([$memberId, 'active', 'completed', 'rejected']);
    $result = $stmt->fetch();
    return $result['total_exposure'];
}

function getMemberSavingsBalance($memberId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT COALESCE(SUM(balance), 0) as total_savings FROM savings_accounts WHERE member_id = ? AND status = ?');
    $stmt->execute([$memberId, 'active']);
    $result = $stmt->fetch();
    return $result['total_savings'];
}

function sendSMS($phone, $message) {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO sms_queue (phone_number, message_body, message_type, delivery_status, attempts, max_attempts, created_at) VALUES (?, ?, ?, ?, 0, 3, NOW())');
    $stmt->execute([$phone, $message, 'general', 'pending']);
    return true;
}

function sendEmail($recipient, $subject, $body) {
    $headers = 'From: ' . COMPANY_NAME . ' <' . COMPANY_EMAIL . '>' . "\r\n" .
               'Reply-To: ' . COMPANY_EMAIL . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    return mail($recipient, $subject, wordwrap($body, 70), $headers);
}

function logActivity($userId, $action, $table, $recordId, $oldData = null, $newData = null) {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)' 
    );
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    return $stmt->execute([
        $userId,
        $action,
        $table,
        $recordId,
        $oldData ? json_encode($oldData) : null,
        $newData ? json_encode($newData) : null,
        $ip,
        $userAgent
    ]);
}

function generatePDF($html, $filename) {
    try {
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // For now, let's create a simple HTML file
        // In production, you would use libraries like TCPDF, FPDF, or DomPDF
        $filepath = $uploadDir . $filename . '.html';

        // Add basic HTML structure
        $fullHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($filename) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SACCO Report</h1>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    </div>
    ' . $html . '
</body>
</html>';

        file_put_contents($filepath, $fullHtml);

        return [
            'success' => true,
            'message' => 'Report generated successfully',
            'filepath' => $filepath,
            'download_url' => 'uploads/' . $filename . '.html'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error generating report: ' . $e->getMessage(),
            'filepath' => null
        ];
    }
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhoneNumber($phone) {
    // Uganda phone number validation
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Check for valid Uganda phone number patterns
    if (preg_match('/^(\+256|0)[0-9]{9}$/', $phone)) {
        // Convert to international format
        return preg_replace('/^0/', '+256', $phone);
    }

    return false;
}

function formatPhoneNumber($phone) {
    $clean = preg_replace('/[^0-9+]/', '', $phone);
    if (substr($clean, 0, 4) === '+256') {
        return '+256 ' . substr($clean, 4, 3) . ' ' . substr($clean, 7, 3) . ' ' . substr($clean, 10);
    }
    return $phone;
}

function calculateAge($dateOfBirth) {
    $dob = new DateTime($dateOfBirth);
    $now = new DateTime();
    return $now->diff($dob)->y;
}

function generateAccountNumber($type = 'savings') {
    $prefix = ($type === 'savings') ? 'SAV' : 'LON';
    return $prefix . date('Y') . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return $date;
    }
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function maskPhoneNumber($phone) {
    if (strlen($phone) < 8) return $phone;
    return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 7) . substr($phone, -3);
}

function calculateInterest($principal, $rate, $time, $type = 'simple') {
    $rate = $rate / 100; // Convert percentage to decimal

    if ($type === 'compound') {
        return $principal * pow(1 + $rate, $time) - $principal;
    } else {
        return $principal * $rate * $time;
    }
}

function generateBarcode($data, $type = 'CODE128') {
    // Simple barcode generation placeholder
    // In production, you would use a barcode generation library
    return base64_encode($data . '|' . $type);
}

function validateNationalId($nationalId) {
    // Uganda National ID validation (simplified)
    $nationalId = preg_replace('/[^A-Z0-9]/', '', strtoupper($nationalId));
    return preg_match('/^[A-Z]{2}[0-9]{8}[A-Z]{3}$/', $nationalId);
}

function getFinancialYear($date = null) {
    $date = $date ?: date('Y-m-d');
    $year = date('Y', strtotime($date));
    $month = date('n', strtotime($date));

    if ($month >= 7) { // July to December
        return $year . '-' . ($year + 1);
    } else { // January to June
        return ($year - 1) . '-' . $year;
    }
}

function auditLog($action, $table, $recordId, $oldData = null, $newData = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        return logActivity($userId, $action, $table, $recordId, $oldData, $newData);
    }
    return false;
}

function checkDuplicateEntry($table, $field, $value, $excludeId = null) {
    $db = getDB();

    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$field} = ?";
    $params = [$value];

    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();

    return $result['count'] > 0;
}

function generateQRCode($data) {
    // QR Code generation placeholder
    // In production, you would use a QR code generation library
    return 'data:image/png;base64,' . base64_encode('QR:' . $data);
}

function encryptData($data, $key = null) {
    $key = $key ?: (defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default-key');
    $cipher = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptData($encryptedData, $key = null) {
    $key = $key ?: (defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default-key');
    $cipher = 'AES-256-CBC';
    $data = base64_decode($encryptedData);
    $ivSize = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $ivSize);
    $encrypted = substr($data, $ivSize);
    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
}

function formatCurrency($amount, $currency = 'UGX') {
    return $currency . ' ' . number_format($amount, 2);
}

function validateStrongPassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special char
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

function getClientIpAddress() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2629746) return floor($time / 86400) . ' days ago';
    if ($time < 31556926) return floor($time / 2629746) . ' months ago';

    return floor($time / 31556926) . ' years ago';
}

?>
