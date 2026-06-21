<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer', 'cashier']);

$memberId = $_GET['member_id'] ?? null;
$db = Database::getInstance()->getConnection();

if ($memberId) {
    $stmt = $db->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();

    if (!$member) {
        header('Location: ../members/list.php');
        exit();
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/Services/SavingsService.php';
    $savingsService = new \SACCO\Services\SavingsService();

    $membershipNo = $_POST['membership_no'] ?? '';
    $accountId = $_POST['account_id'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $referenceNo = $_POST['reference_no'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validate
    if (empty($membershipNo) || empty($accountId) || $amount <= 0 || empty($paymentMethod)) {
        $error = 'Please fill all required fields';
    } else {
        try {
            // Get account and member
            $stmt = $db->prepare("SELECT * FROM savings_accounts WHERE account_id = ?");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch();

            if (!$account || $account['status'] !== 'active') {
                throw new Exception('Invalid account');
            }

            $receipt = generateReceiptNumber('DEP');
            $result = $savingsService->deposit((int)$account['member_id'], (int)$accountId, (float)$amount, $paymentMethod, (int)($_SESSION['user_id'] ?? 0), $receipt);

            if (!empty($result['success'])) {
                $success = 'Deposit recorded successfully. Receipt: ' . $receipt;
            } else {
                $error = 'Error: ' . ($result['message'] ?? 'Deposit failed');
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
    ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Deposit - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Include Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/savings/accounts.php">Savings</a></li>
                    <li class="breadcrumb-item active">Make Deposit</li>
                </ol>
            </nav>

            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-arrow-down-circle me-2"></i>
                                Record Savings Deposit
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php elseif ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>

                            <form method="POST" id="depositForm" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="membership_no" class="form-label">
                                            <i class="bi bi-person-badge me-1"></i>
                                            Membership Number <span class="text-danger">*</span>
                                        </label>
                                        <div class="position-relative">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-search"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="membership_no" 
                                                       name="membership_no" 
                                                       placeholder="Start typing member name or number..."
                                                       value="<?php echo htmlspecialchars($_POST['membership_no'] ?? ''); ?>"
                                                       autocomplete="off"
                                                       required>
                                                <button type="button" class="btn btn-outline-secondary" id="membershipLookupBtn" title="Lookup exact membership number">
                                                    <i class="bi bi-search-heart"></i>
                                                </button>
                                            </div>
                                            <!-- Live search results will appear here -->
                                        </div>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Search by name, membership number, or phone
                                        </div>
                                        <div class="invalid-feedback">Please select a member.</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="account_id" class="form-label">
                                            <i class="bi bi-bank me-1"></i>
                                            Savings Account <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="account_id" name="account_id" required>
                                            <option value="">Search for member first</option>
                                        </select>
                                        <div id="account_feedback" class="form-text"></div>
                                        <div class="invalid-feedback">Please select a savings account.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="holder_name" class="form-label">
                                            <i class="bi bi-person-lines-fill me-1"></i>
                                            Holder's Name
                                        </label>
                                        <input type="text" id="holder_name" name="holder_name" class="form-control" readonly value="<?php echo htmlspecialchars($_POST['holder_name'] ?? ''); ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="account_number" class="form-label">
                                            <i class="bi bi-credit-card-2-front me-1"></i>
                                            Account Number
                                        </label>
                                        <input type="text" id="account_number" name="account_number" class="form-control" readonly value="<?php echo htmlspecialchars($_POST['account_number'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">
                                            <i class="bi bi-currency-exchange me-1"></i>
                                            Deposit Amount (UGX) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="amount" 
                                               name="amount" 
                                               step="100" 
                                               min="500"
                                               placeholder="0.00"
                                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                                               required>
                                        <div class="form-text">Minimum deposit: UGX 500</div>
                                        <div class="invalid-feedback">Please enter a valid amount (minimum 500).</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="payment_method" class="form-label">
                                            <i class="bi bi-credit-card me-1"></i>
                                            Payment Method <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">Select method</option>
                                            <option value="cash" <?php echo ($_POST['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                            <option value="mobile_money" <?php echo ($_POST['payment_method'] ?? '') === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                            <option value="bank_transfer" <?php echo ($_POST['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                            <option value="cheque" <?php echo ($_POST['payment_method'] ?? '') === 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a payment method.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="reference_no" class="form-label">
                                            <i class="bi bi-hash me-1"></i>
                                            Reference Number
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="reference_no" 
                                               name="reference_no" 
                                               placeholder="e.g., Transaction ID, Cheque No"
                                               value="<?php echo htmlspecialchars($_POST['reference_no'] ?? ''); ?>">
                                        <div class="form-text">Optional: For mobile money, bank transfers, or cheques</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="description" class="form-label">
                                            <i class="bi bi-chat-dots me-1"></i>
                                            Description
                                        </label>
                                        <textarea class="form-control" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="2"
                                                  placeholder="Optional description or notes"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo APP_URL; ?>/savings/accounts.php" class="btn btn-outline-secondary me-md-2">
                                        <i class="bi bi-arrow-left me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle me-1"></i> Record Deposit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Member Info Card (Hidden initially) -->
                    <div id="memberInfoCard" class="card mt-3" style="display: none;">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="bi bi-person-check me-2"></i>
                                Member Information
                            </h6>
                        </div>
                        <div class="card-body" id="memberInfo">
                            <!-- Member details will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Amount validation
        document.getElementById('amount').addEventListener('input', function() {
            const amount = parseFloat(this.value);
            if (amount < 500) {
                this.setCustomValidity('Minimum deposit amount is UGX 500');
            } else {
                this.setCustomValidity('');
            }
        });

        // Listen for member selection
        document.getElementById('membership_no').addEventListener('memberSelected', function(e) {
            console.log('Member selected:', e.detail);
        });

        function lookupMembershipExact() {
            const membershipNoInput = document.getElementById('membership_no');
            const membershipNo = membershipNoInput.value.trim();
            const feedback = document.getElementById('account_feedback');
            const accountSelect = document.getElementById('account_id');
            const holderName = document.getElementById('holder_name');
            const accountNumber = document.getElementById('account_number');

            if (!membershipNo) {
                if (feedback) {
                    feedback.textContent = 'Enter a membership number first.';
                    feedback.className = 'form-text text-danger';
                }
                return;
            }

            if (feedback) {
                feedback.textContent = 'Looking up member...';
                feedback.className = 'form-text text-info';
            }

            fetch(`../api/ajax_handler.php?action=check_membership&membership_no=${encodeURIComponent(membershipNo)}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.member) {
                        if (feedback) {
                            feedback.textContent = 'Member not found. Please verify the membership number.';
                            feedback.className = 'form-text text-danger';
                        }
                        hideMemberInfo();
                        return;
                    }

                    const member = data.member;
                    membershipNoInput.value = member.membership_no;

                    if (holderName) {
                        holderName.value = member.full_name || '';
                    }
                    if (accountNumber) {
                        accountNumber.value = member.matched_account ? member.matched_account.account_number : '';
                    }

                    showMemberInfo(member);

                    if (window.memberSearch) {
                        window.__preselectedAccountNumber = member.matched_account ? member.matched_account.account_number : null;
                        window.memberSearch.loadMemberAccounts(member.member_id);
                    } else if (feedback) {
                        feedback.textContent = 'Member found, but accounts cannot be loaded automatically.';
                        feedback.className = 'form-text text-warning';
                    }
                })
                .catch(() => {
                    if (feedback) {
                        feedback.textContent = 'Unable to verify member right now. Please try again.';
                        feedback.className = 'form-text text-danger';
                    }
                    hideMemberInfo();
                });
        }

        document.getElementById('membershipLookupBtn').addEventListener('click', lookupMembershipExact);
        document.getElementById('membership_no').addEventListener('blur', debounce(lookupMembershipExact, 300));

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function(match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[match];
            });
        }

        function showMemberInfo(member) {
            const memberInfoCard = document.getElementById('memberInfoCard');
            const memberInfo = document.getElementById('memberInfo');
            if (!memberInfo || !memberInfoCard) return;

            memberInfo.innerHTML = `
                <div class="row">
                    <div class="col-12 mb-2"><strong>Name:</strong> ${escapeHtml(member.full_name || 'N/A')}</div>
                    <div class="col-12 mb-2"><strong>Membership No:</strong> ${escapeHtml(member.membership_no || 'N/A')}</div>
                    ${member.phone ? `<div class="col-12 mb-2"><strong>Phone:</strong> ${escapeHtml(member.phone)}</div>` : ''}
                    ${member.email ? `<div class="col-12 mb-2"><strong>Email:</strong> ${escapeHtml(member.email)}</div>` : ''}
                    ${member.matched_account ? `<div class="col-12 mb-2"><strong>Account:</strong> ${escapeHtml(member.matched_account.account_number)}</div>` : ''}
                </div>
            `;
            memberInfoCard.style.display = 'block';
        }

        function hideMemberInfo() {
            const memberInfoCard = document.getElementById('memberInfoCard');
            if (memberInfoCard) {
                memberInfoCard.style.display = 'none';
            }
        }
    </script>
</body>
</html>
