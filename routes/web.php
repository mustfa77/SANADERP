<?php

use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\CampController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentsDemoController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\HourlyBookingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RecruitmentCandidateController;
use App\Http\Controllers\Admin\RecruitmentFollowUpController;
use App\Http\Controllers\Admin\RecruitmentOrderController;
use App\Http\Controllers\Admin\ReferenceController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\RentalCalendarController;
use App\Http\Controllers\Admin\RentalContractController;
use App\Http\Controllers\Admin\RentalReportController;
use App\Http\Controllers\Admin\RentalWorkerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TravelStageController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkerCaseController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\Admin\WorkerCvController;
use App\Http\Controllers\Admin\WorkerReviewController;
use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Portal\AgencyPortalController;
use App\Http\Controllers\Portal\AgencyWorkerController;
use App\Http\Controllers\Portal\DriverPortalController;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Client;
use App\Models\HourlyBooking;
use App\Models\Payment;
use App\Models\RecruitmentOrder;
use App\Models\Refund;
use App\Models\RentalContract;
use App\Models\Role;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerCase;
use App\Services\BackupService;
use App\Services\Rental\PaymentService;
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

    // Workers (BranchScope: another branch's worker is a 404)
    Route::get('workers', [WorkerController::class, 'index'])->middleware('can:viewAny,'.Worker::class)->name('workers.index');
    Route::get('workers/export', [WorkerController::class, 'export'])->middleware('can:export,'.Worker::class)->name('workers.export');
    Route::get('workers/create', [WorkerController::class, 'create'])->middleware('can:create,'.Worker::class)->name('workers.create');
    Route::post('workers', [WorkerController::class, 'store'])->middleware('can:create,'.Worker::class)->name('workers.store');
    Route::get('workers/{worker}', [WorkerController::class, 'show'])->middleware('can:view,worker')->name('workers.show');
    Route::get('workers/{worker}/photo', [WorkerController::class, 'photo'])->middleware('can:view,worker')->name('workers.photo');
    Route::get('workers/{worker}/edit', [WorkerController::class, 'edit'])->middleware('can:update,worker')->name('workers.edit');
    Route::put('workers/{worker}', [WorkerController::class, 'update'])->middleware('can:update,worker')->name('workers.update');
    Route::post('workers/{worker}/status', [WorkerController::class, 'changeStatus'])->middleware('can:changeStatus,worker')->name('workers.status');

    Route::post('workers/{worker}/review', WorkerReviewController::class)->middleware('can:candidates.approve')->name('workers.review');
    Route::get('workers/{worker}/cv', [WorkerCvController::class, 'edit'])->middleware('can:update,worker')->name('workers.cv.edit');
    Route::put('workers/{worker}/cv', [WorkerCvController::class, 'update'])->middleware('can:update,worker')->name('workers.cv.update');
    Route::get('workers/{worker}/cv/view', [WorkerCvController::class, 'preview'])->middleware('can:view,worker')->name('workers.cv.preview');
    Route::get('workers/{worker}/cv.pdf', [WorkerCvController::class, 'pdf'])->middleware('can:view,worker')->name('workers.cv.pdf');
    Route::get('workers/{worker}/full-photo', [WorkerCvController::class, 'fullPhoto'])->middleware('can:view,worker')->name('workers.full-photo');

    // Recruitment orders (BranchScope; every step goes through RecruitmentService)
    Route::get('recruitment', [RecruitmentOrderController::class, 'index'])->middleware('can:viewAny,'.RecruitmentOrder::class)->name('recruitment.index');
    Route::get('recruitment/export', [RecruitmentOrderController::class, 'export'])->middleware('can:export,'.RecruitmentOrder::class)->name('recruitment.export');
    Route::get('recruitment/ready-candidates', [RecruitmentCandidateController::class, 'ready'])->middleware('can:viewAny,'.Worker::class)->name('recruitment.ready');
    Route::get('recruitment/documents', [RecruitmentCandidateController::class, 'documents'])->middleware(['can:viewAny,'.Worker::class, 'can:documents.view'])->name('recruitment.documents');
    Route::get('recruitment/follow-up', RecruitmentFollowUpController::class)->middleware('can:viewAny,'.RecruitmentOrder::class)->name('recruitment.follow-up');
    Route::get('recruitment/create', [RecruitmentOrderController::class, 'create'])->middleware('can:create,'.RecruitmentOrder::class)->name('recruitment.create');
    Route::post('recruitment', [RecruitmentOrderController::class, 'store'])->middleware('can:create,'.RecruitmentOrder::class)->name('recruitment.store');
    Route::get('recruitment/{order}', [RecruitmentOrderController::class, 'show'])->middleware('can:view,order')->name('recruitment.show');
    Route::get('recruitment/{order}/handover.pdf', [RecruitmentOrderController::class, 'handover'])->middleware('can:view,order')->name('recruitment.handover');
    Route::middleware('can:update,order')->prefix('recruitment/{order}')->name('recruitment.')->controller(RecruitmentOrderController::class)
        ->scopeBindings()->group(function (): void {
            Route::post('select', 'selectCandidate')->name('select');
            Route::post('release', 'releaseCandidate')->name('release');
            Route::post('contract', 'contract')->name('contract');
            Route::post('travel', 'startTravel')->name('travel');
            Route::post('stages/{stage}/complete', 'completeStage')->name('stages.complete');
            Route::post('arrived', 'arrived')->name('arrived');
            Route::post('deliver', 'deliver')->name('deliver');
            Route::post('probation/complete', 'completeProbation')->name('probation.complete');
            Route::post('probation/return', 'returnDuringProbation')->name('probation.return');
            Route::post('cancel', 'cancel')->name('cancel');
        });

    // Rental pool: workers available for monthly / hourly rental (permission checked in the controller)
    Route::get('rental/workers', [RentalWorkerController::class, 'index'])->name('rental.workers');
    Route::patch('rental/workers/{worker}', [RentalWorkerController::class, 'assign'])->name('rental.workers.assign');
    Route::get('rental/calendar', [RentalCalendarController::class, 'index'])->name('rental.calendar');
    Route::get('rental/calendar/events', [RentalCalendarController::class, 'events'])->name('rental.calendar.events');
    Route::get('rental/reports', RentalReportController::class)->middleware('can:reports.view')->name('rental.reports');

    // Monthly rental contracts (BranchScope; every step goes through RentalContractService)
    Route::get('rental-contracts', [RentalContractController::class, 'index'])->middleware('can:viewAny,'.RentalContract::class)->name('rental.index');
    Route::get('rental-contracts/create', [RentalContractController::class, 'create'])->middleware('can:create,'.RentalContract::class)->name('rental.create');
    Route::post('rental-contracts', [RentalContractController::class, 'store'])->middleware('can:create,'.RentalContract::class)->name('rental.store');
    Route::get('rental-contracts/{contract}', [RentalContractController::class, 'show'])->middleware('can:view,contract')->name('rental.show');
    Route::get('rental-contracts/{contract}/contract.pdf', [RentalContractController::class, 'pdf'])->middleware('can:print,contract')->name('rental.pdf');
    Route::post('rental-contracts/{contract}/renew', [RentalContractController::class, 'renew'])->middleware('can:update,contract')->name('rental.renew');
    Route::post('rental-contracts/{contract}/close', [RentalContractController::class, 'close'])->middleware('can:update,contract')->name('rental.close');
    Route::post('rental-contracts/{contract}/cancel', [RentalContractController::class, 'cancel'])->middleware('can:update,contract')->name('rental.cancel');
    Route::post('rental-contracts/{contract}/replace', [RentalContractController::class, 'replace'])->middleware('can:update,contract')->name('rental.replace');

    // Hourly bookings (BranchScope; every step goes through HourlyBookingService)
    Route::get('hourly-bookings', [HourlyBookingController::class, 'index'])->middleware('can:viewAny,'.HourlyBooking::class)->name('hourly.index');
    Route::get('hourly-bookings/create', [HourlyBookingController::class, 'create'])->middleware('can:create,'.HourlyBooking::class)->name('hourly.create');
    Route::post('hourly-bookings', [HourlyBookingController::class, 'store'])->middleware('can:create,'.HourlyBooking::class)->name('hourly.store');
    Route::get('hourly-bookings/{booking}', [HourlyBookingController::class, 'show'])->middleware('can:view,booking')->name('hourly.show');
    Route::post('hourly-bookings/{booking}/depart', [HourlyBookingController::class, 'depart'])->middleware('can:update,booking')->name('hourly.depart');
    Route::post('hourly-bookings/{booking}/complete', [HourlyBookingController::class, 'complete'])->middleware('can:update,booking')->name('hourly.complete');
    Route::post('hourly-bookings/{booking}/cancel', [HourlyBookingController::class, 'cancel'])->middleware('can:update,booking')->name('hourly.cancel');

    // Payments of contracts / bookings (receipts on the private disk)
    Route::post('payments/{payableType}/{payableId}', [PaymentController::class, 'store'])->middleware('can:create,'.Payment::class)
        ->whereIn('payableType', array_keys(PaymentService::PAYABLES))->whereNumber('payableId')->name('payments.store');
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->middleware('can:view,payment')->name('payments.receipt');
    Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->middleware('can:verify,payment')->name('payments.verify');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->middleware('can:delete,payment')->name('payments.destroy');

    // Refund requests (early return / cancellation), decided by finance
    Route::get('refunds', [RefundController::class, 'index'])->middleware('can:viewAny,'.Refund::class)->name('refunds.index');
    Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->middleware('can:decide,refund')->name('refunds.approve');
    Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->middleware('can:decide,refund')->name('refunds.reject');

    // Housing operations (BranchScope: another branch's camp is a 404)
    Route::get('camps', [CampController::class, 'index'])->middleware('can:camps.view')->name('camps.index');
    Route::get('camps/movements', [CampController::class, 'movements'])->middleware('can:camp_movements.view')->name('camps.movements');
    Route::get('camps/{camp}', [CampController::class, 'show'])->middleware('can:camps.view')->whereNumber('camp')->name('camps.show');
    Route::middleware('can:camp_movements.create')->group(function (): void {
        Route::post('camps/{camp}/check-in', [CampController::class, 'checkIn'])->name('camps.check-in');
        Route::post('camps/{camp}/workers/{worker}/move', [CampController::class, 'moveOut'])->name('camps.move');
        Route::post('camps/{camp}/attendance', [CampController::class, 'attendance'])->name('camps.attendance');
    });

    // In-system alerts (BranchScope; types filtered by the user's permissions)
    Route::get('alerts', [AlertController::class, 'index'])->middleware('can:alerts.view')->name('alerts.index');
    Route::post('alerts/refresh', [AlertController::class, 'refresh'])->middleware(['can:alerts.view', 'throttle:6,1'])->name('alerts.refresh');
    Route::post('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->middleware('can:alerts.edit')->name('alerts.resolve');

    // Transport: drivers and trips (BranchScope)
    Route::get('drivers', [DriverController::class, 'index'])->middleware('can:drivers.view')->name('drivers.index');
    Route::post('drivers', [DriverController::class, 'store'])->middleware('can:drivers.create')->name('drivers.store');
    Route::put('drivers/{driver}', [DriverController::class, 'update'])->middleware('can:drivers.edit')->name('drivers.update');
    Route::get('trips', [TripController::class, 'index'])->middleware('can:trips.view')->name('trips.index');
    Route::post('trips', [TripController::class, 'store'])->middleware('can:trips.create')->name('trips.store');
    Route::middleware('can:trips.edit')->prefix('trips/{trip}')->name('trips.')->controller(TripController::class)->group(function (): void {
        Route::post('assign', 'assign')->name('assign');
        Route::post('start', 'start')->name('start');
        Route::post('complete', 'complete')->name('complete');
        Route::post('cancel', 'cancel')->name('cancel');
    });

    // Cases: workers waiting for a decision (BranchScope)
    Route::get('cases', [WorkerCaseController::class, 'index'])->middleware('can:viewAny,'.WorkerCase::class)->name('cases.index');
    Route::get('cases/{case}', [WorkerCaseController::class, 'show'])->middleware('can:view,case')->name('cases.show');
    Route::post('cases/{case}/decide', [WorkerCaseController::class, 'decide'])->middleware('can:decide,case')->name('cases.decide');
    Route::post('cases/{case}/complete', [WorkerCaseController::class, 'complete'])->middleware('can:update,case')->name('cases.complete');
    Route::post('cases/{case}/notes', [WorkerCaseController::class, 'note'])->middleware('can:update,case')->name('cases.note');

    // Clients (BranchScope: another branch's client is a 404)
    Route::get('clients', [ClientController::class, 'index'])->middleware('can:viewAny,'.Client::class)->name('clients.index');
    Route::get('clients/export', [ClientController::class, 'export'])->middleware('can:export,'.Client::class)->name('clients.export');
    Route::get('clients/create', [ClientController::class, 'create'])->middleware('can:create,'.Client::class)->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])->middleware('can:create,'.Client::class)->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->middleware('can:view,client')->name('clients.show');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->middleware('can:update,client')->name('clients.edit');
    Route::put('clients/{client}', [ClientController::class, 'update'])->middleware('can:update,client')->name('clients.update');

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
    Route::put('settings/recruitment', [SettingController::class, 'updateRecruitment'])->middleware('can:settings.manage')->name('settings.recruitment');

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

    // The agency's workers and their CV.
    Route::get('workers', [AgencyWorkerController::class, 'index'])->name('workers.index');
    Route::get('workers/{worker}/cv', [AgencyWorkerController::class, 'edit'])->whereNumber('worker')->name('workers.edit');
    Route::put('workers/{worker}/cv', [AgencyWorkerController::class, 'update'])->whereNumber('worker')->name('workers.update');
    Route::get('workers/{worker}/cv/view', [AgencyWorkerController::class, 'preview'])->whereNumber('worker')->name('workers.preview');
    Route::get('workers/{worker}/cv.pdf', [AgencyWorkerController::class, 'pdf'])->whereNumber('worker')->name('workers.pdf');
    Route::get('workers/{worker}/photo/{type}', [AgencyWorkerController::class, 'photo'])->whereNumber('worker')->whereIn('type', ['face', 'full'])->name('workers.photo');
});

/*
|--------------------------------------------------------------------------
| Driver portal
|--------------------------------------------------------------------------
*/
Route::middleware(['panel', 'user.type:driver'])->prefix('driver')->name('driver.')->group(function (): void {
    Route::get('trips', [DriverPortalController::class, 'trips'])->name('trips.index');
    Route::post('trips/{trip}/start', [DriverPortalController::class, 'start'])->whereNumber('trip')->name('trips.start');
    Route::post('trips/{trip}/complete', [DriverPortalController::class, 'complete'])->whereNumber('trip')->name('trips.complete');
});
