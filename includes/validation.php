<?php
/**
 * Form Validation Helper Functions
 * SACCO Management System - No Duplicate Functions
 */

function validatePhone($phone) {
    // Uganda phone format: +256, 0256, 256, or 07-09 starting digits
    $pattern = '/^(\+?256|0)[0-9]{9}$/';
    return preg_match($pattern, preg_replace('/[\s\-\(\)]/', '', $phone)) === 1;
}

function validateUsername($username) {
    // Username: 3-20 chars, alphanumeric and underscore
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username) === 1;
}

function validatePassword($password) {
    // Min 8 chars, at least 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password) 
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

function validateAmount($amount) {
    return is_numeric($amount) && floatval($amount) > 0;
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateFileUpload($file, $allowedExtensions = array('jpg', 'jpeg', 'png', 'pdf')) {
    if (!defined('MAX_FILE_SIZE')) {
        define('MAX_FILE_SIZE', 5242880); // 5MB
    }

    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return array('valid' => false, 'error' => 'No file uploaded');
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return array('valid' => false, 'error' => 'File size exceeds maximum limit');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        return array('valid' => false, 'error' => 'File type not allowed');
    }

    return array('valid' => true, 'error' => '');
}

function validateSaccoInput($input) {
    if (is_array($input)) {
        return array_map('validateSaccoInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateNullableEmail($email) {
    return empty($email) || validateEmail($email);
}

function validateMembershipNumber($membershipNo) {
    // Validate membership number format: 001-700
    return preg_match('/^[0-9]{3}$/', $membershipNo) && intval($membershipNo) >= 1 && intval($membershipNo) <= 700;
}

function validateLoanAmount($amount, $loanType = 'salary') {
    if (!is_numeric($amount) || floatval($amount) <= 0) {
        return array('valid' => false, 'error' => 'Invalid loan amount');
    }

    $amount = floatval($amount);

    // Define loan limits based on type
    $limits = array(
        'salary' => array('min' => 100000, 'max' => 5000000),
        'business' => array('min' => 50000, 'max' => 1000000)
    );

    if (!isset($limits[$loanType])) {
        return array('valid' => false, 'error' => 'Invalid loan type');
    }

    $min = $limits[$loanType]['min'];
    $max = $limits[$loanType]['max'];

    if ($amount < $min) {
        return array('valid' => false, 'error' => 'Minimum loan amount is UGX ' . number_format($min));
    }

    if ($amount > $max) {
        return array('valid' => false, 'error' => 'Maximum loan amount is UGX ' . number_format($max));
    }

    return array('valid' => true, 'error' => '');
}

function validateRepaymentPeriod($months, $loanType = 'salary') {
    if (!is_numeric($months) || intval($months) <= 0) {
        return array('valid' => false, 'error' => 'Invalid repayment period');
    }

    $months = intval($months);

    // Define repayment period limits based on type
    $limits = array(
        'salary' => array('min' => 6, 'max' => 24),
        'business' => array('min' => 1, 'max' => 6)
    );

    if (!isset($limits[$loanType])) {
        return array('valid' => false, 'error' => 'Invalid loan type');
    }

    $min = $limits[$loanType]['min'];
    $max = $limits[$loanType]['max'];

    if ($months < $min) {
        return array('valid' => false, 'error' => 'Minimum repayment period is ' . $min . ' months');
    }

    if ($months > $max) {
        return array('valid' => false, 'error' => 'Maximum repayment period is ' . $max . ' months');
    }

    return array('valid' => true, 'error' => '');
}

function validateMemberAge($dateOfBirth, $minAge = 18, $maxAge = 75) {
    if (!validateDate($dateOfBirth)) {
        return array('valid' => false, 'error' => 'Invalid date of birth');
    }

    $dob = new DateTime($dateOfBirth);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

    if ($age < $minAge) {
        return array('valid' => false, 'error' => 'Minimum age is ' . $minAge . ' years');
    }

    if ($age > $maxAge) {
        return array('valid' => false, 'error' => 'Maximum age is ' . $maxAge . ' years');
    }

    return array('valid' => true, 'error' => '', 'age' => $age);
}

function validateUgandanNationalId($nationalId) {
    // Uganda National ID format: CM12345678ABC (simplified validation)
    $nationalId = strtoupper(trim($nationalId));

    if (strlen($nationalId) < 8 || strlen($nationalId) > 20) {
        return array('valid' => false, 'error' => 'National ID must be between 8-20 characters');
    }

    // Basic format check (can be enhanced)
    if (!preg_match('/^[A-Z0-9]+$/', $nationalId)) {
        return array('valid' => false, 'error' => 'National ID contains invalid characters');
    }

    return array('valid' => true, 'error' => '');
}

function validateSavingsAmount($amount, $accountType = 'monthly') {
    if (!is_numeric($amount) || floatval($amount) <= 0) {
        return array('valid' => false, 'error' => 'Invalid savings amount');
    }

    $amount = floatval($amount);

    // Define minimum amounts based on account type
    $minimums = array(
        'monthly' => 10000,
        'voluntary' => 1000,
        'fixed' => 50000
    );

    $min = isset($minimums[$accountType]) ? $minimums[$accountType] : 1000;

    if ($amount < $min) {
        return array('valid' => false, 'error' => 'Minimum ' . $accountType . ' savings amount is UGX ' . number_format($min));
    }

    return array('valid' => true, 'error' => '');
}

function validatePhoneUganda($phone) {
    // Enhanced Uganda phone validation
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

    // Accept formats: +256XXXXXXXXX, 0XXXXXXXXX, 256XXXXXXXXX
    $patterns = array(
        '/^\+256[7-9][0-9]{8}$/',  // +256XXXXXXXXX
        '/^0[7-9][0-9]{8}$/',      // 0XXXXXXXXX
        '/^256[7-9][0-9]{8}$/'     // 256XXXXXXXXX
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return array('valid' => true, 'error' => '', 'formatted' => formatPhoneUganda($phone));
        }
    }

    return array('valid' => false, 'error' => 'Invalid Uganda phone number format');
}

function formatPhoneUganda($phone) {
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

    // Convert to +256 format
    if (substr($phone, 0, 1) === '0') {
        $phone = '+256' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) === '256') {
        $phone = '+' . $phone;
    } elseif (substr($phone, 0, 4) !== '+256') {
        $phone = '+256' . $phone;
    }

    return $phone;
}

function getSaccoValidationErrors($data, $rules) {
    $errors = array();

    foreach ($rules as $field => $fieldRules) {
        $value = isset($data[$field]) ? $data[$field] : '';

        foreach ($fieldRules as $rule => $ruleValue) {
            switch ($rule) {
                case 'required':
                    if ($ruleValue && empty($value)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                    }
                    break;

                case 'email':
                    if (!empty($value) && !validateEmail($value)) {
                        $errors[$field] = 'Please enter a valid email address';
                    }
                    break;

                case 'phone':
                    if (!empty($value)) {
                        $phoneValidation = validatePhoneUganda($value);
                        if (!$phoneValidation['valid']) {
                            $errors[$field] = $phoneValidation['error'];
                        }
                    }
                    break;

                case 'min':
                    if (strlen($value) < $ruleValue) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be at least ' . $ruleValue . ' characters';
                    }
                    break;

                case 'max':
                    if (strlen($value) > $ruleValue) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must not exceed ' . $ruleValue . ' characters';
                    }
                    break;

                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number';
                    }
                    break;

                case 'date':
                    if (!empty($value) && !validateDate($value)) {
                        $errors[$field] = 'Please enter a valid date';
                    }
                    break;

                case 'national_id':
                    if (!empty($value)) {
                        $idValidation = validateUgandanNationalId($value);
                        if (!$idValidation['valid']) {
                            $errors[$field] = $idValidation['error'];
                        }
                    }
                    break;
            }

            // Break if error found for this field
            if (isset($errors[$field])) {
                break;
            }
        }
    }

    return $errors;
}

function validateMemberRegistration($data) {
    $rules = array(
        'full_name' => array('required' => true, 'min' => 2, 'max' => 100),
        'national_id' => array('required' => true, 'national_id' => true),
        'phone' => array('required' => true, 'phone' => true),
        'email' => array('email' => true),
        'gender' => array('required' => true),
        'date_of_birth' => array('required' => true, 'date' => true)
    );

    $errors = getSaccoValidationErrors($data, $rules);

    // Additional age validation
    if (empty($errors['date_of_birth']) && !empty($data['date_of_birth'])) {
        $ageValidation = validateMemberAge($data['date_of_birth']);
        if (!$ageValidation['valid']) {
            $errors['date_of_birth'] = $ageValidation['error'];
        }
    }

    return $errors;
}

function validateLoanApplication($data) {
    $rules = array(
        'member_id' => array('required' => true, 'numeric' => true),
        'loan_type' => array('required' => true),
        'amount' => array('required' => true, 'numeric' => true),
        'repayment_period' => array('required' => true, 'numeric' => true),
        'purpose' => array('required' => true, 'min' => 10, 'max' => 500)
    );

    $errors = getSaccoValidationErrors($data, $rules);

    // Additional loan-specific validations
    if (empty($errors['amount']) && !empty($data['amount']) && !empty($data['loan_type'])) {
        $amountValidation = validateLoanAmount($data['amount'], $data['loan_type']);
        if (!$amountValidation['valid']) {
            $errors['amount'] = $amountValidation['error'];
        }
    }

    if (empty($errors['repayment_period']) && !empty($data['repayment_period']) && !empty($data['loan_type'])) {
        $periodValidation = validateRepaymentPeriod($data['repayment_period'], $data['loan_type']);
        if (!$periodValidation['valid']) {
            $errors['repayment_period'] = $periodValidation['error'];
        }
    }

    return $errors;
}

function validateTransaction($data) {
    $rules = array(
        'account_id' => array('required' => true, 'numeric' => true),
        'amount' => array('required' => true, 'numeric' => true),
        'transaction_type' => array('required' => true),
        'description' => array('required' => true, 'min' => 3, 'max' => 255)
    );

    return getSaccoValidationErrors($data, $rules);
}

function validateAndSanitizeForm($data, $rules = array()) {
    $sanitized = array();
    $errors = array();

    foreach ($data as $field => $value) {
        $sanitized[$field] = validateSaccoInput($value);
    }

    if (!empty($rules)) {
        $errors = getSaccoValidationErrors($sanitized, $rules);
    }

    return array(
        'data' => $sanitized,
        'errors' => $errors,
        'valid' => empty($errors)
    );
}

?>
