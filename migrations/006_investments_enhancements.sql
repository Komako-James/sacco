-- Investment module enhancements for Uganda SACCO workflows
ALTER TABLE investments
  ADD COLUMN IF NOT EXISTS interest_payment_frequency VARCHAR(30) NOT NULL DEFAULT 'At Maturity' AFTER interest_rate,
  ADD COLUMN IF NOT EXISTS auto_recognize_interest TINYINT(1) NOT NULL DEFAULT 0 AFTER interest_payment_frequency,
  ADD COLUMN IF NOT EXISTS expected_interest DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER expected_return;

CREATE INDEX IF NOT EXISTS idx_investments_maturity ON investments(maturity_date, status);
