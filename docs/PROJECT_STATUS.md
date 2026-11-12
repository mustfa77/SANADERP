# Sanad ERP: Project Status

Audit date: 2026-11-12 · Branch: `feature/users-roles-settings-branches` · Last commit: `63099cf Add reference data settings, documents engine and dashboard demo data`

This file describes what exists in the code **today**. It was written during a read-only audit: no code, migrations, config or tests were changed.

---

## 1. Stack

| Item | Value |
|---|---|
| Laravel | 13.40.0 (`laravel/framework ^13.17`) |
| PHP | 8.3.36 (Laragon `C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64`). PHP is **not on PATH** in the default shell |
| Database | SQLite (`database/database.sqlite`, `DB_CONNECTION=sqlite`). MySQL 8.4 is installed in Laragon but not used |
| Tests DB | SQLite `:memory:` (phpunit.xml) |
| Queue driver | `database` (`QUEUE_CONNECTION=database`). No jobs exist yet |
| Mail driver | `log` in `.env`/`.env.example`. It can be overridden at runtime from Settings > Mail (`SiteSettings::applyMailConfig()`) |
| Cache / session | `database` / `database` |
| Storage disks | `local` (private: documents, document-prints, backups, mpdf-tmp), `public` (avatars, branding), `s3` (configured, unused). `public/storage` is linked |
| Locale | `APP_LOCALE=ar`, `APP_FALLBACK_LOCALE=en`, available: `ar` (rtl), `en` (ltr), from `config/app.php` `available_locales` |
| Timezone | `UTC` (`config/app.php:68`) |

**Composer (direct)**

| Package | Version | Use |
|---|---|---|
| laravel/framework | 13.40.0 | |
| spatie/laravel-permission | 8.5.0 | roles & permissions |
| mpdf/mpdf | 8.3.1 | authority file PDF (`AuthorityFilePrintService`) |
| spatie/simple-excel | 3.11.0 | CSV/XLSX exports (branches, audit log) |
| laravel/tinker | 3.0.2 | |
| dev: laravel/boost 2.10.4, laravel/pail 1.2.7, laravel/pao 1.1.5, laravel/pint 1.32.1, phpunit/phpunit 12.5.43, mockery 1.6.15, collision 8.9.5, fakerphp/faker 1.24.1 | | |

**Frontend:** the UI uses the **Upzet Bootstrap 5 admin template**, served as static files from `public/assets` (bootstrap.min.css / bootstrap-rtl.min.css, app.min.css / app-rtl.min.css, Remix/MDI icons). `package.json` has Vite 8, `laravel-vite-plugin` 3.1, Tailwind 4 and `@tailwindcss/vite`, but the Blade layouts do not use Vite or Tailwind. The raw template source is committed in `_template/` (24 MB).

**`.env.example` keys:** APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL, APP_LOCALE, APP_FALLBACK_LOCALE, APP_FAKER_LOCALE, APP_MAINTENANCE_DRIVER, (APP_MAINTENANCE_STORE), (PHP_CLI_SERVER_WORKERS), BCRYPT_ROUNDS, LOG_CHANNEL, LOG_STACK, LOG_DEPRECATIONS_CHANNEL, LOG_LEVEL, DB_CONNECTION, (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD), SESSION_DRIVER, SESSION_LIFETIME, SESSION_ENCRYPT, SESSION_PATH, SESSION_DOMAIN, BROADCAST_CONNECTION, FILESYSTEM_DISK, QUEUE_CONNECTION, CACHE_STORE, (CACHE_PREFIX), MEMCACHED_HOST, REDIS_CLIENT, REDIS_HOST, REDIS_PASSWORD, REDIS_PORT, MAIL_MAILER, MAIL_SCHEME, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET, AWS_USE_PATH_STYLE_ENDPOINT, VITE_APP_NAME, SUPER_ADMIN_NAME_AR, SUPER_ADMIN_NAME_EN, SUPER_ADMIN_EMAIL, SUPER_ADMIN_PHONE, SUPER_ADMIN_PASSWORD. Keys in brackets are commented out. `QPDF_BINARY` is read by `config/documents.php` but is missing from `.env.example`.

---

## 2. Project structure and conventions in use

| Concern | How it is done now | Where |
|---|---|---|
| Bilingual fields | Two columns `{field}_ar` / `{field}_en` (name, city, address, district, nationality, description, label). Read with `$model->trans('name')` (current locale, falls back to the other language) and `$model->translations('name')` (`['ar'=>..,'en'=>..]`) | `app/Models/Concerns/HasTranslations.php` |
| UI translations | PHP group files `lang/{ar,en}/*.php` (18 groups each) plus `lang/ar.json` for framework strings. Labels for permissions, modules, roles and audit actions all come from lang files | `lang/` |
| RTL / LTR | The layouts set `<html dir="{{ $dir }}">` and load `bootstrap-rtl.min.css` / `app-rtl.min.css` for `rtl`. `SetLocale` middleware picks session `locale`, then `users.preferred_language`, then `config('app.locale')`. Codes, phones and emails use `dir="ltr"` | `layouts/*.blade.php`, `layouts/partials/head-css.blade.php`, `app/Http/Middleware/SetLocale.php`, `LanguageController` |
| Permissions | spatie/laravel-permission. One catalogue in `config/permissions.php` (sections > modules > actions, `standard_actions`, `special`, default `roles` with wildcards). Read through `App\Support\PermissionRegistry`. Names follow `module.action` (snake_case). Custom `App\Models\Permission` / `App\Models\Role` add `label_ar/en`, `group`, `sort_order`, `name_ar/en`, `description_ar/en`, `is_system`, `requires_2fa`. Super Admin passes everything through `Gate::before`, except `GUARDED_ABILITIES` | `config/permissions.php`, `app/Support/PermissionRegistry.php`, `app/Providers/AppServiceProvider.php` |
| Route protection | Route middleware `can:` with either a permission (`can:settings.view`) or a policy ability (`can:update,branch`). Policies exist for User, Role, Branch and AuditLog. FormRequests also check `authorize()` | `routes/web.php`, `app/Policies/*` |
| Blade protection | `@can('perm')` and `$canX = auth()->user()->can(...)` at the top of views. The sidebar filters items by `permission` | `resources/views/**` |
| Branch scoping | `App\Models\Concerns\BelongsToBranch` trait adds global `App\Models\Scopes\BranchScope` (filters by the switcher's selected branch, else by `allowedBranchIds()`; `branches.all` or Super Admin sees all; guests/console are not filtered). Branch switcher: `POST admin/branch` stores `current_branch_id` in the session. Used by: `Worker`, `Trip`, `Camp`. Branch records themselves are restricted through `BranchPolicy::allowed()` (404 outside the user's branches) | `app/Models/Concerns/BelongsToBranch.php`, `app/Models/Scopes/BranchScope.php`, `app/Models/User.php` |
| Audit log | Manual calls only: `app(AuditLogger::class)->log('module.action', $target, $params, $old, $new)`. The message is a translation key `audit.actions.{key}` plus params (bilingual params given as `['ar'=>..,'en'=>..]`). No model observers. `AuditLog` and `LoginAttempt` are `Prunable` by retention settings | `app/Services/AuditLogger.php`, `app/Models/AuditLog.php`, `lang/*/audit.php` |
| File storage | Documents: private `local` disk under `documents/{document_id}/v{n}-{hash}`, streamed only through `DocumentController` routes. Prints: `document-prints/`. Avatars: `public` disk `avatars/`. Branding: `public` disk `branding/`. Backups: `local` `backups/` | `config/documents.php`, `DocumentFile::DISK` |
| Blade components | Anonymous: `x-alerts`, `x-action-form`, `x-status-badge`, `x-sensitive`, `x-reference.field`, `x-reference.active-badge`, `x-reference.move-buttons`. Class-based: `x-documents.checklist`, `x-documents.print-button` | `resources/views/components`, `app/View/Components/Documents` |
| Validation | Three styles: (1) **FormRequests** in `app/Http/Requests/{Admin,Auth}` for users, roles, branches and each settings tab; (2) **`Validator::make()`** in `ReferenceController` (rules generated from `ReferenceRegistry` field definitions) and `DocumentController`; (3) **`$request->validate([...])`** in `DocumentTypeController`, `TravelStageController`, `ProfileController` and `BranchSwitchController` | |
| Flash messages | `->with('success' / 'error', __('module.flash.key'))`, rendered by `<x-alerts />`. Special key `authority_file_missing` for the print modal | `resources/views/components/alerts.blade.php` |
| Soft deletes | Used on: users, branches, agencies, workers, candidates, trips, countries, country_travel_stages, authorities, camps, professions, shifts, prices, lookups, document_types. **Not** used on: documents, document_files, document_prints, completed_visa_list_settings, roles, permissions, settings, audit_logs, login_attempts | migrations |
| "In use" protection | Reference models expose `isInUse()`; delete is refused and the user is told to deactivate (`is_active`, `HasActiveFlag` trait) | `ReferenceController::destroy`, models |
| Ordering | `Sortable` trait (`sort_order`, optional group columns, `move()`, `reorderGroup()`) | `app/Models/Concerns/Sortable.php` |
| Enums | Backed string enums with a `label()` method that reads lang: `UserStatus`, `UserType` (also `homeRoute()`), `CampType`, `LookupType`, `RentalType`. **Not** enums: `candidates.status`, `trips.status` (plain strings) | `app/Enums` |
| Model attributes | Laravel 13 attributes: `#[Fillable([...])]`, `#[Hidden([...])]`; casts in `casts()` method | all models |
| Money | `decimal(10,2)` + cast `decimal:2`. VAT is stored per price (`vat_percent decimal(5,2)`, default 15). Calculations in `Price::vatAmount()` / `totalWithVat()` use float `round(..., 2)`. No money value object | `app/Models/Price.php` |
| Dates | Carbon casts (`datetime`), app timezone UTC, no Hijri support | |
| Sensitive data | `SensitiveData` service + `<x-sensitive :value permission type>` mask passport / iqama / national id / address unless the user has `*.view_sensitive` | `app/Services/SensitiveData.php` |
| Settings storage | Key/value `settings` table via `Setting::get/put/integer/boolean` (cached forever as `settings.all`). Integer settings are defined as constants with default/min/max: `Setting::SECURITY`, `MAINTENANCE`, `ALERTS`. Branding/mail/appearance in `SiteSettings` | `app/Models/Setting.php`, `app/Services/SiteSettings.php` |
| Reference data CRUD | One generic `ReferenceController` + `ReferenceRegistry` / `ReferenceResource` definitions generate routes, forms, lists, filters, validation and permissions for 9 lists | `app/Support/Reference/*` |
| User types / portals | `users.user_type` = `internal` → `/admin`, `external_agency` → `/agency`, `driver` → `/driver`, enforced by `user.type:` middleware. Group `panel` = auth, active, session.timeout, two-factor, password.changed, CheckMaintenanceMode | `bootstrap/app.php` |
| Code style | PHPDoc on classes/methods, constructor promotion, explicit return types, Pint | |

**Two conventions for the same thing**

1. **Country / nationality reference.** `country_id` foreign key on `agencies.country_id`, `authorities`, `prices`, `document_types`, `completed_visa_list_settings`; a **2-letter code string** on `workers.nationality`, `candidates.nationality` and `agencies.country` (legacy, synced from `country_id` by a `saving` hook in `app/Models/Agency.php:30`). `Candidate::documentCountryId()` (`app/Models/Candidate.php:24`) converts the code back to an id.
2. **Validation.** FormRequests (`app/Http/Requests/Admin/*`) vs `Validator::make` (`ReferenceController.php:138,171`, `DocumentController.php:43,58`) vs `$request->validate()` (`DocumentTypeController.php:137`, `TravelStageController.php:88`).
3. **Settings edit permission.** `settings.edit` (general, appearance, mail, security, maintenance, backups: `routes/web.php` settings group) vs `settings.manage` (alerts tab `routes/web.php` `settings.alerts`, `UpdateAlertSettingsRequest.php:15`, the reference lists countries/professions/shifts/prices/lookups, travel stages) vs `document_types.manage` (document types, completed visa lists) vs module permissions (`agencies.*`, `authorities.*`, `camps.*`). All of these screens sit under the same "Settings" menu.
4. **Branch-aware access.** Global scope (`BelongsToBranch`) for Worker/Trip/Camp, policy check for Branch, and nothing for Users and Candidates.

---

## 3. Database

All 28 migrations have run on the dev DB (batches 1–3). Abbreviations: `str(n)` = varchar(n) (default 255), `FK→t (rule)` = foreign key, `ts` = created_at+updated_at, `sd` = deleted_at.

### Framework tables
| Table | Columns | Keys | Migration |
|---|---|---|---|
| password_reset_tokens | email PK, token, created_at | PK email | 0001_01_01_000000 |
| sessions | id str PK, user_id (idx), ip_address(45), user_agent text, payload longtext, last_activity int (idx) | | 0001_01_01_000000 |
| cache, cache_locks | Laravel default | | 0001_01_01_000001 |
| jobs, job_batches, failed_jobs | Laravel default | | 0001_01_01_000002 |

### Access / foundation
| Table | Columns | Indexes / unique / FK | Created / altered |
|---|---|---|---|
| **users** | id; name_ar str null; name_en str null; email str; email_verified_at ts null; password; phone str(20) null; status str(20) def `active`; user_type str(20) def `internal`; agency_id; preferred_language str(2) def `ar`; avatar str null; last_login_at ts null; must_change_password bool def 0; two_factor_enabled bool def 0; two_factor_code str null; two_factor_expires_at ts null; remember_token; created_by; ts; sd | unique email, unique phone, idx status, idx user_type; FK agency_id→agencies (null on delete); FK created_by→users (null on delete) | created 0001_01_01_000000; altered 2026_11_10_134358 (adds fields, **drops `name`**) |
| **permissions** | id; name; guard_name; label_ar null; label_en null; group str(50) null; sort_order uint def 0; ts | unique(name, guard_name); idx group | 2026_11_10_134304 |
| **roles** | id; name; guard_name; name_ar null; name_en null; description_ar text null; description_en text null; is_system bool; requires_2fa bool; ts | unique(name, guard_name) | 2026_11_10_134304 |
| model_has_permissions | permission_id, model_type, model_id | PK(permission_id, model_id, model_type); FK→permissions cascade | 2026_11_10_134304 |
| model_has_roles | role_id, model_type, model_id | PK(role_id, model_id, model_type); FK→roles cascade | 2026_11_10_134304 |
| role_has_permissions | permission_id, role_id | PK; FKs cascade | 2026_11_10_134304 |
| **branches** | id; code str(20); name_ar; name_en; city_ar null; city_en null; address_ar str(500) null; address_en str(500) null; phone str(20) null; email null; manager_id; is_active bool def 1; ts; sd | unique code; FK manager_id→users (null on delete) | created 2026_11_10_134355; altered 2026_11_10_161730 |
| **user_branches** (pivot) | user_id; branch_id; ts | PK(user_id, branch_id); idx branch_id; FKs cascade | 2026_11_10_134400 |
| **login_attempts** | id; user_id null; email; successful bool; failure_reason str(50) null; ip_address str(45); user_agent text; created_at (useCurrent) | idx(email, created_at), idx(user_id, created_at); FK user_id→users (null on delete) | 2026_11_10_134401 |
| **audit_logs** | id; user_id null; action_key str(100); params json; target_type str null; target_id ubigint null; old_values json; new_values json; ip_address(45); user_agent text; created_at | idx action_key, idx created_at, idx(user_id, created_at), idx(target_type, target_id); FK user_id→users (null on delete) | 2026_11_10_134403 |
| **settings** | id; key str(100); value text null; ts | unique key | 2026_11_10_134405 |

### Reference data (Settings)
| Table | Columns | Indexes / unique / FK | Created / altered |
|---|---|---|---|
| **countries** | id; code str(2) null; name_ar; name_en; nationality_ar; nationality_en; sort_order uint; is_active bool; ts; sd | unique code; idx is_active | 2026_11_11_162448 |
| **country_travel_stages** | id; country_id; name_ar; name_en; sort_order; max_days usmallint null; is_active; ts; sd | idx(country_id, sort_order); FK country_id→countries **cascade** | 2026_11_11_162450 |
| **agencies** | id; name_ar; name_en; country_id null; contact_person null; country str(2) null (legacy); email null; phone str(30) null; is_active; ts; sd | FK country_id→countries (restrict) | created 2026_11_10_134356; altered 2026_11_11_162451 |
| **authorities** | id; country_id; name_ar; name_en; city_ar/en null; address_ar/en str(500) null; is_active; ts; sd | idx is_active; FK country_id→countries (restrict) | 2026_11_11_162453 |
| **camps** | id; branch_id; name_ar; name_en; district_ar/en null; address_ar/en str(500) null; capacity uint def 0; type str(20) (`CampType`); is_active; ts; sd | idx type, idx is_active; FK branch_id→branches (restrict) | 2026_11_11_162454 |
| **professions** | id; name_ar; name_en; sort_order; is_active; ts; sd | idx is_active | 2026_11_11_162456 |
| **shifts** | id; name_ar; name_en; start_time time; move_time time null; hours decimal(4,2); sort_order; is_active; ts; sd | idx is_active | 2026_11_11_162458 |
| **prices** | id; country_id; rental_type str(20) (`RentalType`); price decimal(10,2); vat_percent decimal(5,2) def 15; is_active; ts; sd | idx(country_id, rental_type, is_active); FK country_id (restrict). "One active per country+type" is enforced **in the app only** | 2026_11_11_162459 |
| **lookups** | id; type str(30) (`LookupType`); name_ar; name_en; sort_order; is_active; ts; sd | idx(type, sort_order) | 2026_11_11_162501 |
| **document_types** | id; country_id null; profession_id null; authority_id null; name_ar; name_en; sort_order; is_required bool; include_in_authority_file bool; is_active; ts; sd | idx(country_id, sort_order); FKs country/profession/authority (restrict) | 2026_11_11_162502 |
| **completed_visa_list_settings** | id; authority_id; country_id; visas_count usmallint def 30; is_active; ts | unique(authority_id, country_id); FKs cascade | 2026_11_11_162504 |

### Documents engine
| Table | Columns | Indexes / unique / FK | Created / altered |
|---|---|---|---|
| **documents** | id; documentable_type; documentable_id; document_type_id null; title null; current_version uint def 1; uploaded_by null; uploaded_at ts null; ts | morphs index; unique(documentable_type, documentable_id, document_type_id) `documents_owner_type_unique`; FK document_type_id (restrict); FK uploaded_by→users (null) | legacy shape created in 2026_11_10_134409 (candidate_id, agency_id, type, file_path, sd), **dropped and recreated** in 2026_11_11_162505 |
| **document_files** | id; document_id; version uint; file_path; original_name; mime str(100); size ubigint; uploaded_by null; created_at | unique(document_id, version); FK document_id cascade | 2026_11_11_162505 |
| **document_prints** | id; documentable_type/id; authority_id; file_path; printed_by null; printed_at ts | morphs index; FK authority_id (restrict) | 2026_11_11_162505 |

### Operational stubs (created early, no UI yet)
| Table | Columns | Indexes / FK | Migration |
|---|---|---|---|
| **workers** | id; branch_id; agency_id null; name_ar; name_en; nationality str(2) null; passport_number str(30) null; iqama_number str(20) null; ts; sd | unique passport_number, unique iqama_number; FK branch_id (restrict), agency_id (null) | 2026_11_10_134406 |
| **candidates** | id; agency_id (NOT NULL); name_ar; name_en; nationality str(2) null; passport_number str(30) null; status str(20) def `new`; ts; sd | idx status; FK agency_id (restrict). **No branch_id** | 2026_11_10_134408 |
| **trips** | id; branch_id; driver_id null; scheduled_at datetime; status str(20) def `scheduled`; ts; sd | idx status; FK branch_id (restrict), driver_id→users (null) | 2026_11_10_134411 |

### Model relationships
- User: belongsToMany Branch (`user_branches`), belongsTo Agency, belongsTo User `creator` (created_by), hasMany LoginAttempt, hasMany AuditLog, Spatie roles/permissions.
- Branch: belongsTo User `manager`, belongsToMany User, hasMany Worker / Trip (both without BranchScope). No `camps()` relation.
- Agency: belongsTo Country `originCountry` (country_id), hasMany User, hasMany Candidate.
- Country: hasMany CountryTravelStage (`travelStages`, alias `stages` for scoped binding), DocumentType, Agency, Authority, Price.
- CountryTravelStage: belongsTo Country.
- Authority: belongsTo Country; hasMany DocumentType, CompletedVisaListSetting, DocumentPrint.
- Camp: belongsTo Branch (via BelongsToBranch).
- Profession: hasMany DocumentType.
- Price: belongsTo Country. Lookup / Shift: none.
- DocumentType: belongsTo Country, Profession, Authority; hasMany Document.
- CompletedVisaListSetting: belongsTo Authority, Country.
- Document: morphTo documentable; belongsTo DocumentType `type` (withTrashed), User `uploader`; hasMany DocumentFile `files`; hasOne `latestFile`.
- DocumentFile: belongsTo Document, User `uploader`. DocumentPrint: morphTo documentable, belongsTo Authority (withTrashed), User `printer`.
- Candidate: belongsTo Agency; morphMany Document / DocumentPrint (HasDocuments). Implements `DocumentOwner`.
- Worker: belongsTo Branch only. Trip: belongsTo Branch only (no `driver()` relation).
- AuditLog: belongsTo User (withTrashed), morphTo target. LoginAttempt: belongsTo User.

### Consistency checks
- **Tables without a model:** framework tables, Spatie pivots and `user_branches` (pivot). These are expected.
- **Models without a table:** none.
- **$fillable vs columns:** every `#[Fillable]` column exists in its table. Columns not fillable on purpose: `users.two_factor_code`, `two_factor_expires_at`, `last_login_at`, `email_verified_at` (set with `forceFill`).
- Foreign keys with `restrictOnDelete` never fire, because those tables use soft deletes. Integrity relies on `isInUse()` checks (see §10).

---

## 4. Modules status

| # | Module | Status |
|---|---|---|
| 1 | Foundation | **DONE** |
| 2 | Settings + documents engine | **DONE** (hooks for later modules are deferred on purpose) |
| 3 | Workers, clients, status engine | **DONE** (commit a90b804; see the note under module 4 for the CV) |
| 4 | Recruitment | **DONE (core flow)**, uncommitted: orders → delivery → probation, CV, follow-up page. Deferred: interviews, correction requests, tickets, website requests |
| 5 | Monthly rental & finance | **PARTIAL** (uncommitted): monthly contracts (replace, early return, cancel), payments with receipts, refunds, rental reports done; invoices (Wafeq) and general financial reports not started |
| 6 | Camps (movements, daily count) | **DONE (core)**, uncommitted: movements log + daily roll-call |
| 7 | Hourly rental | **DONE (core)**, uncommitted: hourly bookings with overlap check and payments. Transport (driver trips) not linked yet |
| 8 | Transport & driver app | **DONE (core)**, uncommitted: drivers, trips (automatic + manual), driver portal |
| 9 | Returns, transfer, deportation (cases) | **DONE (core)**, uncommitted |
| 10 | Agency portal | **PARTIAL** |
| 11 | Public website & client portal | **NOT STARTED** |
| 12 | Alerts & messaging | **PARTIAL**, uncommitted: in-system alerts + bell; no e-mail / WhatsApp |
| 13 | Reports & dashboard | **PARTIAL** (dashboard UI with demo data) |
| 14 | Data migration from Excel | **NOT STARTED** |

**1. Foundation: DONE.** Users (`UserController`, CRUD, activate/deactivate, reset link, avatar, roles + branches + "all branches" via `UserAccessManager`, login history, change log), roles (`RoleController`, CRUD, clone, permission matrix, `is_system`, `requires_2fa`), permissions catalogue (`config/permissions.php`, `PermissionSeeder`, `RoleSeeder`), branches (`BranchController`, CRUD, export, activate/deactivate, last-active protection, in-use check), branch switcher + `BranchScope`, audit log (`AuditLogController` list/filter/show/export, `AuditLogger`, pruning). Extras: login with lockout and `login_attempts`, email 2FA (`TwoFactorController`), forced password change, password reset, session timeout, maintenance mode, backups (`BackupService`, `backup:run`, scheduled), profile page, language switch, error pages. Tests: Branch, User, Role, Login, Backup, Settings, UserPolicy, BranchScope, EnsureUserIsActive.

**2. Settings: DONE.**
- Countries: `ReferenceRegistry` `countries` + `CountryController@show` (tabs: travel stages, document types). Done.
- Travel stages per country: `TravelStageController` (CRUD, move, toggle, `max_days`). Done.
- Agencies, authorities, camps, professions, shifts, prices (one active per country + rental type), lookup lists (return_reason, cancel_reason, payment_method): `ReferenceController` + `ReferenceRegistry`. Done.
- Alert settings: Settings > Alerts tab, `Setting::ALERTS` (contract end, probation end, iqama, passport, medical expiry, hold duration, document max upload MB). Done (values only; nothing consumes them yet except `document_max_upload_mb`).
- Document types: `DocumentTypeController` (global list + per country, per profession, per authority, required, include in authority file, order). Done.
- Documents engine: `DocumentChecklistService` (ordered checklist, upload, versioning, extras, delete version), `<x-documents.checklist>`, `<x-documents.print-button>`, `AuthorityFilePrintService` (mPDF cover + documents + completed visas page; optional qpdf for PDF 1.5+), `CompletedVisasProvider` interface bound to `NullCompletedVisasProvider`, completed visa list settings. Done.
- Deferred or missing: only `candidate` is registered as a document owner (`config/documents.php`), so workers are not owners yet. The real `CompletedVisasProvider` waits for recruitment. `documents.authenticate` permission has no feature. The sidebar "Documents" item (Recruitment section) is "soon". The engine is exercised only by the local-only demo page `admin/dev/documents-demo`.

**3. Workers, clients, status engine: PARTIAL.**
- Exists: stub `workers` table/model (`Worker` with `BelongsToBranch`, `HasTranslations`; no controller, views or routes), `WorkerFactory`, masking service `SensitiveData` + `<x-sensitive>` with `workers.view_sensitive` / `clients.view_sensitive`, permissions `workers.*` and `clients.*`.
- Missing: workers screens/CRUD, worker fields (status, country_id, profession_id, dates, photo...), **clients table/model/screens**, `WorkerStatusService`, workflow/transitions config, status history table, profile tabs config, worker as document owner.

**4. Recruitment: DONE (core flow), 2026-11-12, not committed yet.**
- Orders (`RecruitmentOrderController`, `RecruitmentService`, number `RQ-YYYY-0001`, BranchScope). Statuses (`RecruitmentStatus`): new → candidate_selected → contracted (visa no. + Musaned contract, one visa per order) → in_travel (country stages) → arrived → delivered (probation) → completed / returned; cancel before the contract. The worker status follows through `WorkerStatusService`.
- New order screen: existing or walk-in client (`ClientRegistrar`) and a searchable worker selection. "Direct order" button in the candidates list and the worker profile.
- Arrival: the worker is housed in an active camp of the order's branch (capacity checked), or none.
- Delivery: office pickup or home delivery. Fees come from Settings > Recruitment (`Setting::RECRUITMENT`: home delivery fee 120, free camp days, daily camp fee, probation days) and can be changed or waived. Receiver name/phone, notes and `delivered_by` are recorded. The worker leaves the camp and goes delivered → in_probation. Handover report PDF (`RecruitmentHandoverPdf`, draft before delivery).
- Probation: complete (worker probation_completed, order completed) or return with a `return_reason` lookup (worker awaiting_decision, or absconded).
- Follow-up page `admin/recruitment/follow-up`: overdue stages, arrived workers waiting for delivery (with the camp fee so far), and probations ending within `days_before_probation_end`.
- Sidebar pages (`RecruitmentCandidateController`): "Ready candidates" `admin/recruitment/ready-candidates` (cards with photo, CV, documents progress, direct order) and "Documents" `admin/recruitment/documents` (required-document completeness of every worker from candidate_new to arrived, via `RecruitmentDocumentProgress`; links to the worker's documents tab to upload or print the authority file).
- Worker CV per profession template (`config/cv_templates.php`), filled by staff or by the agency portal, printed as a PDF. `RecruitmentCompletedVisas` feeds the authority file.
- Deferred: interviews, correction requests, tickets, website requests, invoices for delivery fees (module 5).

Original groundwork (before this module): stub `candidates` table/model (agency_id, names, nationality, passport, status string), `CandidateFactory`, Candidate as document owner, travel stages config, `hold_duration_hours` setting, `CompletedVisasProvider` interface, permissions `recruitment.*` and `candidates.*` (incl. `candidates.approve`). Missing: everything listed in the module (intake, review checklist, interviews, correction requests, approval, holds, recruitment requests, travel stage tracking, upcoming candidates list, completed visas, tickets, arrival, delivery, probation).

**5. Monthly rental & finance: PARTIAL (2026-11-12, uncommitted).**
- Monthly contracts (`RentalContractController`, `RentalContractService`, `rental_contracts`, number `MR-YYYY-0001`, BranchScope): one month per contract (end = start + 1 month − 1 day); renewal creates the next month's contract (`renewed_from_id`, old one "renewed"); close = worker returned ("ended" on/after the end date, "terminated" before it, reason from `return_reason` lookups), optional return housing. Eligible workers: available + company sponsorship + rental_type monthly, same branch as the client. Price = active monthly price of the worker's country (editable) − discount + VAT. Worker status: available ↔ rented_monthly through `WorkerStatusService`. Contract PDF (`rental.contract-pdf`). "Ending soon" filter uses `days_before_contract_end`.
- Payments (`PaymentController`, `PaymentService`, `payments` morph to contracts and hourly bookings): amount ≤ balance, method from `payment_method` lookups, transfer receipt on the private disk (served by `payments.receipt`), verify (`payments.verify`), remove only when unverified (`payments.delete`). Payment status (unpaid / partial / paid) is computed.
- Profile tab "Rental" on workers and clients (`admin.rental.tabs.history`).
- "Workers for rent" page (`RentalWorkerController`, `RentalWorkerService`): tabs monthly / hourly / not in rental; one-click add/remove of the worker's rental type (audited `worker.rental_type_changed`; blocked while rented or with upcoming bookings; hourly only for countries with an hourly price) and direct contract / booking buttons.
- Rental calendar (`RentalCalendarController`, FullCalendar 6 from `public/assets/libs`): hourly bookings and contract start/end days, JSON feed `admin.rental.calendar.events`, click a day to book.
- Rental reports (`RentalReportController`, `RentalReportService`, `reports.view`; amounts only with `reports.view_financial`): operations, billed / collected / verified, by payment method, collected per day (ApexCharts), outstanding balances, hourly and monthly worker utilisation.
- Departure / return with date and time: monthly in `rental_contract_workers` (one row per worker period), hourly in `hourly_bookings.departed_at/returned_at` (delay shown, no extra charge).
- Replacement: same contract, the current worker returns and another eligible worker goes out (`RentalContractService::replace`).
- Early return: used days charged (return before `config('rental.return_cutoff_hour')` = 17:00 does not count that day) → `final_total`; cancel → `final_total` 0. Refund requests (`refunds`, `RefundService`, `RefundController`, menu Finance > Refunds) approved / rejected with `refunds.approve`; approved refunds reduce the amount paid.
- Demo data: `php artisan db:seed --class=RecruitmentDemoSeeder` (recruitment, one order per step per branch) and `php artisan db:seed --class=RentalDemoSeeder` (local/testing only; skipped when rental data exists).
- Missing: invoices / Wafeq, refunds, financial reports. Note: the default `rental_team` role has no `payments.*`; `accounts` cannot delete payments.

Original groundwork: `prices` (monthly/hourly, VAT), `LookupType::PaymentMethod`, permissions `rental_contracts.*`, `payments.*` (+verify), `invoices.*` (+issue, retry), `refunds.*` (+approve), `reports.view_financial`. No Wafeq, WhatsApp, invoice or report code.

**6. Camps: DONE (core), 2026-11-12, uncommitted.** `CampController`, `CampService`. Every change of `workers.camp_id` is logged in `camp_movements` by the Worker model (`saved` event; note from `CampMovement::withNote()` or the status change reason), so arrivals, rentals, returns and manual moves are all recorded. Housing page (`admin/camps`): occupancy, workers inside, manual check-in / transfer / check-out (reason required; housed statuses only; capacity checked), daily roll-call (`camp_attendances`: present / absent / sick / outside, one row per worker and day), movements log (`admin/camps/movements`), worker profile tab "Housing". Permissions: `camps.view`, `camp_movements.view`, `camp_movements.create`.

**7. Hourly rental: DONE (core), 2026-11-12, uncommitted.** `HourlyBookingController`, `HourlyBookingService`, `hourly_bookings` (number `HB-YYYY-0001`): one worker + date + shift; starts/ends copied from the shift; a worker is never booked at overlapping times; several consecutive days in one step (all or nothing). Only workers that are available, rental_type hourly, and from a country with an active hourly price (today only the Philippines, per the business) can be booked. Price = hourly price × shift hours − discount + VAT. Complete (after start) / cancel (cancel reason). The worker status does not change. Payments as in module 5. `Shift::isInUse()` checks bookings. Missing: driver trips for the shift's move time.

**8. Transport & driver app: DONE (core), 2026-11-12, uncommitted.** `TripService`, `TripController` (`admin/trips`, day board, assign driver, start / complete / cancel, manual trips), `DriverController` (`admin/drivers`: creates a user of type driver + `driver_profiles` with vehicle and licence; must change password at first login). Automatic trips: hourly booking → drop-off at the shift's move time and return at the end (cancelled with the booking); recruitment home delivery → delivery trip. Driver portal (`/driver/trips`): own open trips with "on my way" / "done". Missing: GPS, trip costs, rental monthly drop-offs.

**9. Cases: DONE (core), 2026-11-12, uncommitted.** `WorkerCaseService`, `WorkerCaseController` (`admin/cases`), `worker_cases` (CS-YYYY-0001) + `worker_case_notes`. A case opens automatically when a worker becomes `awaiting_decision` (`App\Listeners\OpenWorkerCase` on `WorkerStatusChanged`). Decision (`cases.decide`, e.g. general manager): sponsorship transfer (new sponsor client or name + ID, fee) → for_transfer → transferred; deportation (flight date, ticket cost) → for_deportation → deported; back to work → available (closed at once). Completing and follow-up notes need `cases.edit` (e.g. branch manager).

**10. Agency portal: PARTIAL.** `external_agency` user type linked to `users.agency_id`; `GET /agency/candidates` (own candidates list) and `GET /agency/documents` (documents of own candidates, read-only list, no file links), `portal` layout, `AgencyPortalControllerTest`. Missing: candidate submission, uploads, correction responses and every write action.

**11. Public website & client portal: NOT STARTED.** Only the `website_requests.*` permissions and a "soon" sidebar item.

**12. Alerts & messaging: PARTIAL, 2026-11-12, uncommitted.** In-system alerts only (no e-mail / WhatsApp, as decided): `AlertService`, `alerts` table, `php artisan alerts:refresh` scheduled hourly, page `admin/alerts` and the top-bar bell. Types: iqama / passport / medical expiry, contract end, probation end, overdue travel stage, hold time over, absent from housing, trip without driver, driver licence expiry, case waiting > 3 days. Periods from Settings > Alerts. One alert per subject and date; closes by itself when the cause is gone, or "handled" (`alerts.edit`). Each type is shown only to users with the subject's view permission.

**13. Reports & dashboard: PARTIAL.** `DashboardController` + `dashboard.blade.php` (KPIs, monthly overview, nationalities, pipeline, alerts, branches, latest contracts). Every figure comes from `App\Support\DashboardDemoData` (fixed sample data); only the branch list is real. No reports.

**14. Data migration from Excel: NOT STARTED.** `spatie/simple-excel` is installed and used only for exports.

---

## 5. Routes and screens

All admin routes have the prefix `/admin`, the name prefix `admin.` and the middleware `panel, user.type:internal`. `panel` = `auth, active, session.timeout, two-factor, password.changed, CheckMaintenanceMode`.

### Public / auth
| Method | URI | Name | Controller@method | Middleware | Permission |
|---|---|---|---|---|---|
| GET | `/` | – | Closure (redirect by user type) | – | – |
| GET | `/lang/{locale}` | lang.switch | LanguageController | – | – |
| GET/POST | `/login` | login / login.store | Auth\LoginController@create/store | guest | – |
| POST | `/logout` | logout | Auth\LoginController@destroy | auth, active | – |
| GET/POST | `/forgot-password` | password.request / password.email | Auth\PasswordResetController@create/store | guest (+throttle:5,1 on POST) | – |
| GET | `/reset-password/{token}` | password.reset | PasswordResetController@edit | guest | – |
| POST | `/reset-password` | password.update | PasswordResetController@update | guest | – |
| GET/POST | `/two-factor` | two-factor.show / two-factor.verify | Auth\TwoFactorController@show/store | auth, active (+throttle:two-factor) | – |
| POST | `/two-factor/resend` | two-factor.resend | TwoFactorController@resend | auth, active, throttle:3,1 | – |
| GET/PUT | `/change-password` | password.force / password.force.update | Auth\ForcePasswordController@edit/update | auth, active, two-factor | – |
| GET | `/up` | – | health | – | – |

### Foundation (admin)
| Method | URI | Name | Controller@method | Permission |
|---|---|---|---|---|
| GET | `/admin` | dashboard | DashboardController | dashboard.view |
| POST | `/admin/branch` | branch.switch | BranchSwitchController | – (validated against allowed branches) |
| GET | `/admin/profile` | profile.edit | ProfileController@edit | – |
| PUT | `/admin/profile/password`, `/language`, `/two-factor` | profile.password / profile.language / profile.two-factor | ProfileController@updatePassword/updateLanguage/updateTwoFactor | – |
| GET | `/admin/branches` | branches.index | BranchController@index | viewAny,Branch → branches.view |
| GET | `/admin/branches/export` | branches.export | BranchController@export | export,Branch → branches.export |
| GET/POST | `/admin/branches/create`, `/admin/branches` | branches.create / branches.store | BranchController@create/store | create,Branch → branches.create |
| GET | `/admin/branches/{branch}` | branches.show | BranchController@show | view,branch → branches.view + in allowed branches |
| GET/PUT | `/admin/branches/{branch}/edit`, `/admin/branches/{branch}` | branches.edit / branches.update | BranchController@edit/update | update,branch → branches.edit |
| PATCH | `/admin/branches/{branch}/activate`, `/deactivate` | branches.activate / branches.deactivate | BranchController@activate/deactivate | update,branch |
| DELETE | `/admin/branches/{branch}` | branches.destroy | BranchController@destroy | delete,branch → branches.delete |
| GET | `/admin/users` | users.index | UserController@index | viewAny,User → users.view |
| GET/POST | `/admin/users/create`, `/admin/users` | users.create / users.store | UserController@create/store | create,User → users.create |
| GET | `/admin/users/{user}` | users.show | UserController@show | view,user → users.view |
| GET/PUT | `/admin/users/{user}/edit`, `/admin/users/{user}` | users.edit / users.update | UserController@edit/update | update,user → users.edit |
| DELETE | `/admin/users/{user}` | users.destroy | UserController@destroy | delete,user → users.delete (guarded) |
| PATCH | `/admin/users/{user}/activate` | users.activate | UserController@activate | activate,user |
| PATCH | `/admin/users/{user}/deactivate` | users.deactivate | UserController@deactivate | deactivate,user (guarded) |
| POST | `/admin/users/{user}/reset-password` | users.reset-password | UserController@resetPassword | resetPassword,user |
| GET | `/admin/roles` | roles.index | RoleController@index | viewAny,Role → roles.view |
| GET/POST | `/admin/roles/create`, `/admin/roles` | roles.create / roles.store | RoleController@create/store | create,Role → roles.create |
| GET/PUT | `/admin/roles/{role}/edit`, `/admin/roles/{role}` | roles.edit / roles.update | RoleController@edit/update | update,role → roles.edit |
| DELETE | `/admin/roles/{role}` | roles.destroy | RoleController@destroy | delete,role → roles.delete, not is_system |
| POST | `/admin/roles/{role}/clone` | roles.clone | RoleController@clone | clone,role → roles.create |
| GET | `/admin/roles/{role}/permissions` | roles.permissions.edit | RoleController@editPermissions | view,role → roles.view |
| PUT | `/admin/roles/{role}/permissions` | roles.permissions.update | RoleController@updatePermissions | updatePermissions,role → roles.edit, not super_admin |
| GET | `/admin/audit-logs` | audit-logs.index | AuditLogController@index | viewAny,AuditLog → audit_log.view |
| GET | `/admin/audit-logs/export` | audit-logs.export | AuditLogController@export | export,AuditLog → audit_log.export |
| GET | `/admin/audit-logs/{auditLog}` | audit-logs.show | AuditLogController@show | view,auditLog → audit_log.view |

### Settings (admin)
| Method | URI | Name | Controller@method | Permission |
|---|---|---|---|---|
| GET | `/admin/settings` | settings.edit | SettingController@edit | settings.view |
| PUT | `/admin/settings/general` | settings.general | SettingController@updateGeneral | settings.edit |
| PUT/DELETE | `/admin/settings/appearance` | settings.appearance / settings.appearance.reset | SettingController@updateAppearance/resetAppearance | settings.edit |
| PUT | `/admin/settings/mail` | settings.mail | SettingController@updateMail | settings.edit |
| POST | `/admin/settings/mail/test` | settings.mail.test | SettingController@sendTestMail | settings.edit (+throttle:5,1) |
| PUT | `/admin/settings/security` | settings.security | SettingController@updateSecurity | settings.edit |
| PUT | `/admin/settings/maintenance` | settings.maintenance | SettingController@updateMaintenance | settings.edit |
| PUT | `/admin/settings/alerts` | settings.alerts | SettingController@updateAlerts | **settings.manage** |
| POST | `/admin/settings/backups` | settings.backups.store | BackupController@store | settings.edit (+throttle:3,1) |
| GET/DELETE | `/admin/settings/backups/{backup}` | settings.backups.download / destroy | BackupController@download/destroy | settings.edit |
| GET | `/admin/settings/countries/{country}` | countries.show | CountryController@show | settings.view |
| GET/POST/PUT/PATCH/DELETE | `/admin/settings/countries/{country}/stages[/create, /{stage}, /{stage}/edit, /{stage}/move, /{stage}/toggle]` | countries.stages.create/store/edit/update/move/toggle/destroy | TravelStageController@… (scoped bindings) | settings.manage |
| GET | `/admin/settings/document-types` | document-types.index | DocumentTypeController@index | settings.view |
| GET/POST/PUT/PATCH/DELETE | `/admin/settings/document-types[/create, /{documentType}, /edit, /move, /toggle]` | document-types.create/store/edit/update/move/toggle/destroy | DocumentTypeController@… | document_types.manage |

**Reference lists**, generated from `ReferenceRegistry` (`routes/web.php` foreach). For each `{key}` the URI is `/admin/settings/{key-with-dashes}` and the names are `admin.reference.{key}.{index,create,store,edit,update,activate,deactivate,destroy[,move]}` on `ReferenceController@{same}`:

| Resource key | view | create / edit (incl. activate, deactivate, move) | delete | Sortable (move route) |
|---|---|---|---|---|
| countries | settings.view | settings.manage | settings.manage | yes |
| agencies | agencies.view | agencies.create / agencies.edit | agencies.delete | no |
| authorities | authorities.view | authorities.create / authorities.edit | authorities.delete | no |
| camps | camps.view | camps.create / camps.edit | camps.delete | no |
| professions | settings.view | settings.manage | settings.manage | yes |
| shifts | settings.view | settings.manage | settings.manage | yes |
| prices | settings.view | settings.manage | settings.manage | no |
| lookups | settings.view | settings.manage | settings.manage | yes |
| completed_visa_lists | settings.view | document_types.manage | document_types.manage | no |

### Documents engine (admin)
| Method | URI | Name | Controller@method | Permission |
|---|---|---|---|---|
| POST | `/admin/documents/{ownerType}/{ownerId}/types/{documentType}` | documents.upload | DocumentController@upload | documents.upload (+documents.replace if exists) + owner permission |
| POST | `/admin/documents/{ownerType}/{ownerId}/extra` | documents.extra | DocumentController@extra | documents.upload + owner permission |
| POST | `/admin/documents/{ownerType}/{ownerId}/authorities/{authority}/print` | documents.print | DocumentController@print | documents.print_authority_file + owner permission |
| GET | `/admin/documents/files/{documentFile}` | documents.files.show | DocumentController@show | documents.view + owner permission |
| DELETE | `/admin/documents/files/{documentFile}` | documents.files.destroy | DocumentController@destroyVersion | documents.delete_version + owner permission |
| GET | `/admin/documents/prints/{documentPrint}` | documents.prints.download | DocumentController@downloadStoredPrint | documents.print_authority_file + owner permission |
| GET | `/admin/dev/documents-demo` (registered only when `APP_ENV=local`) | documents.demo | DocumentsDemoController | documents.view |

### Portals
| Method | URI | Name | Controller@method | Middleware | Permission |
|---|---|---|---|---|---|
| GET | `/agency/candidates` | agency.candidates.index | Portal\AgencyPortalController@candidates | panel, user.type:external_agency | – (filtered by users.agency_id) |
| GET | `/agency/documents` | agency.documents.index | Portal\AgencyPortalController@documents | panel, user.type:external_agency | – |
| GET | `/driver/trips` | driver.trips.index | Portal\DriverPortalController@trips | panel, user.type:driver | – (filtered by driver_id) |

Every admin route has a permission or policy check, except profile, branch switch and logout, which apply only to the user's own data. Portal routes rely on `user.type` plus ownership filters.

### Sidebar (`config/menu.php`)
| Section | Item | Target | State |
|---|---|---|---|
| Main | Dashboard | admin.dashboard | Working (demo figures) |
| Recruitment | Recruitment requests, Candidates, Documents, Website requests | – | "Soon" |
| Rental | Clients, Monthly rental contracts, Hourly bookings | – | "Soon" |
| Operations | Workers, Camp movements, Drivers, Trips, Cases, Alerts | – | "Soon" |
| Finance | Payments, Invoices, Refunds, Reports | – | "Soon" |
| Administration | Branches, Users, Roles, Audit log | admin.branches/users/roles/audit-logs.index | Working |
| Administration > Settings | System settings, Alert settings (`?tab=alerts`), Countries, Document types, Completed visa lists, Agencies, Authorities, Camps, Professions, Shifts, Prices, Lookup lists | settings / reference routes | Working |

That makes 17 "soon" items and 17 working items.

---

## 6. Permissions

**Defined:** 153 permissions in `config/permissions.php` (sections → modules → actions; standard actions `view, create, edit, delete, export, print`; overrides for `documents`, `document_types`, `reports`, `dashboard`, `audit_log`, `settings`; 11 specials). Labels come from `lang/{ar,en}/permissions.php` and are copied to `label_ar/en` by `PermissionSeeder`.

| Section | Modules (actions) |
|---|---|
| recruitment | recruitment (std), candidates (std + approve), documents (view, upload, replace, delete_version, print_authority_file, authenticate), document_types (manage), agencies (std), authorities (std), website_requests (std) |
| rental | clients (std + view_sensitive), rental_contracts (std), hourly_bookings (std) |
| operations | workers (std + view_sensitive), camps (std), camp_movements (std), drivers (std), trips (std), cases (std + decide), alerts (std) |
| finance | payments (std + verify), invoices (std + issue, retry), refunds (std + approve), reports (view, export, print, view_financial) |
| administration | dashboard (view), branches (std + all), users (std), roles (std), audit_log (view, export, print), settings (view, edit, manage) |

**Roles (seeded by `RoleSeeder` from `config/permissions.php` `roles`). Defaults apply only when a role is first created; super_admin is re-synced on every run.**

| Role | is_system | Patterns | Count |
|---|---|---|---|
| super_admin | yes | `*` (+ Gate::before bypass) | 153 |
| general_manager | no | `*.view, *.export, *.print, branches.all, candidates.approve, refunds.approve, cases.decide, payments.verify, workers/clients.view_sensitive, reports.view_financial` except `settings.*` | 79 |
| branch_manager | no | dashboard, reports view/export/print, alerts.*, workers.*, clients.*, camps.*, camp_movements.*, trips.*, drivers.view, rental_contracts.*, hourly_bookings.*, cases.*, payments/invoices/refunds.view, candidates.view, documents.view except `*.delete, cases.decide` | 57 |
| recruitment_team | no | dashboard, recruitment.*, candidates.*, documents.*, agencies/authorities/workers.view except `candidates.approve, *.delete` | 20 |
| marketing_team | no | dashboard, candidates.view, website_requests.* | 8 |
| rental_team | no | dashboard, rental_contracts.*, hourly_bookings.*, clients view/create/edit, workers.view except `*.delete` | 15 |
| accounts | no | dashboard, payments.*, invoices.*, refunds view/create, clients.view except `*.delete` | 17 |
| finance_manager | no | dashboard, payments.*, invoices.*, refunds.*, reports.*, clients.view | 28 |
| camp_team | no | dashboard, camps.*, camp_movements.*, cases.view, workers.view except `*.delete` | 13 |
| transport_coordinator | no | dashboard, trips.*, drivers.* except `*.delete` | 11 |
| driver | yes | none | 0 |
| external_agency | yes | none | 0 |

Only super_admin holds `settings.edit`, `settings.manage`, `document_types.manage`, `users.*`, `roles.*`, `branches.create/edit/delete` and `audit_log.*`. general_manager gets view/export only.

**Used in code but not defined:** none. Every `can:`, `@can`, `->can()` and `checkPermissionTo()` string matches a defined permission. Policy abilities (`viewAny`, `assignRoles`, …) map to defined permissions.

**Defined but never referenced in code.** Most belong to modules that are not built yet; the ones that belong to built modules are in bold:
- **branches.print, users.export, users.print, roles.export, roles.print, audit_log.print, agencies.export, agencies.print, authorities.export, authorities.print, camps.export, camps.print, documents.authenticate**
- recruitment.create/edit/delete/export/print; candidates.create/edit/delete/export/print/approve; website_requests.create/edit/delete/export/print; clients.create/edit/delete/export/print/view_sensitive; rental_contracts.*, hourly_bookings.* (except view); workers.create/edit/delete/export/print; camp_movements.*, drivers.*, trips.*, cases.*, alerts.* (except view); payments.*, invoices.*, refunds.* (except view, plus verify/issue/retry/approve); cases.decide; reports.export/print.

---

## 7. Services, events, jobs, scheduled commands

| Class | Purpose | Used by |
|---|---|---|
| `App\Services\AuditLogger` | Writes `audit_logs` rows (translation key + params, old/new, IP, UA) | Every controller that changes data, `UserAccessManager`, `DocumentChecklistService`, `AuthorityFilePrintService`, `BackupRun`, `SessionTimeout`, `LoginController` |
| `App\Services\UserAccessManager` | Sync a user's roles, branches and `branches.all`, with last-Super-Admin protection and audit | `UserController::syncAccess` |
| `App\Services\SuperAdminGuard` | `isLastActiveSuperAdmin()` | `UserPolicy`, `UserAccessManager` |
| `App\Services\SiteSettings` (singleton, shared to views as `$site`) | Branding, appearance (body attrs + theme CSS), mail config (encrypted password), maintenance flag/message | `SettingController`, layouts, `CheckMaintenanceMode`, `AppServiceProvider::boot` (`applyMailConfig()`) |
| `App\Services\BackupService` | Zip DB + uploaded files to `local:backups/`, list, prune, validate names | `BackupController`, `BackupRun`, `SettingController@edit` |
| `App\Services\SensitiveData` | Mask passport/iqama/national id/address without `*.view_sensitive` | `<x-sensitive>` (not used in any page yet) |
| `App\Services\Documents\DocumentChecklistService` | Checklist for an owner, upload/replace (versioned), extras, delete version, max upload size | `DocumentController`, `<x-documents.checklist>`, `AuthorityFilePrintService` |
| `App\Services\Documents\AuthorityFilePrintService` | Build the merged authority PDF (mPDF), store a `DocumentPrint`, audit `document.printed` | `DocumentController@print`, `<x-documents.print-button>` |
| `App\Services\Documents\CompletedVisasProvider` (interface) → `NullCompletedVisasProvider` | Latest completed visas page in the authority file (returns empty until recruitment exists) | bound in `AppServiceProvider::register` |
| `DocumentOwner` (interface), `ChecklistItem`, `AuthorityFileResult`, `AuthorityFileException` | Engine value objects / contract | documents engine |
| `App\Support\PermissionRegistry` | Read/expand the permission catalogue | seeders, role matrix, labels |
| `App\Support\Reference\ReferenceRegistry` / `ReferenceResource` | Definitions of reference lists (fields, rules, permissions, filters) | `routes/web.php`, `ReferenceController`, views |
| `App\Support\DashboardDemoData` | Fixed sample dashboard figures | `DashboardController` |

**Notifications:** `TwoFactorCode` (mail, **not queued**), `TestMail` (sent with `notifyNow`). Password reset uses the framework notification (`lang/ar.json` strings).

**Events / listeners:** none defined by the app. The only app dispatch is `event(new PasswordReset($user))` in `PasswordResetController.php:64`, which has no app listener. `event:list` shows only framework/package listeners.

**Jobs:** none. **Observers:** none.

**Console commands / schedule** (`routes/console.php`):
| Command | Schedule | Purpose |
|---|---|---|
| `backup:run {--scheduled}` (`App\Console\Commands\BackupRun`) | hourly, withoutOverlapping. Runs only when `backup_auto_enabled` and `now()->hour == backup_hour` and no auto backup today | DB + files backup, prune to `backup_keep` |
| `model:prune` | daily 03:30 | Prunes `AuditLog` (`audit_retention_days`, 0 = keep) and `LoginAttempt` (`login_attempts_retention_days`) |
| `inspire` | – | Laravel boilerplate |

---

## 8. Settings

`GET /admin/settings` (`admin/settings/edit.blade.php`) has these tabs. Values are stored in the `settings` table (key/value).

| Tab | Permission to save | Keys |
|---|---|---|
| General | settings.edit | `site_name_ar`, `site_name_en`, images `logo_dark`, `logo_light`, `logo_sm`, `favicon` (public disk `branding/`) |
| Appearance | settings.edit | `primary_color`, `theme_mode`, `sidebar_color`, `topbar_color`, `sidebar_size`, `layout_width`, `button_shape` |
| Mail | settings.edit | `mail_mailer` (smtp/log), `mail_host`, `mail_port`, `mail_encryption` (none/tls/ssl), `mail_username`, `mail_password` (encrypted), `mail_from_address`, `mail_from_name`; test mail button |
| Security | settings.edit | `session_timeout_minutes` (120), `login_max_attempts` (5), `login_lockout_minutes` (15), `two_factor_code_minutes` (10) |
| Alerts & documents | **settings.manage** | `days_before_contract_end` (7), `days_before_probation_end` (3), `days_before_iqama_expiry` (30), `days_before_passport_expiry` (180), `days_before_medical_expiry` (30), `hold_duration_hours` (48), `document_max_upload_mb` (10) |
| Backup & maintenance | settings.edit | `maintenance_enabled`, `maintenance_message_ar/en`, `backup_auto_enabled`, `backup_hour` (2), `backup_keep` (7), `audit_retention_days` (0), `login_attempts_retention_days` (90); backup list/create/download/delete |

Other settings screens are tables, not keys:

| Screen | Table(s) | Route |
|---|---|---|
| Countries (+ country page: travel stages, document types tabs) | countries, country_travel_stages, document_types | `admin.reference.countries.*`, `admin.countries.show`, `admin.countries.stages.*` |
| Document types | document_types | `admin.document-types.*` |
| Completed visa lists | completed_visa_list_settings | `admin.reference.completed_visa_lists.*` |
| Agencies / Authorities / Camps / Professions / Shifts / Prices / Lookup lists | agencies / authorities / camps / professions / shifts / prices / lookups | `admin.reference.{key}.*` |
| Roles & permission matrix | roles, role_has_permissions | `admin.roles.*` |
| Branches | branches | `admin.branches.*` |

Seeded defaults: `SettingSeeder` (all `SECURITY + MAINTENANCE + ALERTS` keys), `BranchSeeder` (RYD Riyadh, QSM Qassim), `CountrySeeder` (PH, ID, KE, UG, ET, LK + 7 default travel stages each), `ProfessionSeeder` (housemaid, private driver, nanny), `LookupSeeder` (return/cancel reasons, payment methods), `PermissionSeeder`, `RoleSeeder`, `SuperAdminSeeder` (from `SUPER_ADMIN_*` env).

---

## 9. Tests

Run on 2026-11-12 with `vendor/bin/phpunit` (SQLite in memory):

**110 tests, 110 passed, 0 failed, 405 assertions** (about 101 s).

| File | Tests |
|---|---|
| Feature/ExampleTest (home redirects guests) | 1 |
| Feature/Http/Controllers/Admin: Backup 7, Branch 11, Dashboard 3, Document 8, DocumentType 6, Reference 8, Role 8, Setting 12, User 9 | 72 |
| Feature/Http/Controllers/Auth/LoginControllerTest | 7 |
| Feature/Http/Controllers/Portal/AgencyPortalControllerTest | 4 |
| Feature/Http/Middleware/EnsureUserIsActiveTest | 2 |
| Feature/Models/Scopes/BranchScopeTest | 5 |
| Feature/Policies/UserPolicyTest | 5 |
| Feature/Services/Documents: AuthorityFilePrintService 4, DocumentChecklistService 6 | 10 |
| Feature/Services/SensitiveDataTest | 3 |
| Unit/ExampleTest (boilerplate) | 1 |

`migrate:status`: all 28 migrations ran (batch 1: 15, batch 2: 1, batch 3: 12). Nothing is pending.

Not covered by tests: TwoFactorController, PasswordResetController, ForcePasswordController, ProfileController, TravelStageController, CountryController, AuditLogController, DriverPortalController, BranchSwitchController, SessionTimeout, CheckMaintenanceMode, BackupRun schedule logic, the seeders.

---

## 10. Conflicts and problems found

Severity: **HIGH** = will block or break the next module · **MEDIUM** = wrong behaviour or inconsistency to fix soon · **LOW** = cleanup.

Summary: **3 HIGH, 10 MEDIUM, 17 LOW.**

### HIGH
| # | Problem | Evidence |
|---|---|---|
| H1 | **The `workers` and `candidates` tables are early placeholders that do not match modules 3–4.** `workers` has no status, country_id, profession_id, client/contract or date fields. `candidates` has no branch, country_id or profession and has a free-text `status`. Both use a 2-letter `nationality` string. Building modules 3–4 needs alter migrations and a decision on how a candidate becomes a worker (see Q1). `Candidate::documentProfessionId()` always returns null, so profession-specific document types never apply to candidates. | `database/migrations/2026_11_10_134406_create_workers_table.php`, `2026_11_10_134408_create_candidates_table.php`, `app/Models/Worker.php`, `app/Models/Candidate.php:34` |
| H2 | **Two ways to reference a country.** Workers, candidates and `agencies.country` use a 2-letter code; agencies (country_id), authorities, prices, document_types and completed visa lists use the `country_id` FK. Agency keeps both and syncs them in a `saving` hook. The documents engine converts code → id for candidates. A country whose `code` is null (allowed: `countries.code` is nullable) can never match a worker or candidate. | `app/Models/Agency.php:19,30`, `app/Models/Candidate.php:24`, `countries.code` nullable in `2026_11_11_162448` |
| H3 | **No status engine.** `WorkerStatusService`, workflow/transition config, status history and profile-tabs config do not exist. Statuses are raw strings with DB defaults (`candidates.status` = `new`, `trips.status` = `scheduled`) and are written directly (`DocumentsDemoController.php:36` sets `'status' => 'new'`). This must exist before modules 3/4 write any status. | `config/` (no workflow / profile_tabs files), `app/Services` |

### MEDIUM
| # | Problem | Evidence |
|---|---|---|
| M1 | **Four permissions guard the "Settings" area.** `settings.edit` covers the system tabs and backups; `settings.manage` covers the alerts tab, countries, professions, shifts, prices, lookups and travel stages; `document_types.manage` covers document types and completed visa lists; agencies, authorities and camps use their module permissions. The alerts tab is the only settings tab that uses `settings.manage`. The meaning of `settings.edit` vs `settings.manage` is not documented. | `routes/web.php` (settings group, `settings.alerts`), `app/Http/Requests/Admin/UpdateAlertSettingsRequest.php:15`, `app/Support/Reference/ReferenceRegistry.php:53,172`, `resources/views/admin/settings/edit.blade.php:11,23` |
| M2 | **Placeholder "in use" checks will allow deleting records once modules reference them.** `Camp::isInUse()`, `Shift::isInUse()` and `Lookup::isInUse()` return `false`. `Profession::isInUse()` checks only document types. `Country::isInUse()` does not check workers/candidates (they store codes, see H2). FKs are `restrictOnDelete`, but deletes are soft, so the DB never blocks them. | `app/Models/Camp.php:41`, `Shift.php:36`, `Lookup.php:55`, `Profession.php:29`, `Country.php:76` |
| M3 | **A branch with camps can be deleted.** The branch destroy check counts users, workers and trips but not camps, so camps keep pointing to a soft-deleted branch. `Branch` has no `camps()` relation. | `app/Http/Controllers/Admin/BranchController.php` `destroy()` |
| M4 | **Hard deletes exist.** Deleting a document version removes the file and the `document_files` row. `documents`, `document_files` and `document_prints` have no soft deletes. Roles are hard-deleted (Spatie). Backups are deleted from disk. Audit logs are pruned when retention > 0. | `app/Services/Documents/DocumentChecklistService.php:133-134`, `RoleController.php:94`, migration `2026_11_11_162505` |
| M5 | **Candidates are not branch-scoped**, but the documents engine relies on owner scopes for isolation (`config/documents.php` comment: "an owner outside the user's branches is a 404"). Any user with `candidates.view` + `documents.view` can open any candidate's files. | `app/Models/Candidate.php` (no BelongsToBranch), `config/documents.php:12-16` |
| M6 | **The dashboard shows fixed fake figures** (KPIs, revenue, contracts with real-looking Arabic/English names) to every user, including production users. Only the branch list is real. | `app/Support/DashboardDemoData.php`, `DashboardController.php` |
| M7 | **The Arabic validation file is incomplete.** About 75 rules are missing (e.g. `gt.numeric`, used by Shifts `hours`; `decimal`, `starts_with`, `required_without`, `uuid`…), so the Arabic UI shows English validation messages. `values.mail_mailer.smtp` exists only in ar. | `lang/ar/validation.php` vs `lang/en/validation.php`, `ReferenceRegistry.php:138` |
| M8 | **Timezone is UTC** for a Saudi business. `backup_hour` is compared with `now()->hour` in UTC (backup at "2" runs at 05:00 Riyadh), `model:prune` runs at 03:30 UTC, and every displayed time is UTC. | `config/app.php:68`, `app/Console/Commands/BackupRun.php` `isDue()` |
| M9 | **Mail is sent synchronously.** `TwoFactorCode` is not `ShouldQueue`, and password reset and test mail are sent inline. No job pattern exists for external integrations yet (Wafeq/WhatsApp planned), and no queue worker is part of the run instructions. | `app/Notifications/TwoFactorCode.php`, `app/Http/Controllers/Admin/SettingController.php` `sendTestMail` |
| M10 | **The authority file PDF is built synchronously** in the HTTP request (mPDF + optional qpdf conversion), so large document sets may time out. | `app/Services/Documents/AuthorityFilePrintService.php:68-138` |

### LOW
| # | Problem | Evidence |
|---|---|---|
| L1 | Legacy migration creates a `documents` table that a later migration drops and recreates. Rolling back the later one restores the legacy shape. | `2026_11_10_134409_create_documents_table.php`, `2026_11_11_162505_replace_documents_table_with_documents_engine.php` |
| L2 | A fresh `migrate --seed` leaves every `sort_order` = 0 (countries, stages, lookups, professions), because `DatabaseSeeder` uses `WithoutModelEvents`, which disables the `Sortable` creating hook and Agency's `saving` hook. Verified on an in-memory DB. Display still works (ties ordered by id) and `move()` renormalises. The dev DB has correct values. | `database/seeders/DatabaseSeeder.php`, `app/Models/Concerns/Sortable.php` |
| L3 | User admin is not branch-aware: the users list shows every user, and the branch picker offers every branch regardless of the actor's allowed branches. Today only super_admin has `users.edit`. | `app/Http/Controllers/Admin/UserController.php` `index()`, `formOptions()` |
| L4 | Defined permissions for built screens are never used: `branches.print`, `users.export/print`, `roles.export/print`, `audit_log.print`, `agencies/authorities/camps.export/print`, `documents.authenticate`. | §6 |
| L5 | `audit.targets` has no labels for `DocumentFile`, `DocumentPrint`, `Setting`, `Worker`; the raw key will show once these are logged as targets. | `lang/{ar,en}/audit.php` `targets` |
| L6 | The agency portal shows passport numbers unmasked (no `<x-sensitive>`). `<x-sensitive>` is not used in any page yet. | `resources/views/portal/agency/candidates.blade.php:27` |
| L7 | The documents demo page creates an agency and a candidate on a GET request (local only). The dev DB contains that demo candidate and 3 documents. | `app/Http/Controllers/Admin/DocumentsDemoController.php:30-41` |
| L17 | A new user's avatar is stored before the transaction commits; if the transaction fails, the file stays on disk. | `UserController.php:82` |
| L8 | Hard-coded Arabic strings in PHP outside lang: demo controller names and dashboard demo data. Seeders and factories also contain them, which is acceptable. No hard-coded UI text was found in Blade apart from `welcome.blade.php`. | `DocumentsDemoController.php:32,36`, `DashboardDemoData.php:145-150` |
| L9 | Dead or boilerplate files: `resources/views/welcome.blade.php` (Laravel default page, English, not routed), `tests/Unit/ExampleTest.php`, the `inspire` command in `routes/console.php`, the commented import `app/Models/User.php:5`, and the `_template/` folder (24 MB raw HTML template committed to git). | |
| L10 | "One active price per country + rental type" is enforced in the app only (no partial unique index). | `ReferenceRegistry::singleActivePriceRule()`, prices migration |
| L11 | Replacing a user avatar does not delete the old file. | `UserController::update` |
| L12 | Portal routes have no permission checks (only `user.type`), and the `driver` / `external_agency` roles have zero permissions, so roles cannot restrict portal features. | `routes/web.php` portal groups, `config/permissions.php` roles |
| L13 | `Trip` has no `driver()` relation and `Worker` no `agency()` relation, although the columns exist. | `app/Models/Trip.php`, `Worker.php` |
| L14 | Before this audit, `CLAUDE.md` and `AGENTS.md` were identical Boost-generated files. Boost's rules point to `.ai/rules/`, which does not exist. Running `boost:update` may rewrite the `<laravel-boost-guidelines>` block in CLAUDE.md. | `boost.json`, `AGENTS.md` |
| L15 | Tailwind/Vite are installed but unused. The UI uses static Bootstrap template assets. A new screen built with Tailwind would conflict with the template. | `package.json`, `vite.config.js`, `layouts/partials/head-css.blade.php` |
| L16 | `QPDF_BINARY` is read in config but is missing from `.env.example`. | `config/documents.php` |

Checked and found consistent: naming of the worker concept (`worker`/`workers` everywhere; no "employee" or "labor" models), routes all protected, every used permission defined, no translation key missing in ar/en apart from the validation rules (M7) and the audit targets (L5), and `$fillable` matches the tables.

---

## 11. Where we stopped

- **Last completed module:** 2, Settings (reference data, alert settings, document types, documents engine). Commit `63099cf`.
- **Module in progress:** none. Module 3 (workers, clients, status engine) has not started apart from the stub `workers` table and the masking service.
- **Exact next step:** answer the open questions Q1–Q6 below, then start Module 3, step 1: design and migrate the real **workers** schema (alter the stub table: status, `country_id` vs nationality code, profession, branch, agency), together with `config/workflow.php` (statuses + allowed transitions), a `worker_status_histories` table and `WorkerStatusService`. Register Worker as a document owner in `config/documents.php`. Clients and profile tabs come after that.

---

## 12. Open questions

1. **Candidate vs worker:** is a candidate converted into a new `workers` row on arrival, or is it one record that moves through statuses? This decides the documents owner, the status engine and the agency portal.
2. **Country reference:** should workers/candidates switch to `country_id` (and should `agencies.country` be dropped)? Can `countries.code` stay nullable?
3. **Branch ownership of candidates:** is a candidate or recruitment request owned by a branch (add `branch_id` + BranchScope)?
4. **Worker statuses:** what is the full list of worker statuses and allowed transitions (and which permission or role may do each)?
5. **Profile tabs:** which tabs should the worker and client profiles have, and which permission does each tab need?
6. **Clients:** individuals only, or companies too? Which identifiers (national ID / iqama / CR number) must be masked?
7. Is there a real difference intended between `settings.edit` and `settings.manage`, or should they be merged?
8. Should the timezone be `Asia/Riyadh`? Are Hijri dates needed anywhere (iqama/passport expiry)?
9. May document versions be hard-deleted, or should deletions be soft (keeping the file)?
10. Should 2FA / password emails be queued (this requires a queue worker running in production)?
11. Should the dashboard keep demo figures until module 13, or hide them in production?
12. Should the agency portal mask passport numbers?
13. Keep `_template/` in the repository, or remove it?
14. Target production database: SQLite (current) or MySQL?
