<?php
/**
 * Form Validation Helper Functions
 */

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Uganda phone format: +256, 0256, 256, or 07-09 starting digits
    $pattern = '/^(\+?256|0)[0-9]{9}$/';
    return preg_match($pattern, preg_replace('/[\s\-\(\)]/', '', $phone)) === 1;
}

function validateNationalId($id) {
    return strlen(trim($id)) >= 8 && strlen(trim($id)) <= 20;
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

function validateFileUpload($file, $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File size exceeds maximum limit'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true, 'error' => ''];
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateNullableEmail($email) {
    return empty($email) || validateEmail($email);
}

function getValidationErrors($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $fieldRules) {
        foreach ($fieldRules as $rule => $value) {
            switch ($rule) {
                case 'required':
                    if (empty($data[$field] ?? null)) {
                        $errors[$field] = ucfirst($field) . ' is required';
                    }
                    break;
                case 'email':
                    if (!empty($data[$field]) && !validateEmail($data[$field])) {
                        $errors[$field] = 'Invalid email address';
                    }
                    break;
                case 'phone':
                    if (!empty($data[$field]) && !validatePhone($data[$field])) {
                        $errors[$field] = 'Invalid phone number';
                    }
                    break;
                case 'min':
                    if (strlen($data[$field] ?? '') < $value) {
                        $errors[$field] = ucfirst($field) . ' must be at least ' . $value . ' characters';
                    }
                    break;
                case 'max':
                    if (strlen($data[$field] ?? '') > $value) {
                        $errors[$field] = ucfirst($field) . ' must not exceed ' . $value . ' characters';
                    }
                    break;
            }
        }
    }
    
    return $errors;
}
?>
