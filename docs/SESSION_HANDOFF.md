# Session handoff (2026-11-12)

Read this and `docs/PROJECT_STATUS.md` before starting. The user writes in Arabic and wants Arabic replies.

## State

- Branch `feature/users-roles-settings-branches`. Last commit `a90b804` (workers, clients, status engine).
- **Everything below is NOT committed** (~140 changed/new files). Do not commit or push unless the user asks ("ارفع" = push). Never commit `.env`.
- Migrations are applied on the local SQLite DB, and `RentalDemoSeeder` was run (demo contracts, bookings, payments, shifts, prices).
- Tests: last full run (after the operations modules) 253 passing. The full suite takes 5-10 minutes: run it in the background.

## Built in this session (uncommitted)

### Recruitment (module 4)
- Orders `RQ-YYYY-0001`: new → candidate selected → contracted (visa no. per order) → in travel (country stages) → arrived (housed in a camp of the branch) → delivered (office pickup or home delivery 120 SAR, camp fees after free days, receiver, handover PDF) → probation → completed / returned (or absconded). Cancel before contract.
- Settings > Recruitment: home delivery fee, free camp days, daily camp fee, probation days.
- Pages: Recruitment follow-up, Ready candidates (cards, CV, direct order), Recruitment documents (required-document completeness).
- Worker CV per profession (`config/cv_templates.php`): staff and agency portal fill it; the CV button opens a preview page with Print and Download PDF.
- Arabic wording is gender-neutral (workers can be men).

### Rental (modules 5 and 7)
- Workers for rent (`admin/rental/workers`): tabs monthly / hourly / not in rental, one-click add/remove, contract and booking buttons.
- Monthly contracts `MR-YYYY-0001`: one month, renew = new contract; departure/return with date and time per worker (`rental_contract_workers`); replace the worker on the same contract; early return charges used days (return before 17:00 does not count that day, `config/rental.php`) and requests a refund; cancel = nothing due, refund of what was paid; contract PDF.
- Hourly bookings `HB-YYYY-0001`: only workers marked hourly from a country with an hourly price (today the Philippines); price = hourly price × shift hours + VAT; no overlapping bookings; several days at once; departure and return times, delay shown (not charged); cancel requests a refund of what was paid.
- Payments with transfer receipt (private disk), verify by accounts; refunds approved/rejected by finance (`refunds.approve`, Finance > Refunds).
- Rental calendar (FullCalendar) and Rental reports (operations, billed / collected / refunds, outstanding balances, worker utilisation; amounts need `reports.view_financial`).

## Open questions for the user
1. Hourly booking returned late: charge extra hours, or only show the delay (current)?
2. SJJ-001: driver, or a separate "Domestic Worker" profession?
3. Nanny CV sample (uses the housemaid template for now).
4. Should prices depend on the profession (care giver costs more)?
5. Contact details in the CV header from the site settings?
6. Should agencies add new candidates from their portal?
7. Roles: the rental team cannot record payments by default (accounts does); change?

## Not built yet
Invoices / Wafeq, financial reports beyond rental, recruitment payments, e-mail / WhatsApp alerts, public website / client portal, Excel data migration. (Operations — housing, cases, transport, in-system alerts — were built on 2026-11-12, see PROJECT_STATUS.md.)

## Useful commands
```
export PATH="/c/laragon/bin/php/php-8.3.36-Win32-vs16-x64:$PATH"
php artisan test --compact tests/Feature/Services/Rental      # narrow tests
php artisan test --compact                                     # full suite (~10 min, run in background)
php vendor/bin/pint --dirty --format agent
php artisan db:seed --class=RentalDemoSeeder                   # local demo data (skips if rental data exists)
php artisan db:seed --class=RecruitmentDemoSeeder              # recruitment demo: one order per step per branch (skips if already seeded)
```
