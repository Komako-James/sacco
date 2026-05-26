<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();

// Default values
$principal = $_GET['amount'] ?? 100000;
$rate = $_GET['rate'] ?? 20; // 20% annual
$months = $_GET['months'] ?? 12;

// Calculation results
$monthly_rate = $rate / 100 / 12;
$monthly_payment = 0;
$total_interest = 0;
$total_payment = 0;
$schedule = [];

if ($principal && $rate && $months) {
    // Calculate monthly payment using loan formula
    $monthly_payment = $principal * ($monthly_rate * pow(1 + $monthly_rate, $months)) / (pow(1 + $monthly_rate, $months) - 1);
    $total_payment = $monthly_payment * $months;
    $total_interest = $total_payment - $principal;

    // Generate amortization schedule
    $remaining_balance = $principal;
    for ($i = 1; $i <= $months; $i++) {
        $interest_payment = $remaining_balance * $monthly_rate;
        $principal_payment = $monthly_payment - $interest_payment;
        $remaining_balance -= $principal_payment;

        $schedule[] = [
            'month' => $i,
            'payment' => $monthly_payment,
            'principal' => $principal_payment,
            'interest' => $interest_payment,
            'balance' => max(0, $remaining_balance)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Calculator - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Loan Calculator</h1>
                </div>

                <div class="row">
                    <!-- Calculator Form -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Loan Parameters</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" id="loanCalculatorForm">
                                    <div class="mb-3">
                                        <label class="form-label">Loan Amount (UGX)</label>
                                        <input type="number" name="amount" class="form-control" value="<?php echo $principal; ?>" min="50000" max="50000000" step="10000" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Annual Interest Rate (%)</label>
                                        <input type="number" name="rate" class="form-control" value="<?php echo $rate; ?>" min="5" max="50" step="0.5" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Loan Term (Months)</label>
                                        <select name="months" class="form-select" required>
                                            <option value="6" <?php echo $months == 6 ? 'selected' : ''; ?>>6 months</option>
                                            <option value="12" <?php echo $months == 12 ? 'selected' : ''; ?>>12 months</option>
                                            <option value="18" <?php echo $months == 18 ? 'selected' : ''; ?>>18 months</option>
                                            <option value="24" <?php echo $months == 24 ? 'selected' : ''; ?>>24 months</option>
                                            <option value="36" <?php echo $months == 36 ? 'selected' : ''; ?>>36 months</option>
                                            <option value="48" <?php echo $months == 48 ? 'selected' : ''; ?>>48 months</option>
                                            <option value="60" <?php echo $months == 60 ? 'selected' : ''; ?>>60 months</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-calculator"></i> Calculate
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="col-lg-8">
                        <?php if ($monthly_payment > 0): ?>
                        <!-- Summary Results -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-white bg-primary">
                                    <div class="card-body text-center">
                                        <h6>Monthly Payment</h6>
                                        <h4><?php echo formatMoney($monthly_payment); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body text-center">
                                        <h6>Total Interest</h6>
                                        <h4><?php echo formatMoney($total_interest); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body text-center">
                                        <h6>Total Payment</h6>
                                        <h4><?php echo formatMoney($total_payment); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body text-center">
                                        <h6>Interest Rate</h6>
                                        <h4><?php echo $rate; ?>%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amortization Schedule -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Amortization Schedule</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Payment</th>
                                                <th>Principal</th>
                                                <th>Interest</th>
                                                <th>Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($schedule as $payment): ?>
                                            <tr>
                                                <td><?php echo $payment['month']; ?></td>
                                                <td><?php echo formatMoney($payment['payment']); ?></td>
                                                <td><?php echo formatMoney($payment['principal']); ?></td>
                                                <td><?php echo formatMoney($payment['interest']); ?></td>
                                                <td><?php echo formatMoney($payment['balance']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-calculator display-3 text-muted"></i>
                                <p class="text-muted mt-3">Enter loan parameters to see calculations</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
    // Auto-calculate on input change
    document.getElementById('loanCalculatorForm').addEventListener('input', function(e) {
        // Add small delay to avoid too many requests
        clearTimeout(window.calcTimeout);
        window.calcTimeout = setTimeout(() => {
            this.submit();
        }, 1000);
    });
    </script>
</body>
</html>
