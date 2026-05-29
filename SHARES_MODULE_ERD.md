# Shares Management Module ERD and Architecture

## Entity Relationship Overview

- `members` (existing) is the master entity for all member data.
- `savings_accounts` links to `members` and funds share purchases.
- `member_share_holdings` stores each member's share ownership and equity position.
- `member_share_transactions` records every shares event: purchases, transfers, adjustments, reversals.
- `member_share_transfers` tracks ownership transfers between members and stores audit metadata.
- `ledger_entries` stores double-entry accounting records for every share purchase, transfer, adjustment, or reversal.
- `audit_logs` tracks user actions and changes for all share operations.

## ERD Relationships

- `members.member_id` -> `member_share_holdings.member_id`
- `members.member_id` -> `savings_accounts.member_id`
- `member_share_holdings.share_id` -> `member_share_transactions.share_id`
- `savings_accounts.account_id` -> `member_share_transactions.account_id`
- `members.member_id` -> `member_share_transfers.source_member_id`
- `members.member_id` -> `member_share_transfers.destination_member_id`
- `member_share_holdings.share_id` -> `member_share_transfers.source_share_id`
- `member_share_holdings.share_id` -> `member_share_transfers.destination_share_id`
- `users.user_id` -> `ledger_entries.posted_by`
- `members.member_id` -> `ledger_entries.member_id`
- `members.member_id` -> `ledger_entries.related_member_id`

## Key Tables

### member_share_holdings
- `share_id` (PK)
- `member_id`
- `shares_owned`
- `share_price`
- `total_invested`
- `last_purchase_date`
- `created_at`, `updated_at`

### member_share_transactions
- `transaction_id` (PK)
- `member_id`
- `share_id`
- `account_id`
- `transaction_type` (`purchase`, `transfer_in`, `transfer_out`, `adjustment`, `reversal`)
- `shares`
- `amount`
- `reference_number`
- `related_member_id`
- `transfer_id`
- `status`
- `created_at`, `updated_at`

### member_share_transfers
- `transfer_id` (PK)
- `source_member_id`
- `destination_member_id`
- `source_share_id`
- `destination_share_id`
- `shares_transferred`
- `amount`
- `reference_number`
- `status`
- `posted_by`, `approved_by`, `reversed_by`
- `transfer_date`

### ledger_entries
- `entry_id` (PK)
- `ledger_code`
- `ledger_name`
- `entry_date`
- `receipt_number`
- `transaction_reference`
- `transaction_type`
- `debit`
- `credit`
- `description`
- `payment_method`
- `posted_by`
- `member_id`
- `related_member_id`
- `account_type`
- `status`
- `reversal_of_id`

## Shares Reporting Structure

- Member Shares Statement: Transactions by member, month-to-date and running balance.
- Total SACCO Shares: Aggregate `SUM(shares_owned)` across holdings.
- Shares Transfers: `member_share_transfers` with source/destination and amount.
- Shares Purchases: Filter `member_share_transactions` by `transaction_type = 'purchase'`.
- Top Shareholders: order `member_share_holdings` by shares and equity.
- Shares Movement History: month-by-month rollup of share activity.
- Monthly Shares Summary: aggregated purchase and transfer volumes by month.

## Accounting Treatment

- Share purchases funded from savings:
  - Debit: `Member Savings` (liability reduction)
  - Credit: `Member Shares` (equity increase)

- Share transfers between members:
  - Debit: `Member Shares` for the source member
  - Credit: `Member Shares` for the destination member
  - Total SACCO share capital remains unchanged.

- Share reversals create offsetting ledger entries and reverse ownership records.

## Security and Audit

- Validate member existence and active status before purchase or transfer.
- Prevent share transfers that exceed owned shares.
- Require positive transfer amounts and distinct source/destination members.
- Preserve audit logs for every share transaction and ledger posting.
- Restrict administrative share adjustments and reversal actions to authenticated users.
