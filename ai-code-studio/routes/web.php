<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PublishedAppController;
use App\Http\Controllers\Studio;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

// Installer (only reachable until installation finishes)
Route::prefix('install')->name('install.')->controller(InstallController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::get('/requirements', 'requirements')->name('requirements');
    Route::post('/test/{kind}', 'test')->whereIn('kind', ['db', 'ai', 'cron', 'lic'])->name('test');
    Route::post('/run', 'run')->name('run');
});

// Live preview of a project's working copy (token in the URL; the sandboxed iframe has no session)
Route::get('/preview/{slug}/{token}/{path?}', [Studio\BuilderController::class, 'preview'])->where('path', '.*')->name('preview');

// Published apps (sandboxed, public)
Route::get('/p/{subdomain}/{path?}', PublishedAppController::class)->where('path', '.*')->name('published');

Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'show'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/two-factor', [Auth\TwoFactorController::class, 'show'])->name('two-factor');
    Route::post('/two-factor', [Auth\TwoFactorController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [Auth\RegisterController::class, 'show'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [Auth\PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/forgot-password/sent', [Auth\PasswordController::class, 'sent'])->name('password.sent');
    Route::get('/reset-password/{token}', [Auth\PasswordController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [Auth\PasswordController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [Auth\LoginController::class, 'destroy'])->name('logout');
    Route::get('/verify-email', [Auth\VerifyEmailController::class, 'show'])->name('verification.notice');
    Route::post('/verify-email', [Auth\VerifyEmailController::class, 'store'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify-email/resend', [Auth\VerifyEmailController::class, 'resend'])->middleware('throttle:3,1')->name('verification.resend');
    Route::get('/onboarding', [Auth\OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [Auth\OnboardingController::class, 'store']);
    Route::post('/impersonation/stop', [Admin\UserController::class, 'stopImpersonating'])->name('impersonation.stop');
});

Route::middleware(['auth', 'ready'])->prefix('studio')->name('studio.')->group(function () {
    Route::get('/', [Studio\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/experience', [Studio\DashboardController::class, 'experience'])->name('experience');
    Route::get('/account', [Studio\AccountController::class, 'show'])->name('account');
    Route::post('/account/password', [Studio\AccountController::class, 'password'])->name('account.password');
    Route::post('/account/two-factor', [Studio\AccountController::class, 'enableTwoFactor'])->name('account.2fa');
    Route::post('/account/two-factor/confirm', [Studio\AccountController::class, 'confirmTwoFactor'])->name('account.2fa.confirm');
    Route::delete('/account/two-factor', [Studio\AccountController::class, 'disableTwoFactor'])->name('account.2fa.disable');

    Route::get('/new', [Studio\ProjectController::class, 'create'])->name('new');
    Route::post('/projects', [Studio\ProjectController::class, 'store'])->name('projects.store');

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('/', [Studio\BuilderController::class, 'show'])->name('builder');
        Route::delete('/', [Studio\ProjectController::class, 'destroy'])->name('destroy');
        Route::get('/plan', [Studio\ProjectController::class, 'plan'])->name('plan');
        Route::post('/plan', [Studio\ProjectController::class, 'updatePlan'])->name('plan.update');
        Route::post('/build', [Studio\ProjectController::class, 'build'])->name('build');
        Route::get('/state', [Studio\BuilderController::class, 'state'])->name('state');
        Route::post('/messages', [Studio\BuilderController::class, 'message'])->middleware('throttle:30,1')->name('messages');
        Route::post('/changes/{changeSet}/approve', [Studio\BuilderController::class, 'approve'])->name('changes.approve');
        Route::post('/changes/{changeSet}/reject', [Studio\BuilderController::class, 'reject'])->name('changes.reject');
        Route::post('/undo', [Studio\BuilderController::class, 'undo'])->name('undo');
        Route::get('/code', [Studio\WorkspaceController::class, 'show'])->name('code');
        Route::post('/files', [Studio\WorkspaceController::class, 'save'])->name('files.save');
        Route::delete('/files', [Studio\WorkspaceController::class, 'delete'])->name('files.delete');
        Route::get('/download', [Studio\WorkspaceController::class, 'download'])->name('download');
        Route::get('/agents', [Studio\AgentsController::class, 'show'])->name('agents');
        Route::get('/deploy', [Studio\DeployController::class, 'show'])->name('deploy');
        Route::post('/deploy', [Studio\DeployController::class, 'store'])->name('deploy.store');
        Route::post('/deployments/{deployment}/rollback', [Studio\DeployController::class, 'rollback'])->name('deploy.rollback');
    });

    Route::middleware('admin')->group(function () {
        Route::get('/providers', [Studio\PlatformController::class, 'providers'])->name('providers');
        Route::get('/brand', [Studio\PlatformController::class, 'brand'])->name('brand');
    });
});

Route::middleware(['auth', 'ready', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\OverviewController::class, 'index'])->name('overview');
    Route::post('/tips', [Admin\OverviewController::class, 'toggleTips'])->name('tips');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users');
    Route::get('/users/export', [Admin\UserController::class, 'export'])->name('users.export');
    Route::post('/users/{user}/credits', [Admin\UserController::class, 'credits'])->name('users.credits');
    Route::post('/users/{user}/plan', [Admin\UserController::class, 'plan'])->name('users.plan');
    Route::post('/users/{user}/reset', [Admin\UserController::class, 'sendReset'])->name('users.reset');
    Route::post('/users/{user}/impersonate', [Admin\UserController::class, 'impersonate'])->name('users.impersonate');
    Route::post('/users/{user}/status', [Admin\UserController::class, 'status'])->name('users.status');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/plans', [Admin\PlanController::class, 'index'])->name('plans');
    Route::post('/plans', [Admin\PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [Admin\PlanController::class, 'destroy'])->name('plans.destroy');
    Route::post('/gateways/{gateway}', [Admin\PlanController::class, 'gateway'])->whereIn('gateway', ['stripe', 'paystack', 'paypal'])->name('gateways.update');

    Route::get('/providers', [Admin\ProviderController::class, 'index'])->name('providers');
    Route::post('/providers', [Admin\ProviderController::class, 'store'])->name('providers.store');
    Route::put('/providers/{provider}', [Admin\ProviderController::class, 'update'])->name('providers.update');
    Route::post('/providers/{provider}/test', [Admin\ProviderController::class, 'test'])->name('providers.test');
    Route::delete('/providers/{provider}', [Admin\ProviderController::class, 'destroy'])->name('providers.destroy');
    Route::post('/routing', [Admin\ProviderController::class, 'routing'])->name('routing');

    Route::get('/branding', [Admin\SettingsController::class, 'branding'])->name('branding');
    Route::post('/branding', [Admin\SettingsController::class, 'saveBranding'])->name('branding.save');
    Route::get('/settings/{section?}', [Admin\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/{section}', [Admin\SettingsController::class, 'save'])->name('settings.save');
    Route::post('/settings/{section}/action', [Admin\SettingsController::class, 'action'])->name('settings.action');

    Route::get('/health', [Admin\SystemController::class, 'health'])->name('health');
    Route::post('/health/clear-cache', [Admin\SystemController::class, 'clearCache'])->name('health.clear');
    Route::get('/logs', [Admin\SystemController::class, 'logs'])->name('logs');
    Route::get('/logs/download', [Admin\SystemController::class, 'downloadLogs'])->name('logs.download');
    Route::get('/license', [Admin\SystemController::class, 'license'])->name('license');
    Route::post('/license', [Admin\SystemController::class, 'saveLicense'])->name('license.save');
});
