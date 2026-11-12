# Sanad ERP: Project Rules

Bilingual (Arabic/English, RTL/LTR) admin system for a Saudi domestic-worker recruitment and rental company with multiple branches.
**Current status, module progress and known problems: [docs/PROJECT_STATUS.md](docs/PROJECT_STATUS.md). Read it before starting any task.**

## Working rules (every task)
1. Inspect first: read the related code, config, lang files and docs/PROJECT_STATUS.md.
2. List your questions and assumptions before writing code. Ask; do not guess business rules.
3. Work in small steps. Show the plan, then wait for confirmation before each step.
4. Never modify modules unrelated to the task. Report problems you find; do not fix them silently.
5. Every change comes with feature tests. Run the narrowest tests, then `vendor/bin/pint --dirty --format agent`.
6. Update docs/PROJECT_STATUS.md when a module or step is completed.

## Stack and commands
- Laravel 13, PHP 8.3, SQLite (`database/database.sqlite`), spatie/laravel-permission 8, mpdf 8, spatie/simple-excel 3, PHPUnit 12.
- PHP is not on PATH in the default shell. Use `C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe` (or add it to PATH).
- UI: Upzet Bootstrap 5 template, static files in `public/assets` (RTL CSS loaded when `dir=rtl`). Do **not** use Tailwind/Vite in Blade, even though they are installed.
- Run: `php artisan serve` (or Laragon). Setup: `php artisan migrate --seed` (needs `SUPER_ADMIN_*` in `.env`).
- Tests: `php artisan test --compact` or `vendor/bin/phpunit` (SQLite in memory, never touches the dev DB).
- Queue: `php artisan queue:work` (driver `database`). Scheduler: `php artisan schedule:work` (backup hourly check, `model:prune` 03:30).

## Where code goes
| Kind | Location |
|---|---|
| Admin controllers | `app/Http/Controllers/Admin` (portals: `Portal/`, auth: `Auth/`) |
| Validation | FormRequest in `app/Http/Requests/Admin` (preferred for new screens) |
| Business logic | `app/Services/{Module}/` (e.g. `Services/Documents`). Controllers stay thin |
| Helpers / registries | `app/Support` |
| Model traits / scopes | `app/Models/Concerns`, `app/Models/Scopes` |
| Enums | `app/Enums` (string-backed, TitleCase cases, `label()` reading lang) |
| Policies | `app/Policies` (auto-discovered) |
| Views | `resources/views/admin/{module}/` (index, create, edit, show, `_form`), components in `resources/views/components` |
| Config catalogues | `config/permissions.php`, `config/menu.php`, `config/documents.php` |
| Translations | `lang/ar/{module}.php` + `lang/en/{module}.php` (same keys in both) |

## Bilingual rules
- Translatable columns are `{field}_ar` + `{field}_en`; the model uses `HasTranslations`. Read with `$model->trans('name')`, store both with `$model->translations('name')`.
- No hard-coded UI text in Blade, controllers, enums or flash messages: always `__('module.key')`, with the key added to **both** `lang/ar` and `lang/en`.
- Layouts already set `dir`/RTL CSS. Use `dir="ltr"` for codes, phones, emails, passport/iqama numbers. Use logical Bootstrap classes (`ms-`/`me-`, `text-start`/`text-end`).
- Enum labels: `__("module.group.{$this->value}")`.

## Permissions
- Defined **only** in `config/permissions.php` (sections → modules → actions). Name format: `module.action` in snake_case (`workers.view`, `candidates.approve`). Extra actions go under `special`. Labels go in `lang/{ar,en}/permissions.php` (`modules`, `actions`, `special`).
- After adding: `php artisan db:seed --class=PermissionSeeder`. Default role grants live in `config/permissions.php` `roles` (applied only when a role is first created).
- Protect every route with middleware `can:perm` or `can:ability,model` (policy for model-specific rules). Protect Blade with `@can('perm')`. Never leave an admin route without a permission check.
- Sidebar entries: `config/menu.php` (`route => null` shows "soon").
- Settings screens: `settings.view` to see, `settings.manage` to change reference data. Do not add new uses of `settings.edit` until Q7 in PROJECT_STATUS.md is answered.

## Branch scope
- Every branch-owned table has a non-null `branch_id` FK, and its model uses `App\Models\Concerns\BelongsToBranch` (adds `BranchScope`). This gives a 404 on another branch's record through route model binding.
- Use `withoutGlobalScope(BranchScope::class)` only with a written reason (e.g. counts on the Branch page, driver portal).
- Branch pickers in forms list only `auth()->user()->allowedBranchIds()`.

## Worker status
- `WorkerStatusService` does not exist yet (Module 3). From now on, statuses are enums and change **only** through a status service. Allowed transitions are defined in `config/workflow.php`, and every change writes a status-history row and an audit entry. Never `update(['status' => ...])` directly.

## Documents engine
To attach it to a model:
1. Implement `App\Services\Documents\DocumentOwner` (`documentCountryId()`, `documentProfessionId()`, `documentOwnerName()`) and `use HasDocuments`.
2. Register an alias in `config/documents.php` `owners` with the model and the view permission it requires.
3. Render `<x-documents.checklist :owner="$model" />` and `<x-documents.print-button :owner="$model" :authority="$id" />`.
Files are stored only on the private `local` disk and served through `DocumentController` routes.

## Profile tabs
- No profile-tabs config exists yet. From now on, define tabs in `config/profile_tabs.php` (key, label key, view partial, permission) and render them from that config. Do not hard-code tabs in a view.

## Audit log
- Log every create/update/delete/status change: `app(AuditLogger::class)->log('module.action', $model, ['name' => $model->translations('name')], $old, $new)`.
- Add `actions.{module.action}` and `action_names.{module.action}` to `lang/{ar,en}/audit.php`, plus `targets.{ModelClass}` for new targets.

## Deleting
- No hard deletes of business data: models use `SoftDeletes`. Refuse deletion when `isInUse()` and offer deactivation (`is_active` + `HasActiveFlag`).
- When a new module references an existing table, update that model's `isInUse()` (e.g. `Camp`, `Shift`, `Lookup`, `Profession`, `Country`, `Branch::destroy`).

## External integrations
- Mail, WhatsApp, Wafeq and any other HTTP call run in queued jobs (`ShouldQueue`, retries, logged failures), never inline in a request. WhatsApp sending stays disabled until approved; manual `wa.me` links are allowed.

## Money and dates
- Money: `decimal(10,2)` columns with the `decimal:2` cast; VAT per record (default 15%). Dates use Carbon casts.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
