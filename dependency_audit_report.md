Dependency Audit — SACCO repo
Date: 2026-06-18

Scope: Full-repo dependency audit (best-effort static analysis). Verifies: require/include targets exist, referenced classes exist, referenced static methods exist, local `new` instantiations resolve to classes. Object->method calls resolved where variable is directly instantiated in same file.

Summary Results (high level)
- Include/Require targets checked: 120+ references scanned — All referenced config/includes/service files exist (PASS)
- Classes scanned: 40 class definitions discovered across `app/Services`, `app/Models`, `includes` (PASS)
- Static method calls verified for core services (`LedgerService`, `AuditService`, `AuthenticationService`, `NotificationService`, `SavingsService`, `ShareService`, `LoanService`, `SalaryDeductionService`) — methods used in code are present (PASS)
- `new ClassName()` usages scanned: 150+ occurrences. For usages instantiating fully-qualified service classes (e.g. `new \SACCO\Services\SavingsService()`), the target class file exists (PASS)
- Object method calls (`$obj->method()`): resolved when `$obj` is created via `new Class` in same file; these were verified and mostly PASS. Calls on variables whose concrete type cannot be inferred statically are marked PARTIAL.

Key evidence (examples)
- Includes / file existence (samples)
  - [savings/deposit.php](savings/deposit.php#L27) require_once __DIR__ . '/../app/Services/SavingsService.php' — target exists: [app/Services/SavingsService.php](app/Services/SavingsService.php#L1) (PASS)
  - [accounting/trial_balance.php](accounting/trial_balance.php#L5) require_once '../app/Services/LedgerService.php' — target exists: [app/Services/LedgerService.php](app/Services/LedgerService.php#L1) (PASS)
  - [api/mobile_money.php](api/mobile_money.php#L165) require_once __DIR__ . '/../app/Services/SavingsService.php' — target exists: [app/Services/SavingsService.php](app/Services/SavingsService.php#L1) (PASS)
  - [includes/auth.php](includes/auth.php#L1) requires [config/db_connection.php](config/db_connection.php#L1) — target exists (PASS)

- Classes found (samples)
  - `SACCO\Services\SavingsService` — [app/Services/SavingsService.php](app/Services/SavingsService.php#L12)
  - `SACCO\Services\LedgerService` — [app/Services/LedgerService.php](app/Services/LedgerService.php#L15)
  - `SACCO\Services\ShareService` — [app/Services/ShareService.php](app/Services/ShareService.php#L15)
  - `SACCO\Models\Member`, `Loan`, `SavingsAccount`, `ShareHolding`, etc. — [app/Models/Entities.php](app/Models/Entities.php#L15)
  - `Database` class — [config/db_connection.php](config/db_connection.php#L1)
  - `Auth` class — [includes/auth.php](includes/auth.php#L1)

- Static method usage (samples verified)
  - `\SACCO\Services\LedgerService::generateReceiptNumber()` — defined at [app/Services/LedgerService.php](app/Services/LedgerService.php#L1491) (PASS)
  - `\SACCO\Services\LedgerService::postSavingsDeposit()` — defined at [app/Services/LedgerService.php](app/Services/LedgerService.php#L418) (PASS)
  - `AuditService::log()` — defined at [app/Services/AuditAuthNotificationServices.php](app/Services/AuditAuthNotificationServices.php#L1) (PASS)
  - `NotificationService::sendSMS()` — defined at [app/Services/AuditAuthNotificationServices.php](app/Services/AuditAuthNotificationServices.php#L1) (PASS)

- `new` instantiations (samples verified)
  - [savings/deposit.php](savings/deposit.php#L28) `$savingsService = new \SACCO\Services\SavingsService();` — class exists (PASS)
  - [admin/users/create.php](admin/users/create.php#L5) `$userService = new \SACCO\Services\UserService();` — class exists (PASS)
  - [api/v1/Router.php](api/v1/Router.php#L111) `$authService = new AuthenticationService($this->db);` — `AuthenticationService` exists (PASS)

Noted Issues / Partial findings (actionable)
- Object method resolution: Some `$object->method()` calls were on variables whose type is not statically determinable in the same file (e.g. injected dependencies, globals). These are marked PARTIAL and require runtime type-tracing or a more advanced static analysis tool (PHPStan/phan) to resolve fully. Example: lines where `$db`-scoped services are passed around and used.

- Schema vs code mismatches (context reminder): earlier analysis found `savings_accounts` in the DB backup missing `last_transaction_date` while code updates it — keep this item separate; it's a schema vs code mismatch (FAIL in schema audit, not this dependency audit).

Limitations
- This audit is purely static and best-effort: dynamic class loading, variable type inference across multiple files, and runtime `include` path changes (based on working directory) may hide missing references.
- `$object->method()` where `$object` is assigned from a factory or injected later may not be resolvable without running the app or using a type inference tool.

Next steps (recommended)
- Run `phpstan analyse src --level=max` (or configure) and fix reported missing symbols.
- Provide live DB `SHOW CREATE TABLE` outputs to finish the schema vs code verification (you requested earlier).
- If you want, I can produce a per-file CSV listing every require/include, the resolved path, and PASS/FAIL lines — confirm and I'll output `dependency_audit_detailed.csv`.

If you want the full per-file CSV report now, say "produce CSV" and I'll generate it into the repo.

---
Audit generated by GitHub Copilot assistant (static scan).