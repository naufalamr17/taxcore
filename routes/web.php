<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:login')->name('login.store');
    Route::get('/setup', [AuthController::class, 'setup'])->name('setup');
    Route::post('/setup', [AuthController::class, 'initialize'])->middleware('throttle:login')->name('setup.store');
});
Route::middleware(['auth', EnsureActiveUser::class])->group(function (): void {
    Route::get('/dashboard', [WorkspaceController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::post('/companies/{company}/branches', [CompanyController::class, 'branch'])->name('branches.store');
    Route::get('/tax-reference', [WorkspaceController::class, 'reference'])->middleware('can:payroll.view')->name('tax.reference');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    Route::post('/access/users', [AccessController::class, 'store'])->name('access.store');
    Route::put('/access/users/{user}', [AccessController::class, 'update'])->name('access.update');
    Route::put('/access/permissions', [AccessController::class, 'permissions'])->name('access.permissions');
    Route::prefix('/companies/{company}')->group(function (): void {
        Route::get('/payroll', [WorkspaceController::class, 'index'])->name('payroll.index');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->name('employees.lookup');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::post('/payroll/bulk', [PayrollController::class, 'bulk'])->name('payroll.bulk');
        Route::get('/payroll/{entry}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::get('/payroll/{entry}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
        Route::put('/payroll/{entry}', [PayrollController::class, 'update'])->name('payroll.update');
        Route::delete('/payroll/{entry}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        Route::post('/payroll-runs/{run}/status', [PayrollController::class, 'transition'])->name('payroll.transition');
        Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');
        Route::post('/simulator', [SimulatorController::class, 'calculate'])->middleware('throttle:60,1')->name('simulator.calculate');
        Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::get('/imports/template', [ImportController::class, 'template'])->name('imports.template');
        Route::post('/imports/preview', [ImportController::class, 'preview'])->name('imports.preview');
        Route::post('/imports/{batch}/commit', [ImportController::class, 'commit'])->name('imports.commit');
        Route::get('/exports/{format}', [ExportController::class, 'download'])->name('exports.download');
        Route::get('/audit',[WorkspaceController::class, 'audit'])->name('audit.index');
    });
});
