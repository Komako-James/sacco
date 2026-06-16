<?php
/**
 * ExecutiveDashboardService
 * Provides high-level management metrics and financial KPIs.
 */

namespace SACCO\Services;

use PDO;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/LedgerService.php';

class ExecutiveDashboardService
{
    private $db;

    public function __construct(PDO $database = null)
    {
        $this->db = $database ?? \Database::getInstance()->getConnection();
    }

    public function getExecutiveDashboard()
    {
        $dashboard = [
            'financial_position' => [
                'total_members' => 0,
                'total_savings' => 0,
                'total_shares' => 0,
                'total_loans_outstanding' => 0
            ],
            'current_month' => [
                'revenue_mtd' => 0,
                'expenses_mtd' => 0,
                'profit_mtd' => 0
            ],
            'portfolio' => [
                'active_loans' => 0,
                'interest_receivable' => 0,
                'loan_portfolio_value' => 0
            ],
            'growth' => [
                'revenue_growth_pct' => 0,
                'expense_growth_pct' => 0,
                'member_growth_pct' => 0
            ]
        ];

        $dashboard['financial_position'] = $this->getFinancialPosition();
        $dashboard['current_month'] = $this->getCurrentMonthMetrics();
        $dashboard['portfolio'] = $this->getPortfolioMetrics();
        $dashboard['growth'] = $this->getGrowthMetrics();

        return $dashboard;
    }

    private function getFinancialPosition()
    {
        $members = (int) $this->db->query('SELECT COUNT(*) AS total_members FROM members')->fetchColumn();

        $totalSavings = 0;
        if ($this->tableExists('savings_accounts')) {
            $stmt = $this->db->query('SELECT COALESCE(SUM(balance), 0) AS total_savings FROM savings_accounts WHERE status = "active"');
            $totalSavings = (float) $stmt->fetchColumn();
        }

        $totalShares = 0;
        if ($this->tableExists('member_share_holdings')) {
            $stmt = $this->db->query('SELECT COALESCE(SUM(shares_owned), 0) AS total_shares FROM member_share_holdings');
            $totalShares = (float) $stmt->fetchColumn();
        }

        $totalLoans = 0;
        if ($this->tableExists('loans')) {
            $stmt = $this->db->query('SELECT COALESCE(SUM(outstanding_balance), 0) AS total_outstanding FROM loans');
            $totalLoans = (float) $stmt->fetchColumn();
        }

        return [
            'total_members' => $members,
            'total_savings' => $totalSavings,
            'total_shares' => $totalShares,
            'total_loans_outstanding' => $totalLoans
        ];
    }

    private function getCurrentMonthMetrics()
    {
        $start = date('Y-m-01');
        $end = date('Y-m-d');

        $revenue = LedgerService::getProfitAndLoss($start, $end)['total_revenue'];
        $expenses = LedgerService::getProfitAndLoss($start, $end)['total_expenses'];
        $profit = $revenue - $expenses;

        return [
            'revenue_mtd' => $revenue,
            'expenses_mtd' => $expenses,
            'profit_mtd' => $profit
        ];
    }

    private function getPortfolioMetrics()
    {
        $loanSummary = LedgerService::getLoanPortfolioSummary();

        $interestReceivable = 0;
        if ($this->tableExists('ledger_entries')) {
            $stmt = $this->db->prepare(
                'SELECT COALESCE(SUM(debit - credit), 0) AS interest_receivable
                 FROM ledger_entries
                 WHERE ledger_code = ?
                   AND status = "posted"'
            );
            $stmt->execute(['1040']);
            $interestReceivable = (float) $stmt->fetchColumn();
        }

        $loanPortfolioValue = $loanSummary['outstanding_principal'];

        return [
            'active_loans' => $loanSummary['active_loans'],
            'interest_receivable' => $interestReceivable,
            'loan_portfolio_value' => $loanPortfolioValue
        ];
    }

    private function getGrowthMetrics()
    {
        $today = date('Y-m-d');
        $currentMonthStart = date('Y-m-01');
        $previousMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $previousMonthEnd = date('Y-m-t', strtotime('first day of last month'));

        $currentRevenue = LedgerService::getProfitAndLoss($currentMonthStart, $today)['total_revenue'];
        $previousRevenue = LedgerService::getProfitAndLoss($previousMonthStart, $previousMonthEnd)['total_revenue'];

        $currentExpenses = LedgerService::getProfitAndLoss($currentMonthStart, $today)['total_expenses'];
        $previousExpenses = LedgerService::getProfitAndLoss($previousMonthStart, $previousMonthEnd)['total_expenses'];

        $currentMembers = (int) $this->db->query('SELECT COUNT(*) AS total_members FROM members')->fetchColumn();
        $previousMembers = 0;
        if ($this->tableExists('members')) {
            $stmt = $this->db->prepare('SELECT COUNT(*) AS total_members FROM members WHERE DATE(created_at) <= ?');
            $stmt->execute([$previousMonthEnd]);
            $previousMembers = (int) $stmt->fetchColumn();
        }

        return [
            'revenue_growth_pct' => $this->calculateGrowth($previousRevenue, $currentRevenue),
            'expense_growth_pct' => $this->calculateGrowth($previousExpenses, $currentExpenses),
            'member_growth_pct' => $this->calculateGrowth($previousMembers, $currentMembers)
        ];
    }

    private function calculateGrowth($previous, $current)
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / max($previous, 1)) * 100, 2);
    }

    private function tableExists($table)
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
            );
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (\Exception $e) {
            return false;
        }
    }
}

?>