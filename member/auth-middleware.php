<?php
/**
 * Member Authentication Middleware
 * Checks if user is a logged-in member
 */

require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../app/Services/MemberAuthenticationService.php';

use SACCO\Services\MemberAuthenticationService;

function requireMemberLogin()
{
    session_start();

    // Check if member session exists
    if (!isset($_SESSION['member_user_id']) || !isset($_SESSION['session_token'])) {
        // Clear any partial session
        session_destroy();
        header('Location: ' . str_replace('/member/', '/', $_SERVER['PHP_SELF']) . 'member-login.php');
        exit();
    }

    // Validate session token
    $db = getDB();
    $authService = new MemberAuthenticationService($db);

    $sessionValidation = $authService->validateSession($_SESSION['session_token']);

    if (!$sessionValidation['valid']) {
        session_destroy();
        header('Location: ../member-login.php?error=' . urlencode($sessionValidation['message']));
        exit();
    }

    // Update session data
    $_SESSION['last_activity'] = time();
    $_SESSION['user_id'] = $sessionValidation['user_id'];
    $_SESSION['member_id'] = $sessionValidation['member_id'];

    return true;
}

function getMemberData()
{
    session_start();

    if (!isset($_SESSION['member_id'])) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.username, u.email, u.phone
        FROM members m
        LEFT JOIN users u ON m.user_id = u.user_id
        WHERE m.member_id = ?
    ");
    $stmt->execute([$_SESSION['member_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getMemberSavings()
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM savings_accounts
        WHERE member_id = ? AND status = 'active'
    ");
    $stmt->execute([$_SESSION['member_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMemberLoans()
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT l.*, l.loan_ref_no AS loan_reference, l.amount_requested AS loan_amount
        FROM loans l
        WHERE l.member_id = ? AND l.status IN ('approved', 'disbursed')
    ");
    $stmt->execute([$_SESSION['member_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getMemberShares()
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM member_share_holdings WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function memberLogout()
{
    session_start();

    if (isset($_SESSION['session_token'])) {
        $db = getDB();
        $authService = new MemberAuthenticationService($db);
        $authService->memberLogout($_SESSION['session_token']);
    }

    session_destroy();
    header('Location: ../member-login.php?logged_out=1');
    exit();
}
?>
