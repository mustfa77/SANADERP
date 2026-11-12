# Sanad ERP: Roadmap and Task Specifications

This file is the single source of truth for WHAT to build and in WHICH order.
- `CLAUDE.md` = HOW to build (conventions and rules).
- `docs/PROJECT_STATUS.md` = WHAT EXISTS today (audit).
- `docs/ROADMAP.md` (this file) = WHAT TO BUILD next, with decisions and acceptance criteria.

---

## How to use this file (instructions for Claude Code)

When the user says "continue the roadmap" or "start the next task":

1. Read `CLAUDE.md`, `docs/PROJECT_STATUS.md` and this file.
2. Find the first phase in the checklist below that is not checked `[x]`.
3. Inspect the related code. Compare it with the phase specification.
4. Reply with:
   - what already exists for this phase
   - your step-by-step plan, naming the `_template/layouts` pages and existing Blade components
     each screen will follow (see "UI rules")
   - your questions (business rules you are not sure about)
5. Wait for confirmation. Then work one step at a time and wait for confirmation after each step.
6. Every step ships with feature tests. All existing tests must keep passing.
7. When the phase is done:
   - check it `[x]` in the checklist below
   - update `docs/PROJECT_STATUS.md` (modules status, where we stopped, new problems)
   - add any new decision to the Decisions section of this file

Never skip a phase. Never build features of a later phase early. If a later phase needs a hook,
fire an event and leave a note.

If something in this file conflicts with the code, stop and ask. Do not guess.

---

## Checklist

- [x] Phase 1: Foundation (users, roles, permissions, branches, audit log)
- [x] Phase 2: Settings and documents engine
- [ ] Phase 2.5: Alignment and fixes
- [ ] Phase 3: Workers, clients, status engine
- [ ] Phase 4: Recruitment
- [ ] Phase 5: Monthly rental, payments, Wafeq invoicing, invoice delivery
- [ ] Phase 6: Camps (movements, daily count)
- [ ] Phase 7: Hourly rental
- [ ] Phase 8: Transport and driver app
- [ ] Phase 9: Cases (returns, sponsorship transfer, deportation, absconding)
- [ ] Phase 10: Agency portal
- [ ] Phase 11: Public website and client portal
- [ ] Phase 12: Alerts and messaging
- [ ] Phase 13: Reports and real dashboard
- [ ] Phase 14: Data migration from Excel

---

## Business summary

The company works in domestic labour in Saudi Arabia, with two activities:

1. **Recruitment for clients.** External agencies abroad send CVs to the company. The recruitment
   team verifies data, interviews, and approves candidates. Marketing publishes them. A client
   chooses a candidate, the company creates a Musaned contract. The external agency handles the
   medical exam, visa and travel; the company follows up step by step until arrival. The company
   receives the worker, delivers him or her to the sponsor and follows a 90-day probation.
2. **Labour rental.** The company recruits workers on its own sponsorship (same recruitment steps).
   After arrival they live in company camps and are rented to clients monthly or hourly
   (morning and evening shifts, 5 hours each).

Branches: Riyadh and Qassim today, more later. Each branch has camps.
Workers can be women or men (housemaids, drivers, nannies...).

---

## Decisions

| # | Decision |
|---|---|
| D1 | A candidate and a worker are ONE record in `workers`, moving through statuses. No separate candidates table. |
| D2 | Countries are referenced only by `country_id`. No nationality codes. `countries.code` is optional. |
| D3 | Every worker belongs to a branch from intake. |
| D4 | `settings.edit` = system configuration (general, appearance, mail, security, maintenance, backups). `settings.manage` = business reference data (countries, stages, professions, shifts, prices, lookups, alerts). |
| D5 | Timezone `Asia/Riyadh`. Dates stored Gregorian. Hijri display may be added later. |
| D6 | No hard delete of business data, including document versions (soft delete, files kept on disk). |
| D7 | All emails and external calls (mail, Wafeq, WhatsApp) run in queued jobs. Test mail may stay synchronous. |
| D8 | Dashboard demo figures only when `APP_ENV=local`. Phase 13 replaces them with real data. |
| D9 | Agency portal shows the agency's own candidates' passport numbers unmasked. |
| D10 | `_template/` stays in the project. It is the UI reference for every screen (see "UI rules"). It is not served to users. |
| D11 | Database: MySQL 8 for development, tests and production. |
| D12 | Clients can be individuals or companies. Identifier type: national ID, iqama, or commercial registration. All are masked without permission. |
| D13 | Sensitive identifiers (passport, iqama, national ID, CR) are stored encrypted, with a lookup column = HMAC-SHA256 of the normalized value using `IDENTIFIER_HASH_KEY` from `.env`. Uniqueness and exact search use the lookup column. |
| D14 | Hourly rental is NOT a worker status. A worker in hourly rental stays `available`; availability per day and shift is calculated from bookings. |
| D15 | Worker status changes only through `WorkerStatusService`. Allowed transitions live in `config/workflow.php`. |
| D16 | No integration with Musaned. Musaned CV number and contract number are stored as reference fields. Documents printed from Musaned are uploaded manually. |
| D17 | Wafeq: invoices are issued as FINAL after payment verification. Refunds create credit notes linked to the original invoice. |
| D18 | Invoices are sent to clients by email (PDF attached). WhatsApp is prepared but disabled until a provider is connected. A manual "Share via WhatsApp" button (wa.me link with a signed invoice link) works now. |
| D19 | The external agency portal is optional per agency. If an agency does not use it, the recruitment team enters and updates everything. |
| D20 | The client portal is optional. Clients can send requests from the website without logging in. Staff do everything from the dashboard. If a client logs in, all his data is there. |
| D21 | Workers have no access to the system. |
| D22 | Documents per country are dynamic (settings). The Philippines needs an authority file printed and submitted to the Philippine Labor Office in Riyadh. The file contains the worker's uploaded documents in the configured order plus the list of the latest completed visas for the Philippines (count from settings). |
| D23 | UI uses the existing Upzet Bootstrap 5 template and the project's Blade components. Every screen follows the layouts and components in `_template/layouts`. No Tailwind, no new CSS framework, no invented styles. See "UI rules". |

---

## UI rules (apply to every screen in every phase)

The design reference is `_template/layouts` (Upzet Bootstrap 5 admin template), together with
the layouts and components already used in `resources/views`.

Before building any screen:
1. Look in `_template/layouts` for the closest page to what you are building
   (list/table, form, profile/details, tabs, calendar, dashboard cards, modals, wizards,
   timeline, invoice, file upload, empty state...).
2. Look in `resources/views/components` and existing admin views for a component or partial that
   already does it (`x-alerts`, `x-action-form`, `x-status-badge`, `x-sensitive`,
   `x-reference.*`, `x-documents.*`, list/form/show patterns of branches, users, roles, settings).
3. In your plan for the step, name the template page(s) and existing components you will follow.

When building:
- Copy the markup structure and CSS classes from the template page. Do not invent new styles,
  colors, spacing or icons. Use the icon sets already in the template (Remix / MDI).
- Use only `public/assets` CSS/JS that the layouts already load. If a template page needs a plugin
  (calendar, charts, date picker, select, drag and drop), check whether it exists in
  `public/assets/libs` first, and propose it before adding it to a layout.
- Every new pattern used in more than one screen becomes a Blade component in
  `resources/views/components` (e.g. data table with filters, profile header, tab container,
  timeline, calendar grid, stat card, file row).
- RTL and LTR: use the template's RTL CSS and logical classes (`ms-`/`me-`, `text-start`/`text-end`).
  Check every new screen in Arabic and English.
- Keep visual consistency with the existing screens (branches, users, roles, settings): same page
  header, breadcrumb, card style, buttons, badges, empty states and flash messages.
- No Tailwind or Vite in Blade (D23).

Do not edit files inside `_template/`. It is read-only reference.

---

## Statuses and transitions (reference for all phases)

`App\Enums\WorkerStatus` (string backed, labels from lang, badge color):

| Group | Value | Arabic |
|---|---|---|
| Recruitment | candidate_new | مرشح جديد |
| | under_review | قيد المراجعة |
| | rejected | مرفوض |
| | ready_for_selection | جاهز للاختيار |
| | on_hold | محجوز مؤقتا |
| | contracted | متعاقد عليه |
| | in_travel_process | في إجراءات السفر |
| | arrived | واصل |
| Client sponsorship | delivered | مسلم للكفيل |
| | in_probation | في فترة التجربة |
| | probation_completed | منتهي التجربة |
| Company sponsorship | available | متاح للتأجير |
| | rented_monthly | مؤجر شهري |
| Special | sick | مريض |
| | suspended | موقوف |
| | exit_reentry | خروج وعودة |
| | awaiting_decision | منتظر قرار |
| | for_deportation | للترحيل |
| | for_transfer | لنقل الكفالة |
| Final | deported | مرحل |
| | transferred | منقول الكفالة |
| | absconded | هارب |

Allowed transitions (`config/workflow.php`):

```
candidate_new        -> under_review, rejected
under_review         -> ready_for_selection, rejected, candidate_new
rejected             -> candidate_new
ready_for_selection  -> on_hold
on_hold              -> ready_for_selection, contracted
contracted           -> in_travel_process
in_travel_process    -> arrived
arrived              -> delivered (client sponsor only), available (company sponsor only), awaiting_decision
delivered            -> in_probation
in_probation         -> probation_completed, awaiting_decision, absconded
available            -> rented_monthly, sick, suspended, exit_reentry, awaiting_decision
rented_monthly       -> available, awaiting_decision, absconded
sick, suspended, exit_reentry -> available, awaiting_decision
awaiting_decision    -> for_deportation, for_transfer, available
for_deportation      -> deported
for_transfer         -> ready_for_selection, transferred
deported, transferred, absconded -> (final)
```

Manual transitions from the worker profile (config `workflow.manual`): sick, suspended,
exit_reentry, available (only from sick, suspended, exit_reentry), awaiting_decision, absconded.
Every other transition is triggered by a module through the service.

---

## Phase 2.5: Alignment and fixes

**Goal:** apply decisions D1 to D23 that affect existing code, and fix blocking problems from
PROJECT_STATUS.md section 10, before Module 3.

**Scope**

1. **MySQL (D11).** Switch `.env` / `.env.example` to MySQL. Tests use a separate MySQL database
   (`sanad_testing`) configured in `phpunit.xml`. Run all migrations fresh on MySQL and fix any
   SQLite-only syntax. Document setup in CLAUDE.md.
2. **Timezone (D5).** `Asia/Riyadh` in `config/app.php`. Check backup hour and prune schedule.
3. **Country reference (D2).**
   - Add `country_id` (FK) to `workers`, migrate any existing codes, drop `nationality`.
   - Drop `agencies.country` and the `saving` sync hook in `Agency`.
   - Update `Country::isInUse()` to check workers and agencies by id.
4. **Merge candidates into workers (D1, D3).**
   - Add to `workers`: `profession_id` (FK, nullable for now), keep `agency_id`, `branch_id`.
   - Move existing candidate rows (demo data only) to workers, move their documents and prints
     (`documentable_type`) to Worker.
   - Make `Worker` a document owner (`DocumentOwner`, `HasDocuments`), register alias `worker` in
     `config/documents.php`, remove the `candidate` owner.
   - Point the agency portal to workers filtered by `agency_id`.
   - Update the documents demo controller, factories and tests. Drop `candidates` table and
     `Candidate` model.
   - Keep permission names `candidates.*` (they describe recruitment actions on workers in the
     candidate stage).
5. **Settings permissions (D4).** Document the meaning in `config/permissions.php` comments,
   `lang` labels and CLAUDE.md. No route changes unless a route contradicts D4.
6. **Branch delete safety.** Add `Branch::camps()` and include camps in the destroy check.
7. **Soft-deleted document versions (D6).** Add `SoftDeletes` to `documents`, `document_files`,
   `document_prints`. "Delete version" soft-deletes the row and keeps the file. The checklist
   ignores soft-deleted versions. Audit logged.
8. **Queued mail (D7).** `TwoFactorCode` and password reset notifications implement
   `ShouldQueue`. Document `queue:work` in CLAUDE.md. Tests use `Notification::fake()`.
9. **Dashboard demo (D8).** `DashboardDemoData` used only when `APP_ENV=local`. Otherwise show
   empty states with real zero counts.
10. **Arabic validation (M7).** Complete `lang/ar/validation.php` with every rule present in
    `lang/en/validation.php`.
11. **Cleanup.** Keep `_template/` (D10). Add `QPDF_BINARY` and
    `IDENTIFIER_HASH_KEY` to `.env.example`. Add missing `audit.targets` labels. Add `Trip::driver()`
    and `Worker::agency()` relations. Remove `welcome.blade.php`, `tests/Unit/ExampleTest.php`,
    the `inspire` command.

**Acceptance**
- All tests pass on MySQL.
- No reference to `candidates` table or `Candidate` model remains.
- Agency portal lists the agency's workers in candidate statuses.
- Documents demo works with a Worker owner.
- Deleting a document version keeps the file and the row (soft deleted).
- Branch with camps cannot be deleted.
- Arabic UI shows Arabic validation messages for every rule.

---

## Phase 3: Workers, clients, status engine

**Goal:** the core records of the system and the engine that controls worker status.

**Depends on:** Phase 2.5.

**Data**

`workers` (alter the existing table):
- Identity: `name_ar`, `name_en`, `gender`, `birth_date`, `religion`, `marital_status`, `photo_path` (private disk)
- Origin: `country_id`, `profession_id`, `agency_id`
- Location: `branch_id`, `camp_id` (nullable)
- Passport: `passport_no` (encrypted), `passport_lookup` (unique), `passport_type`, `passport_expiry`
- Residency: `iqama_no` (encrypted, nullable), `iqama_lookup` (unique, nullable), `iqama_expiry`
- `medical_expiry`
- Work: `languages` (json), `experience_years`, `skills` (json), `salary`
- `sponsor_type` (client / company, nullable until contract), `rental_type` (none / monthly / hourly)
- `musaned_cv_no` (nullable)
- `status`, `status_changed_at`, `created_by`
- Replace the old plain `passport_number` / `iqama_number` columns (migrate values).

`worker_salary_changes`: worker_id, old_salary, new_salary, reason, changed_by, changed_at.

`worker_status_histories`: worker_id, from_status, to_status, reason, related_type, related_id,
changed_by, changed_at.

`clients`:
- `client_type` (individual / company)
- `name_ar`, `name_en`
- `id_type` (national_id / iqama / commercial_registration), `id_number` (encrypted), `id_lookup` (unique)
- `phone`, `phone2`, `email`, `city`, `district`, `national_address`
- `branch_id` (BelongsToBranch), `visa_balance` (int, default 0), `notes`
- `user_id` (nullable, reserved for client portal), `wafeq_contact_id` (nullable, used in Phase 5)
- `created_by`, timestamps, soft deletes

`document_types`: add `applies_to` (worker / client), default worker. Add to the settings screen.
The checklist filters by `applies_to`.

**Logic**
- `App\Support\IdentifierHasher`: normalize (trim, remove spaces, uppercase) + HMAC (D13).
- `WorkerStatusService::transition(Worker, WorkerStatus $to, ?string $reason, ?Model $related)`:
  transaction + `lockForUpdate`, validates against `config/workflow.php`, sponsor-type rule for
  `arrived`, writes history, audit log, fires `WorkerStatusChanged`. `status` not fillable.
- `allowedTransitions()` and `manualTransitions()` for the UI.
- Client is a document owner (country and profession return null; checklist uses
  `applies_to = client`).

**Screens**
- Workers: list (search name ar/en, exact passport, exact iqama; filters status, country,
  profession, agency, branch, camp, sponsor_type, rental_type; export), create/edit
  (agency filtered by country, camp filtered by branch), profile.
- Clients: list (search name, phone, exact ID; filter branch, type; export), create/edit, profile.
- Profile tabs from `config/profile_tabs.php` (key, label key, partial, permission, sort).
  - Worker: overview (with salary history), documents (checklist + authority print buttons
    matching the worker's country), status history.
  - Client: overview, documents.
- "Change status" modal on the worker profile: manual transitions only, reason required.
- Masking with the existing `SensitiveData` / `<x-sensitive>` everywhere (lists, profiles,
  exports).
- Sidebar: enable Workers and Clients.

**Factories and seeders:** realistic bilingual factories; local seeder with 30 workers across
statuses, countries, branches and both sponsor types; 10 clients (individuals and companies).

**Acceptance**
- Duplicate passport, iqama, client ID rejected (case and spaces ignored).
- Status cannot be mass-assigned; invalid transition throws; sponsor rule enforced.
- Every transition writes history, audit and event.
- Salary change requires reason and writes history.
- Riyadh user gets 404 on a Qassim worker or client.
- Masking works without `*.view_sensitive`.
- Worker and client documents tabs show the right document types.

---

## Phase 4: Recruitment

**Goal:** from a new candidate until arrival and delivery (or availability for rental).

**Depends on:** Phase 3.

**Settings additions:** review checklist items (bilingual, ordered); `is_driver` flag on
professions; probation days (default 90); ticket types lookup (international, domestic,
deportation); `country_stage_id` (nullable) on `document_types` to link a document to a stage.

**Data**
- `candidate_reviews`: worker_id, review_item_id, result (pass/fail), note, reviewed_by, reviewed_at
- `interviews`: worker_id, interviewer_id, date, result (passed/failed/pending), notes
- `correction_requests`: worker_id, agency_id, field, description, status (open/resolved/cancelled),
  opened_by, opened_at, resolved_by, resolved_at
- `holds`: worker_id, client_id (nullable), contact_name, contact_phone, created_by, expires_at,
  released_at, release_reason
- `recruitment_requests`: request_no (RQ-YYYY-####), worker_id, client_id (nullable for company
  sponsor), agency_id, branch_id, sponsor_type, musaned_contract_no, musaned_contract_date,
  visa_no, visa_category, visa_issued_at, visa_expiry, arrived_at, delivered_at, probation_end,
  status (active/completed/cancelled), cancel_reason, created_by
- `request_stages`: request_id, country_stage_id, sort_order, started_at, due_at, completed_at,
  completed_by, notes
- `tickets`: request_id (nullable), case_id (nullable, Phase 9), type, flight_no, from_city,
  to_city, departure_at, arrival_at, paid_by (company/client/agency), notes

**Flow and rules**
1. Intake: new worker in `candidate_new` (staff form; agency portal in Phase 10).
2. Review: checklist pass/fail; "Start review" -> `under_review`.
3. Interview; for drivers, trade test certificate and video are document types.
4. Correction requests to the agency; approval blocked while any is open.
5. Approve only if all checklist items passed, interview passed, no open corrections,
   `musaned_cv_no` filled -> `ready_for_selection`, event `CandidateApproved`.
6. Reject with reason -> `rejected`; resubmit -> `candidate_new`.
7. Hold: one active hold per worker (transaction + lock), duration from settings,
   `on_hold`; scheduled command every 5 minutes releases expired holds.
8. Recruitment request: from `on_hold` (client sponsor) or `ready_for_selection` (company sponsor);
   sets `sponsor_type`; -> `contracted` -> `in_travel_process`; event `RecruitmentContracted`.
9. Travel stages created from the country's active stages; due_at from max_days; next stage
   starts when the previous completes; a stage linked to required documents cannot complete
   while they are missing; daily command fires `StageOverdue`.
10. Upcoming candidates list: workers in `contracted` / `in_travel_process`; filters country,
    agency, client, stage, overdue; columns name, nationality, agency, client, current stage,
    days in stage, documents uploaded/required.
11. Real `CompletedVisasProvider`: latest N requests with `visa_issued_at` for the country
    (columns: name, passport, visa no, visa date, sponsor).
12. Ticket: international ticket fires `TicketRecorded`. "Mark arrived" -> `arrived`,
    event `WorkerArrived`.
13. Delivery (client sponsor): delivery receipt uploaded (document type), `delivered_at`,
    `probation_end` = delivered_at + probation days, -> `delivered` -> `in_probation`,
    event `WorkerDelivered`.
14. Company sponsor: "Move to rental" -> `available`, event `WorkerReadyForRental`.
15. Daily command: probation ended with no open case -> `probation_completed`, request completed.

**Screens:** candidates queue, review screen, interviews, corrections list (global, by agency),
holds, request page with stages and ticket, upcoming list, worker tab "Recruitment", client tab
"Recruitment requests". Sidebar: Recruitment requests, Candidates, Documents.

**Acceptance:** approval blocking rules; one active hold (concurrency test); hold expiry; request
number sequence per year; stages order and due dates; stage blocked by missing documents;
overdue detection; completed visas provider; sponsor-based delivery/rental rule; probation date
from settings; all status changes through the service.

---

## Phase 5: Monthly rental, payments, Wafeq invoicing, invoice delivery

**Goal:** monthly contracts, payments with verification, automatic final invoices in Wafeq,
refunds with credit notes, invoice delivery.

**Depends on:** Phase 4. Needs a Wafeq sandbox account and API key, and SMTP in settings.

**Before coding the integration:** read the official Wafeq API documentation
(developer.wafeq.com) for contacts, invoices (draft/sent/finalized), credit notes, tax rates,
items/accounts, external reference, PDF download. Send a short summary of endpoints and fields
and wait for confirmation.

**Settings additions**
- Wafeq: enabled, api_key (encrypted), environment (sandbox/production), mapping per charge type
  (monthly_rental, hourly_booking, recruitment_fee, camp_fee, delivery_fee) to Wafeq item/account
  and tax rate, auto_issue_on_verify (default true), "Test connection", last sync time.
- Invoice delivery: email enabled, auto send after issue, subject/body templates ar/en with
  variables {client_name} {invoice_no} {amount} {contract_no} {invoice_link}; WhatsApp enabled
  (cannot be enabled while provider = none), provider (none), templates ar/en, note "WhatsApp
  provider not connected yet".

**Data**
- `rental_contracts`: contract_no (RC-YYYY-####), worker_id, client_id, branch_id, type
  (new/renewal/replacement), previous_contract_id, start_date, end_date, price, vat_percent,
  vat_amount, total, status (draft/awaiting_payment/active/ended/cancelled), end_reason (lookup),
  ended_at, return_date, cancelled_by_side (client/worker/company), created_by
- `payments`: payment_no, client_id, payable_type/payable_id, charge_type, amount, method (lookup),
  bank, paid_at, proof (private file), status (pending_verification/verified/rejected),
  verified_by, verified_at, credited_at, reject_reason
- `invoices`: invoice_no, payment_id (unique), client_id, invoiceable_type/id, amount, vat_amount,
  total, wafeq_id, wafeq_number, status (pending/issued/failed), attempts, last_error, issued_at,
  pdf_path
- `refunds`: refund_no, invoice_id, contract_id, amount, reason, status
  (requested/approved/rejected/processed), requested_by, approved_by, approved_at,
  wafeq_credit_note_id, credit_note_number, refunded_at, proof
- `integration_logs`: service, action, request (no secrets), response, status, duration, created_at
- `invoice_deliveries`: invoice_id, channel, recipient, status (sent/failed/skipped), error,
  sent_by (null = automatic), sent_at

**Rules**
- Contract requires worker `available` (or rented to same client for renewal).
- No overlapping awaiting_payment/active contracts for a worker (transaction + lock).
- Contract cannot leave draft without required client documents.
- Active only when verified payments cover the total -> worker `rented_monthly`,
  event `RentalContractActivated`.
- Renewal: new contract linked to the previous, starts the day after.
- Replacement: ends current with reason, new contract with another worker for the remaining
  period, previous worker -> `available`.
- End/cancel: reason, side, return date; worker -> `available` or `awaiting_decision`;
  event `RentalContractEnded`; daily `RentalContractEndingSoon`.
- Payment verification fires `PaymentVerified` -> `IssueWafeqInvoice` job (idempotent by payment,
  external reference, FINAL, retries 1/5/15/60 min, then failed + `InvoiceIssueFailed`; manual
  retry; on success store PDF, fire `InvoiceIssued`).
- Refund approved by finance manager -> `IssueWafeqCreditNote` job (idempotent, linked to
  original invoice); amount <= invoice total minus previous refunds.
- `InvoiceDeliveryService`: EmailChannel (queued, client language, PDF attached), WhatsAppChannel
  with `NullWhatsAppDriver` (records "skipped"). Manual buttons "Send by email" and
  "Share via WhatsApp" (`https://wa.me/{phone}?text=...` with a 7-day signed PDF link).

**Screens:** contracts list (tabs draft/awaiting payment/active/ending soon/ended/cancelled),
create, contract page; payments awaiting verification; invoices (status, retry, send, share);
refunds; monthly finance report (filters month/branch/charge type, totals, Excel); tabs:
worker "Contracts", client "Contracts", "Payments", "Invoices". Sidebar: Monthly contracts,
Payments, Invoices, Refunds.

**Acceptance:** overlap blocked (concurrency); draft blocked without client documents;
activation only when covered; status changes through service; one invoice per payment even if
job runs twice; retries then failed; refund limit; credit note dispatched; email with PDF in
client language; WhatsApp skipped and logged; wa.me link and signed URL valid and expiring;
all Wafeq calls mocked in tests.

---

## Phase 6: Camps (movements, daily count)

**Goal:** know who is in which camp at any moment, without daily sheets.

**Depends on:** Phase 5.

**Data**
- `camp_movements`: worker_id, camp_id, type (in/out), moved_at, reason (lookup or text),
  worker_opinion (nullable), related_type/related_id, created_by
- `workers.camp_id` = current camp (updated by the service).
- Settings: daily camp fee for client-sponsored workers waiting for pickup (per branch).

**Logic**
- `CampService::checkIn(worker, camp, reason, related)` / `checkOut(...)`, transaction, prevents
  double check-in, updates `workers.camp_id`, audit.
- Listeners:
  - `WorkerArrived`: client sponsor not delivered yet -> check-in, start counting fee days
  - `WorkerDelivered`: check-out; create camp fee payment (charge_type camp_fee) if days > 0
  - `WorkerReadyForRental`: check-in
  - `RentalContractActivated`: check-out; `RentalContractEnded`: check-in with reason and opinion
- Daily count = query (workers whose latest movement is `in` for a camp, any past date).
- Capacity warning when check-in exceeds capacity.
- `Camp::isInUse()` checks movements.

**Screens:** camp dashboard (per camp: count by status, rental vs individual), daily count for any
date (export), check-in/out actions, "awaiting decision" list with days in camp, worker tab
"Camp movements". Sidebar: Camp movements.

**Acceptance:** no double check-in; listeners create the right movements; daily count correct
for past dates; camp fee calculated; branch scope.

---

## Phase 7: Hourly rental

**Goal:** book dedicated workers by day and shift without conflicts.

**Depends on:** Phase 6.

**Data**
- `hourly_bookings`: booking_no (RH-YYYY-####), worker_id, client_id, branch_id, shift_id, date,
  hours, address, district, price, vat, total, status (booked/done/cancelled/no_show),
  series_id (nullable), driver_id (nullable), delivered_at, picked_up_at, created_by
- Unique: (worker_id, date, shift_id) for non-cancelled bookings (generated column
  `active_key` = NULL when cancelled, unique index on it).
- `booking_series`: client_id, worker_id (nullable = any), shift_id, weekdays (json), start_date,
  end_date, status
- `worker_hours`: worker_id, booking_id, date, hours_worked (from actual times)

**Rules**
- Only workers with `rental_type = hourly` and status `available`.
- Availability calendar per day and shift.
- Series generates bookings in advance (scheduled command), skipping conflicts and reporting them.
- Payment and invoice reuse Phase 5 (charge_type hourly_booking).
- Cancel / no-show frees the slot.
- Event `HourlyBookingCreated` (Phase 8 creates trips).

**Screens:** calendar (days x shifts, booked/available), create booking, series, booking list,
worker hours report (salary basis), client tab "Bookings". Sidebar: Hourly bookings.

**Acceptance:** double booking blocked (concurrency); cancelled slot reusable; series generation;
hours computed from actual times; invoice through Phase 5 flow.

---

## Phase 8: Transport and driver app

**Goal:** every trip any module needs, the driver's daily schedule, punctuality.

**Depends on:** Phase 7.

**Data**
- Drivers are users with `user_type = driver`, plus `driver_profiles`: user_id, branch_id, phone,
  vehicle, plate, is_active.
- Extend `trips`: type (airport_pickup, client_delivery, client_pickup, deportation,
  authority_delivery, domestic), worker_id, related_type/related_id, from/to text,
  planned_at, started_at, arrived_at, completed_at, lat/lng on actions, status
  (scheduled/started/done/cancelled), notes. Status as enum.

**Logic**
- Listeners create trips: `TicketRecorded` (airport pickup), `HourlyBookingCreated` (delivery and
  pickup), deportation decision (Phase 9), authority file printed (authority delivery, optional).
- Coordinator assigns drivers; daily schedule grouped by district.
- Driver portal (mobile web): today's trips, buttons Start / Arrived / Done with geolocation,
  times cannot be edited by the driver.
- Hourly booking `delivered_at` / `picked_up_at` come from trip actions.
- Punctuality = actual vs planned.

**Screens:** trips list and calendar, assign driver, drivers management, driver portal,
punctuality report. Sidebar: Drivers, Trips.

**Acceptance:** trips created by events; driver sees only own trips; action times recorded once;
punctuality report correct.

---

## Phase 9: Cases (returns, transfer, deportation, absconding)

**Goal:** handle a worker returned by a sponsor, ended rental, absconding, and the decision.

**Depends on:** Phase 8.

**Data**
- `cases`: case_no, worker_id, type (return/absconding/end_of_contract), related request or
  contract, opened_at, reason (lookup), worker_opinion, decision (deportation/transfer/back_to_rental),
  decided_by, decided_at, status (open/closed), closed_at, notes
- `musaned_refunds`: case_id, client_id, amount, status (requested/received), dates
  (tracking only, no integration)

**Rules**
- Return during probation: case opened, worker -> `awaiting_decision`, camp check-in.
- Decision (`cases.decide`):
  - deportation -> `for_deportation`, ticket (type deportation), trip, then `deported`, case closed
  - transfer -> `for_transfer` -> `ready_for_selection` (flag "inside kingdom, available now");
    old client: Musaned refund tracking and `visa_balance + 1`; new sponsor creates a new
    recruitment request linked to the same worker; when done -> `transferred`
  - back to rental (company sponsor) -> `available`
- Absconding: report date, ends active contract, worker -> `absconded`, case closed.

**Screens:** cases list (open/closed, type), case page with decision, worker tab "Cases",
client tab "Musaned refunds". Sidebar: Cases.

**Acceptance:** each decision path changes statuses through the service, creates the right
records and events; visa balance updated; branch scope.

---

## Phase 10: Agency portal

**Goal:** agencies that want it can work directly in the system (D19).

**Depends on:** Phase 4 (uses its data and rules).

**Scope**
- Agency users submit candidates (same form and duplicate check), upload documents to the
  checklist, respond to correction requests, update travel stages and ticket.
- See only own workers (any status they handled), counts per stage.
- Passport numbers unmasked for own candidates (D9).
- Portal permissions on the `external_agency` role so features can be restricted.
- Notifications to the recruitment team on submission and responses (in-app; email in Phase 12).

**Acceptance:** isolation between agencies; all writes go through the same services as the
dashboard; audit log shows the agency user.

---

## Phase 11: Public website and client portal

**Goal:** public catalogue and optional client accounts (D20).

**Depends on:** Phases 4, 5, 7.

**Website (Blade, SEO-friendly, bilingual, cached)**
- Sections: recruitment (`ready_for_selection`), monthly rental (`available`, rental_type monthly),
  hourly (availability by day and shift), coming soon (arriving workers with expected date).
- Filters: nationality, profession, age, experience, religion, price, city.
- Worker public page: photo, video, skills, languages, experience. No sensitive data.
- Request form (name, phone) -> `website_requests` + hold (Phase 4 rules); hourly booking request.
- Cache invalidated on `WorkerStatusChanged`.

**Client portal (optional)**
- Login by phone/email; account linked to an existing client by ID or phone, or created by staff.
- Shows requests and recruitment progress, contracts, bookings, payments, invoices (download),
  upload payment proof, request renewal, new request.

**Dashboard:** website requests list with assignment to marketing and status.

**Acceptance:** only allowed statuses shown; no sensitive data in HTML; requests create holds;
client sees only own data.

---

## Phase 12: Alerts and messaging

**Goal:** remind staff before deadlines and notify clients automatically.

**Depends on:** all previous event sources.

**Data:** `alerts` (type, related, branch_id, assigned role/user, due_at, status new/done,
done_by, done_at), `message_templates` (key, channel, ar, en), `message_logs`.

**Alert generators (daily commands, periods from settings):** iqama, passport and medical expiry;
contract ending; probation ending; overdue travel stages; workers too long in camp awaiting
decision; payments awaiting verification; failed invoices; documents at authority too long.

**Messages (listeners, queued, email now, WhatsApp driver later):** arrival notice
(`TicketRecorded`), booking confirmation, contract ending reminder, invoice issued (Phase 5),
payment received.

**Screens:** bell with counts, alerts list (filters, mark done), templates in settings,
message log.

**Acceptance:** no duplicate alert for the same item and period; alerts respect branch scope;
messages in client language; WhatsApp skipped and logged while disabled.

---

## Phase 13: Reports and real dashboard

**Goal:** replace demo data with real figures and provide reports.

**Dashboard:** workers by status, in camp, available, rented, arriving; active contracts;
recruitment pipeline by stage; follow-ups (alerts); revenue this month; by branch; by nationality;
latest contracts. All respect the branch switcher.

**Reports (filters + Excel/PDF):** daily camp count; contracts and revenue by branch/period;
recruitment by stage/country/agency with average stage duration; return reasons by agency/nationality;
agency performance (arrivals, average duration, return and absconding rates, corrections);
hourly occupancy and worker hours; driver punctuality; nationality summary (visas issued/cancelled/active,
arrived, deported, transferred, absconded); finance monthly reconciliation.

Heavy reports use nightly summary tables.

**Acceptance:** figures match the underlying records in tests; branch scope.

---

## Phase 14: Data migration from Excel

**Goal:** import existing Excel data cleanly.

**Source files:** India status report (recruitment), overall arrival/departure notice,
arrival notice format, rental contracts record, camp daily count (one sheet per day since April),
visit schedule (hourly).

**Approach**
1. Artisan commands import each file into staging tables as-is.
2. Mapping tables for agencies, nationalities, statuses, reasons (fix spelling variants).
3. Deduplicate workers by normalized passport (HMAC lookup); report conflicts.
4. Fix numbers stored as dates and impossible dates; report what could not be fixed.
5. Load into real tables; build status history.
6. Rebuild camp movements by comparing each daily sheet with the previous day.
7. Link existing document URLs to owners; report broken links.
8. Every command supports `--dry-run` and can be re-run without duplicates.
9. Final report: imported, skipped, conflicts, with file and row numbers.

**Acceptance:** dry run produces the report without writing; re-run does not duplicate;
counts reconcile with source files.
