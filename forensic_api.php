<?php
// forensic_api.php
// Read-only endpoint to run the combined JSON SQL and return results.

require_once __DIR__ . '/config/db_connection.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();

    $sqlFile = __DIR__ . '/forensic_reconciliation_json.sql';
    if (!file_exists($sqlFile)) {
        http_response_code(500);
        echo json_encode(['error' => 'SQL file not found', 'file' => $sqlFile]);
        exit;
    }

    $content = file_get_contents($sqlFile);
    if ($content === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not read SQL file']);
        exit;
    }

    // Extract the SELECT JSON_OBJECT(...) AS result; statement
    $pos = stripos($content, 'SELECT JSON_OBJECT(');
    if ($pos === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Expected SELECT JSON_OBJECT not found in SQL file']);
        exit;
    }

    $selectSql = substr($content, $pos);
    // Remove trailing delimiters if present
    $selectSql = preg_replace('/;\s*$/', '', trim($selectSql));

    // Execute any SET statements before the select (e.g., group_concat_max_len)
    // We'll execute all statements before the SELECT found earlier.
    $prefix = substr($content, 0, $pos);
    $statements = array_filter(array_map('trim', preg_split('/;\s*/', $prefix)));
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        $db->exec($stmt);
    }

    $stmt = $db->query($selectSql);
    if ($stmt === false) {
        http_response_code(500);
        $err = $db->errorInfo();
        echo json_encode(['error' => 'Query failed', 'info' => $err]);
        exit;
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        echo json_encode(['result' => null]);
        exit;
    }

    // The SELECT returns a single column named 'result' which contains JSON text.
    $jsonText = array_values($row)[0];
    // If $jsonText is already JSON, output it raw.
    if ($jsonText === null) {
        echo json_encode(['result' => null]);
        exit;
    }

    // Validate JSON
    json_decode($jsonText);
    if (json_last_error() === JSON_ERROR_NONE) {
        // echo raw JSON
        echo $jsonText;
    } else {
        // Fallback: return as string inside JSON
        echo json_encode(['result_string' => $jsonText]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
