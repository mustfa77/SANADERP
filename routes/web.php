<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentsDemoController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReferenceController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TravelStageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Portal\AgencyPortalController;
use App\Http\Controllers\Portal\DriverPortalController;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\BackupService;
use App\Support\Reference\ReferenceRegistry;
use Illuminate\Support\Facades\Route;

Route::get('lang/{locale}', LanguageController::class)->name('lang.switch');

Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->user_type->homeRoute())
    : redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('two-factor', [TwoFactorController::class, 'store'])
        ->middleware('throttle:two-factor')->name('two-factor.verify');
    Route::post('two-factor/resend', [TwoFactorController::class, 'resend'])
        ->middleware('throttle:3,1')->name('two-factor.resend');

    Route::middleware('two-factor')->group(function (): void {
        Route::get('change-password', [ForcePasswordController::class, 'edit'])->name('password.force');
        Route::put('change-password', [ForcePasswordController::class, 'update'])->name('password.force.update');
    });
});

/*
|--------------------------------------------------------------------------
| Admin dashboard (internal staff)
|--------------------------------------------------------------------------
*/
Route::middleware(['panel', 'user.type:internal'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

    Route::post('branch', BranchSwitchController::class)->name('branch.switch');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('profile/language', [ProfileController::class, 'updateLanguage'])->name('profile.language');
    Route::put('profile/two-factor', [ProfileController::class, 'updateTwoFactor'])->name('profile.two-factor');

    // Branches
    Route::get('branches', [BranchController::class, 'index'])->middleware('can:viewAny,'.Branch::class)->name('branches.index');
    Route::get('branches/export', [BranchController::class, 'export'])->middleware('can:export,'.Branch::class)->name('branches.export');
    Route::get('branches/create', [BranchController::class, 'create'])->middleware('can:create,'.Branch::class)->name('branches.create');
    Route::post('branches', [BranchController::class, 'store'])->middleware('can:create,'.Branch::class)->name('branches.store');
    Route::get('branches/{branch}', [BranchController::class, 'show'])->middleware('can:view,branch')->name('branches.show');
    Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->middleware('can:update,branch')->name('branches.edit');
    Route::put('branches/{branch}', [BranchController::class, 'update'])->middleware('can:update,branch')->name('branches.update');
    Route::patch('branches/{branch}/activate', [BranchController::class, 'activate'])->middleware('can:update,branch')->name('branches.activate');
    Route::patch('branches/{branch}/deactivate', [BranchController::class, 'deactivate'])->middleware('can:update,branch')->name('branches.deactivate');
    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('can:delete,branch')->name('branches.destroy');

    // Users
    Route::get('users', [UserController::class, 'index'])->middleware('can:viewAny,'.User::class)->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->middleware('can:create,'.User::class)->name('users.create');
    Route::post('users', [UserController::class, 'store'])->middleware('can:create,'.User::class)->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('can:view,user')->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('can:update,user')->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:update,user')->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('can:delete,user')->name('users.destroy');
    Route::patch('users/{user}/activate', [UserController::class, 'activate'])->middleware('can:activate,user')->name('users.activate');
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('can:deactivate,user')->name('users.deactivate');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('can:resetPassword,user')->name('users.reset-password');

    // Roles
    Route::get('roles', [RoleController::class, 'index'])->middleware('can:viewAny,'.Role::class)->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->middleware('can:create,'.Role::class)->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])->middleware('can:create,'.Role::class)->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:update,role')->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:update,role')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:delete,role')->name('roles.destroy');
    Route::post('roles/{role}/clone', [RoleController::class, 'clone'])->middleware('can:clone,role')->name('roles.clone');
    Route::get('roles/{role}/permissions', [RoleController::class, 'editPermissions'])->middleware('can:view,role')->name('roles.permissions.edit');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('can:updatePermissions,role')->name('roles.permissions.update');

    // Audit log
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('can:viewAny,'.AuditLog::class)->name('audit-logs.index');
    Route::get('audit-logs/export', [AuditLogController::class, 'export'])->middleware('can:export,'.AuditLog::class)->name('audit-logs.export');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('can:view,auditLog')->name('audit-logs.show');

    // Settings
    Route::get('settings', [SettingController::class, 'edit'])->middleware('can:settings.view')->name('settings.edit');
    Route::middleware('can:settings.edit')->prefix('settings')->name('settings.')->group(function (): void {
        Route::put('general', [SettingController::class, 'updateGeneral'])->name('general');
        Route::put('appearance', [SettingController::class, 'updateAppearance'])->name('appearance');
        Route::delete('appearance', [SettingController::class, 'resetAppearance'])->name('appearance.reset');
        Route::put('mail', [SettingController::class, 'updateMail'])->name('mail');
        Route::post('mail/test', [SettingController::class, 'sendTestMail'])->middleware('throttle:5,1')->name('mail.test');
        Route::put('security', [SettingController::class, 'updateSecurity'])->name('security');
        Route::put('maintenance', [SettingController::class, 'updateMaintenance'])->name('maintenance');

        Route::post('backups', [BackupController::class, 'store'])->middleware('throttle:3,1')->name('backups.store');
        Route::get('backups/{backup}', [BackupController::class, 'download'])->where('backup', BackupService::NAME_PATTERN)->name('backups.download');
        Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->where('backup', BackupService::NAME_PATTERN)->name('backups.destroy');
    });
    Route::put('settings/alerts', [SettingController::class, 'updateAlerts'])->middleware('can:settings.manage')->name('settings.alerts');

    /*
    | Reference data lists (Settings): one route set per ReferenceRegistry
    | resource, each protected by that resource's permissions.
    */
    foreach (app(ReferenceRegistry::class)->all() as $key => $resource) {
        Route::prefix('settings/'.str_replace('_', '-', $key))
            ->name("reference.{$key}.")
            ->controller(ReferenceController::class)
            ->group(function () use ($key, $resource): void {
                Route::get('/', 'index')->middleware('can:'.$resource->permission('view'))->defaults('resource', $key)->name('index');
                Route::get('create', 'create')->middleware('can:'.$resource->permission('create'))->defaults('resource', $key)->name('create');
                Route::post('/', 'store')->middleware('can:'.$resource->permission('create'))->defaults('resource', $key)->name('store');
                Route::get('{record}/edit', 'edit')->middleware('can:'.$resource->permission('edit'))->defaults('resource', $key)->whereNumber('record')->name('edit');
                Route::put('{record}', 'update')->middleware('can:'.$resource->permission('edit'))->defaults('resource', $key)->whereNumber('record')->name('update');
                Route::patch('{record}/activate', 'activate')->middleware('can:'.$resource->permission('edit'))->defaults('resource', $key)->whereNumber('record')->name('activate');
                Route::patch('{record}/deactivate', 'deactivate')->middleware('can:'.$resource->permission('edit'))->defaults('resource', $key)->whereNumber('record')->name('deactivate');
                Route::delete('{record}', 'destroy')->middleware('can:'.$resource->permission('delete'))->defaults('resource', $key)->whereNumber('record')->name('destroy');

                if ($resource->isSortable()) {
                    Route::patch('{record}/move', 'move')->middleware('can:'.$resource->permission('edit'))->defaults('resource', $key)->whereNumber('record')->name('move');
                }
            });
    }

    // Country page (travel stages and document types tabs)
    Route::get('settings/countries/{country}', [CountryController::class, 'show'])->middleware('can:settings.view')->whereNumber('country')->name('countries.show');
    Route::middleware('can:settings.manage')->prefix('settings/countries/{country}/stages')->name('countries.stages.')
        ->controller(TravelStageController::class)->scopeBindings()->group(function (): void {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{stage}/edit', 'edit')->name('edit');
            Route::put('{stage}', 'update')->name('update');
            Route::patch('{stage}/move', 'move')->name('move');
            Route::patch('{stage}/toggle', 'toggle')->name('toggle');
            Route::delete('{stage}', 'destroy')->name('destroy');
        });

    // Documents engine (files are only served through these routes)
    Route::prefix('documents')->name('documents.')->controller(DocumentController::class)->group(function (): void {
        Route::post('{ownerType}/{ownerId}/types/{documentType}', 'upload')->middleware('can:documents.upload')->whereNumber('ownerId')->name('upload');
        Route::post('{ownerType}/{ownerId}/extra', 'extra')->middleware('can:documents.upload')->whereNumber('ownerId')->name('extra');
        Route::post('{ownerType}/{ownerId}/authorities/{authority}/print', 'print')->middleware('can:documents.print_authority_file')->whereNumber('ownerId')->name('print');
        Route::get('files/{documentFile}', 'show')->middleware('can:documents.view')->name('files.show');
        Route::delete('files/{documentFile}', 'destroyVersion')->middleware('can:documents.delete_version')->name('files.destroy');
        Route::get('prints/{documentPrint}', 'downloadStoredPrint')->middleware('can:documents.print_authority_file')->name('prints.download');
    });

    if (app()->isLocal()) {
        Route::get('dev/documents-demo', DocumentsDemoController::class)->middleware('can:documents.view')->name('documents.demo');
    }

    // Document types (global list; country types are shown on the country page)
    Route::get('settings/document-types', [DocumentTypeController::class, 'index'])->middleware('can:settings.view')->name('document-types.index');
    Route::middleware('can:document_types.manage')->prefix('settings/document-types')->name('document-types.')
        ->controller(DocumentTypeController::class)->group(function (): void {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{documentType}/edit', 'edit')->name('edit');
            Route::put('{documentType}', 'update')->name('update');
            Route::patch('{documentType}/move', 'move')->name('move');
            Route::patch('{documentType}/toggle', 'toggle')->name('toggle');
            Route::delete('{documentType}', 'destroy')->name('destroy');
        });
});

/*
|--------------------------------------------------------------------------
| External agency portal
|--------------------------------------------------------------------------
*/
Route::middleware(['panel', 'user.type:external_agency'])->prefix('agency')->name('agency.')->group(function (): void {
    Route::get('candidates', [AgencyPortalController::class, 'candidates'])->name('candidates.index');
    Route::get('documents', [AgencyPortalController::class, 'documents'])->name('documents.index');
});

/*
|--------------------------------------------------------------------------
| Driver portal
|--------------------------------------------------------------------------
*/
Route::middleware(['panel', 'user.type:driver'])->prefix('driver')->name('driver.')->group(function (): void {
    Route::get('trips', [DriverPortalController::class, 'trips'])->name('trips.index');
});
