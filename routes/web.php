<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorManagementController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\TenderOwnerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenderOwnerApplicationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\EligibilityFailureController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==================== GUEST ROUTES ====================
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    // Password reset
    Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// ==================== LOGOUT ====================
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==================== COOKIE CONSENT ====================
Route::post('/cookie-accept', function () {
    session(['cookie_accepted' => true]);
    return response()->json(['ok' => true]);
})->middleware('auth')->name('cookie.accept');

// ==================== SUPER ADMIN ROUTES ====================
Route::middleware(['auth', 'role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    
    // Vendor Management
    Route::get('/vendors', [VendorManagementController::class, 'index'])->name('vendors');
    Route::get('/vendors/data', [VendorManagementController::class, 'getData'])->name('vendors.data');
    Route::get('/vendors/{id}', [VendorManagementController::class, 'show'])->name('vendors.show');
    Route::post('/vendors/{id}/approve', [VendorManagementController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{id}/reject', [VendorManagementController::class, 'reject'])->name('vendors.reject');
    Route::post('/vendors/{id}/suspend', [VendorManagementController::class, 'suspend'])->name('vendors.suspend');
    Route::post('/vendors/{id}/activate', [VendorManagementController::class, 'activate'])->name('vendors.activate');
    Route::post('/vendors/{id}/categories', [VendorManagementController::class, 'updateCategories'])->name('vendors.categories.update');
    Route::post('/vendors/bulk-action', [VendorManagementController::class, 'bulkAction'])->name('vendors.bulk-action');
    Route::post('/vendors/export', [VendorManagementController::class, 'export'])->name('vendors.export');

    // Admin Management
    Route::get('/admins', [SuperAdminController::class, 'admins'])->name('admins');
    Route::post('/admins', [SuperAdminController::class, 'storeAdmin'])->name('admins.store');
    Route::delete('/admins/{id}', [SuperAdminController::class, 'deleteAdmin'])->name('admins.delete');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    
    // Settings
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings');
    Route::post('/settings/general', [SuperAdminController::class, 'updateGeneralSettings'])->name('settings.general');
    Route::post('/settings/roles', [SuperAdminController::class, 'updateRolePermissions'])->name('settings.roles');
    
    // Audit Logs
    Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/audit-logs/{id}', [SuperAdminController::class, 'auditLogDetails'])->name('audit-logs.show');

    // Tender Management (same as admin)
    Route::get('/tenders', [TenderController::class, 'index'])->name('tenders');
    Route::get('/tenders/data', [TenderController::class, 'getTendersData'])->name('tenders.data');
    Route::get('/tenders/create', [TenderController::class, 'create'])->name('tenders.create');
    Route::post('/tenders', [TenderController::class, 'store'])->name('tenders.store');
    Route::get('/tenders/{id}', [TenderController::class, 'show'])->name('tenders.show');
    Route::get('/tenders/{id}/edit', [TenderController::class, 'edit'])->name('tenders.edit');
    Route::put('/tenders/{id}', [TenderController::class, 'update'])->name('tenders.update');
    Route::delete('/tenders/{id}', [TenderController::class, 'destroy'])->name('tenders.delete');
    Route::post('/tenders/{id}/publish', [TenderController::class, 'publish'])->name('tenders.publish');
    Route::post('/tenders/{id}/university-display', [TenderController::class, 'updateUniversityDisplay'])->name('tenders.university-display');
    Route::post('/tenders/{id}/close', [TenderController::class, 'close'])->name('tenders.close');
    Route::post('/tenders/{id}/duplicate', [TenderController::class, 'duplicate'])->name('tenders.duplicate');
    Route::delete('/tenders/documents/{id}', [TenderController::class, 'deleteDocument'])->name('tenders.delete-document');
    Route::post('/tenders/documents/{id}/order', [TenderController::class, 'updateDocumentOrder'])->name('tenders.update-document-order');
    Route::get('/tenders/{id}/eligibility', [TenderController::class, 'eligibilityQuestions'])->name('tenders.eligibility');
    Route::post('/tenders/{id}/eligibility', [TenderController::class, 'storeEligibilityQuestions'])->name('tenders.eligibility.store');
    Route::get('/tenders/questions/{id}', [TenderController::class, 'getEligibilityQuestion'])->name('tenders.get-question');
    Route::put('/tenders/questions/{id}', [TenderController::class, 'updateEligibilityQuestion'])->name('tenders.update-question');
    Route::delete('/tenders/questions/{id}', [TenderController::class, 'deleteEligibilityQuestion'])->name('tenders.delete-question');
    Route::post('/tenders/{id}/questions', [TenderController::class, 'storeEligibilityQuestion'])->name('tenders.store-question');

    // Committee Management
    Route::get('/tenders/{id}/committee', [TenderOwnerController::class, 'showCommittee'])->name('tenders.committee');
    Route::post('/tenders/{id}/committee/add', [TenderOwnerController::class, 'addCommitteeMember'])->name('tenders.committee.add');
    Route::post('/tenders/{id}/committee/create-user', [TenderOwnerController::class, 'createAndAddCommitteeMember'])->name('tenders.committee.create-user');
    Route::delete('/tenders/{tenderId}/committee/remove/{userId}', [TenderOwnerController::class, 'removeCommitteeMember'])->name('tenders.committee.remove');
    Route::put('/tenders/{tenderId}/committee/{userId}', [TenderOwnerController::class, 'updateCommitteeMember'])->name('tenders.committee.update');

    // Application Management
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications');
    Route::get('/applications/data', [ApplicationController::class, 'getApplicationsData'])->name('applications.data');
    Route::get('/applications/{id}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{id}/award', [ApplicationController::class, 'awardForm'])->name('applications.award-form');
    Route::post('/applications/{id}/shortlist', [ApplicationController::class, 'shortlist'])->name('applications.shortlist');
    Route::post('/applications/{id}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::post('/applications/{id}/award', [ApplicationController::class, 'award'])->name('applications.award');
    Route::post('/applications/bulk-action', [ApplicationController::class, 'bulkAction'])->name('applications.bulk-action');

    // Contract Management + Work/Purchase Order
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts');
    Route::get('/contracts/data', [ContractController::class, 'getContractsData'])->name('contracts.data');
    Route::get('/contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{id}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
    Route::post('/contracts/{id}/extend', [ContractController::class, 'extend'])->name('contracts.extend');
    Route::post('/contracts/{id}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');
    Route::post('/contracts/generate/{applicationId}', [ContractController::class, 'generate'])->name('contracts.generate');
    Route::get('/contracts/{id}/work-order', [WorkOrderController::class, 'showForm'])->name('contracts.work-order');
    Route::get('/contracts/{id}/order-pdf', [ContractController::class, 'downloadOrderPdf'])->name('contracts.order-pdf');
    Route::post('/contracts/{id}/work-order/download', [WorkOrderController::class, 'downloadPdf'])->name('contracts.work-order.download');
    Route::post('/contracts/{id}/work-order/email', [WorkOrderController::class, 'sendEmail'])->name('contracts.work-order.email');
    Route::post('/contracts/{id}/supporting-docs', [ContractController::class, 'uploadSupportingDocs'])->name('contracts.supporting-docs.upload');
    Route::delete('/contracts/{id}/supporting-docs', [ContractController::class, 'deleteSupportingDoc'])->name('contracts.supporting-docs.delete');

    // Notifications
    Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{id}/read', [AdminController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [AdminController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.delete');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');

    // Category Management
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.delete');
    Route::post('/categories/{id}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
    Route::get('/categories/{id}/vendors', [CategoryController::class, 'vendorsJson'])->name('categories.vendors-json');

    // Eligibility Failures
    Route::get('/eligibility-failures', [EligibilityFailureController::class, 'index'])->name('eligibility-failures');
    Route::get('/eligibility-failures/data', [EligibilityFailureController::class, 'data'])->name('eligibility-failures.data');

    // Masters
    Route::get('/masters/terms-conditions', [\App\Http\Controllers\TermsConditionController::class, 'index'])->name('masters.terms');
    Route::post('/masters/terms-conditions', [\App\Http\Controllers\TermsConditionController::class, 'store'])->name('masters.terms.store');
    Route::put('/masters/terms-conditions/{termsCondition}', [\App\Http\Controllers\TermsConditionController::class, 'update'])->name('masters.terms.update');
    Route::delete('/masters/terms-conditions/{termsCondition}', [\App\Http\Controllers\TermsConditionController::class, 'destroy'])->name('masters.terms.delete');
    Route::post('/masters/terms-conditions/{termsCondition}/toggle', [\App\Http\Controllers\TermsConditionController::class, 'toggle'])->name('masters.terms.toggle');
});

// ==================== ADMIN ROUTES ====================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/stats', [AdminController::class, 'getStats'])->name('stats');
    
    // Vendor Management
    Route::get('/vendors', [VendorManagementController::class, 'index'])->name('vendors');
    Route::get('/vendors/data', [VendorManagementController::class, 'getData'])->name('vendors.data');
    Route::get('/vendors/{id}', [VendorManagementController::class, 'show'])->name('vendors.show');
    Route::post('/vendors/{id}/approve', [VendorManagementController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{id}/reject', [VendorManagementController::class, 'reject'])->name('vendors.reject');
    Route::post('/vendors/{id}/suspend', [VendorManagementController::class, 'suspend'])->name('vendors.suspend');
    Route::post('/vendors/{id}/activate', [VendorManagementController::class, 'activate'])->name('vendors.activate');
    Route::post('/vendors/{id}/categories', [VendorManagementController::class, 'updateCategories'])->name('vendors.categories.update');
    Route::post('/vendors/bulk-action', [VendorManagementController::class, 'bulkAction'])->name('vendors.bulk-action');
    Route::post('/vendors/export', [VendorManagementController::class, 'export'])->name('vendors.export');

    // Tender Management
    Route::get('/tenders', [TenderController::class, 'index'])->name('tenders');
    Route::get('/tenders/data', [TenderController::class, 'getTendersData'])->name('tenders.data');
    Route::get('/tenders/create', [TenderController::class, 'create'])->name('tenders.create');
    Route::post('/tenders', [TenderController::class, 'store'])->name('tenders.store');
    Route::get('/tenders/{id}', [TenderController::class, 'show'])->name('tenders.show');
    Route::get('/tenders/{id}/edit', [TenderController::class, 'edit'])->name('tenders.edit');
    Route::put('/tenders/{id}', [TenderController::class, 'update'])->name('tenders.update');
    Route::delete('/tenders/{id}', [TenderController::class, 'destroy'])->name('tenders.delete');
    Route::post('/tenders/{id}/publish', [TenderController::class, 'publish'])->name('tenders.publish');
    Route::post('/tenders/{id}/university-display', [TenderController::class, 'updateUniversityDisplay'])->name('tenders.university-display');
    Route::post('/tenders/{id}/close', [TenderController::class, 'close'])->name('tenders.close');
    Route::post('/tenders/{id}/duplicate', [TenderController::class, 'duplicate'])->name('tenders.duplicate');

    // Document Management
    Route::delete('/tenders/documents/{id}', [TenderController::class, 'deleteDocument'])->name('tenders.delete-document');
    Route::post('/tenders/documents/{id}/order', [TenderController::class, 'updateDocumentOrder'])->name('tenders.update-document-order');
    
    // Eligibility Questions Management
    Route::get('/tenders/{id}/eligibility', [TenderController::class, 'eligibilityQuestions'])->name('tenders.eligibility');
    Route::post('/tenders/{id}/eligibility', [TenderController::class, 'storeEligibilityQuestions'])->name('tenders.eligibility.store');
    Route::get('/tenders/questions/{id}', [TenderController::class, 'getEligibilityQuestion'])->name('tenders.get-question');
    Route::put('/tenders/questions/{id}', [TenderController::class, 'updateEligibilityQuestion'])->name('tenders.update-question');
    Route::delete('/tenders/questions/{id}', [TenderController::class, 'deleteEligibilityQuestion'])->name('tenders.delete-question');
    Route::post('/tenders/{id}/questions', [TenderController::class, 'storeEligibilityQuestion'])->name('tenders.store-question');

    // Application Management
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications');
    Route::get('/applications/data', [ApplicationController::class, 'getApplicationsData'])->name('applications.data');
    Route::get('/applications/{id}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{id}/award', [ApplicationController::class, 'awardForm'])->name('applications.award-form');
    Route::post('/applications/{id}/shortlist', [ApplicationController::class, 'shortlist'])->name('applications.shortlist');
    Route::post('/applications/{id}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::post('/applications/{id}/award', [ApplicationController::class, 'award'])->name('applications.award');
    Route::post('/applications/bulk-action', [ApplicationController::class, 'bulkAction'])->name('applications.bulk-action');

    // Contract Management
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts');
    Route::get('/contracts/data', [ContractController::class, 'getContractsData'])->name('contracts.data');
    Route::get('/contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{id}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
    Route::post('/contracts/{id}/extend', [ContractController::class, 'extend'])->name('contracts.extend');
    Route::post('/contracts/{id}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');
    Route::post('/contracts/generate/{applicationId}', [ContractController::class, 'generate'])->name('contracts.generate');
    Route::get('/contracts/{id}/work-order', [WorkOrderController::class, 'showForm'])->name('contracts.work-order');
    Route::get('/contracts/{id}/order-pdf', [ContractController::class, 'downloadOrderPdf'])->name('contracts.order-pdf');
    Route::post('/contracts/{id}/work-order/download', [WorkOrderController::class, 'downloadPdf'])->name('contracts.work-order.download');
    Route::post('/contracts/{id}/work-order/email', [WorkOrderController::class, 'sendEmail'])->name('contracts.work-order.email');
    Route::post('/contracts/{id}/supporting-docs', [ContractController::class, 'uploadSupportingDocs'])->name('contracts.supporting-docs.upload');
    Route::delete('/contracts/{id}/supporting-docs', [ContractController::class, 'deleteSupportingDoc'])->name('contracts.supporting-docs.delete');

    // Eligibility Failures
    Route::get('/eligibility-failures', [EligibilityFailureController::class, 'index'])->name('eligibility-failures');
    Route::get('/eligibility-failures/data', [EligibilityFailureController::class, 'data'])->name('eligibility-failures.data');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    // Committee Management (admin can manage any tender's committee)
    Route::get('/tenders/{id}/committee', [TenderOwnerController::class, 'showCommittee'])->name('tenders.committee');
    Route::post('/tenders/{id}/committee/add', [TenderOwnerController::class, 'addCommitteeMember'])->name('tenders.committee.add');
    Route::post('/tenders/{id}/committee/create-user', [TenderOwnerController::class, 'createAndAddCommitteeMember'])->name('tenders.committee.create-user');
    Route::delete('/tenders/{tenderId}/committee/remove/{userId}', [TenderOwnerController::class, 'removeCommitteeMember'])->name('tenders.committee.remove');
    Route::put('/tenders/{tenderId}/committee/{userId}', [TenderOwnerController::class, 'updateCommitteeMember'])->name('tenders.committee.update');

    // Notifications
    Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{id}/read', [AdminController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [AdminController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.delete');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');

    // Category Management
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.delete');
    Route::post('/categories/{id}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
    Route::get('/categories/{id}/vendors', [CategoryController::class, 'vendorsJson'])->name('categories.vendors-json');

    // Masters
    Route::get('/masters/terms-conditions', [\App\Http\Controllers\TermsConditionController::class, 'index'])->name('masters.terms');
    Route::post('/masters/terms-conditions', [\App\Http\Controllers\TermsConditionController::class, 'store'])->name('masters.terms.store');
    Route::put('/masters/terms-conditions/{termsCondition}', [\App\Http\Controllers\TermsConditionController::class, 'update'])->name('masters.terms.update');
    Route::delete('/masters/terms-conditions/{termsCondition}', [\App\Http\Controllers\TermsConditionController::class, 'destroy'])->name('masters.terms.delete');
    Route::post('/masters/terms-conditions/{termsCondition}/toggle', [\App\Http\Controllers\TermsConditionController::class, 'toggle'])->name('masters.terms.toggle');
});

// ==================== TENDER OWNER ROUTES ====================
// ==================== TENDER OWNER ROUTES ====================
// ==================== TENDER OWNER ROUTES ====================
Route::middleware(['auth', 'role:tender-owner'])->prefix('tender-owner')->name('tender-owner.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [TenderOwnerController::class, 'dashboard'])->name('dashboard');
    
    // Tenders
    Route::get('/tenders', [TenderController::class, 'ownerIndex'])->name('tenders.index');
    Route::get('/tenders/create', [TenderController::class, 'ownerCreate'])->name('tenders.create');
    Route::post('/tenders', [TenderController::class, 'ownerStore'])->name('tenders.store');
    Route::get('/tenders/{id}', [TenderController::class, 'ownerShow'])->name('tenders.show');
    Route::get('/tenders/{id}/edit', [TenderController::class, 'ownerEdit'])->name('tenders.edit');
    Route::put('/tenders/{id}', [TenderController::class, 'ownerUpdate'])->name('tenders.update');
    Route::delete('/tenders/{id}', [TenderController::class, 'ownerDestroy'])->name('tenders.delete');
    Route::post('/tenders/{id}/publish', [TenderController::class, 'ownerPublish'])->name('tenders.publish');
    Route::post('/tenders/{id}/university-display', [TenderController::class, 'updateUniversityDisplay'])->name('tenders.university-display');
    Route::post('/tenders/{id}/close', [TenderController::class, 'ownerClose'])->name('tenders.close');
    
    // Applications
    Route::get('/applications', [TenderOwnerApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{id}', [TenderOwnerApplicationController::class, 'show'])->name('applications.show');
    Route::post('/applications/{id}/shortlist', [TenderOwnerApplicationController::class, 'shortlist'])->name('applications.shortlist');
    Route::post('/applications/{id}/reject', [TenderOwnerApplicationController::class, 'reject'])->name('applications.reject');
    Route::get('/applications/{id}/award', [TenderOwnerApplicationController::class, 'awardForm'])->name('applications.award-form');
    Route::post('/applications/{id}/award', [TenderOwnerApplicationController::class, 'award'])->name('applications.award');
    Route::get('/applications/stats', [TenderOwnerApplicationController::class, 'getStats'])->name('applications.stats');
    
    // Contracts — full management for tender-owner
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{id}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
    Route::post('/contracts/{id}/extend', [ContractController::class, 'extend'])->name('contracts.extend');
    Route::post('/contracts/{id}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');
    Route::get('/contracts/{id}/work-order', [WorkOrderController::class, 'showForm'])->name('contracts.work-order');
    Route::get('/contracts/{id}/order-pdf', [ContractController::class, 'downloadOrderPdf'])->name('contracts.order-pdf');
    Route::post('/contracts/{id}/work-order/download', [WorkOrderController::class, 'downloadPdf'])->name('contracts.work-order.download');
    Route::post('/contracts/{id}/work-order/email', [WorkOrderController::class, 'sendEmail'])->name('contracts.work-order.email');
    Route::post('/contracts/{id}/supporting-docs', [ContractController::class, 'uploadSupportingDocs'])->name('contracts.supporting-docs.upload');
    Route::delete('/contracts/{id}/supporting-docs', [ContractController::class, 'deleteSupportingDoc'])->name('contracts.supporting-docs.delete');
    
    // Documents
    Route::delete('/tenders/documents/{id}', [TenderController::class, 'ownerDeleteDocument'])->name('tenders.delete-document');

    // Category vendors (for private tender vendor picker)
    Route::get('/categories/{id}/vendors', [CategoryController::class, 'vendorsJson'])->name('categories.vendors-json');

    // Committee
    Route::get('/tenders/{id}/committee', [TenderOwnerController::class, 'showCommittee'])->name('committee');
    Route::post('/tenders/{id}/committee/add', [TenderOwnerController::class, 'addCommitteeMember'])->name('committee.add');
    Route::post('/tenders/{id}/committee/create-user', [TenderOwnerController::class, 'createAndAddCommitteeMember'])->name('committee.create-user');
    Route::delete('/tenders/{tenderId}/committee/remove/{userId}', [TenderOwnerController::class, 'removeCommitteeMember'])->name('committee.remove');

    // Eligibility Failures
    Route::get('/eligibility-failures', [\App\Http\Controllers\EligibilityFailureController::class, 'ownerIndex'])->name('eligibility-failures');
    Route::get('/eligibility-failures/data', [\App\Http\Controllers\EligibilityFailureController::class, 'ownerData'])->name('eligibility-failures.data');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
});

// ==================== VENDOR ROUTES ====================
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [VendorController::class, 'dashboard'])->name('dashboard');
    Route::get('/stats', [VendorController::class, 'getStats'])->name('stats');
    
    // Tender Management
    Route::get('/tenders', [VendorController::class, 'tenders'])->name('tenders');
    Route::get('/tenders/data', [VendorController::class, 'getTendersData'])->name('tenders.data');
    Route::get('/tenders/{id}', [VendorController::class, 'tenderDetails'])->name('tenders.show');
    Route::get('/tenders/{id}/apply', [VendorController::class, 'showApplicationForm'])->name('tenders.apply');
    Route::post('/tenders/{id}/check-eligibility', [VendorController::class, 'checkEligibilitySubmit'])->name('tenders.check-eligibility');
    Route::post('/tenders/{id}/apply', [VendorController::class, 'submitApplication'])->name('tenders.apply.submit');
    Route::get('/tenders/{id}/download/{documentId}', [VendorController::class, 'downloadTenderDocument'])->name('tenders.download');
    
    // Application Management
    Route::get('/applications', [VendorController::class, 'applications'])->name('applications');
    Route::get('/applications/data', [VendorController::class, 'getApplicationsData'])->name('applications.data');
    Route::get('/applications/{id}', [VendorController::class, 'applicationDetails'])->name('applications.show');
    Route::post('/applications/{id}/withdraw', [VendorController::class, 'withdrawApplication'])->name('applications.withdraw');
    Route::get('/applications/{id}/download/{documentIndex}', [VendorController::class, 'downloadDocument'])->name('applications.download');
    
    // Contract Management
    Route::get('/contracts', [VendorController::class, 'contracts'])->name('contracts');
    Route::get('/contracts/data', [VendorController::class, 'getContractsData'])->name('contracts.data');
    Route::get('/contracts/{id}', [VendorController::class, 'contractDetails'])->name('contracts.show');
    Route::post('/contracts/{id}/sign', [VendorController::class, 'signContract'])->name('contracts.sign');
    Route::get('/contracts/{id}/download', [VendorController::class, 'downloadContract'])->name('contracts.download');
    
    // Profile Management
    Route::get('/profile', [VendorController::class, 'profile'])->name('profile');
    Route::put('/profile', [VendorController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/bank-details', [VendorController::class, 'updateBankDetails'])->name('profile.bank-details');
    Route::post('/profile/upload-document', [VendorController::class, 'uploadDocument'])->name('profile.upload-document');
    Route::delete('/profile/delete-document/{type}', [VendorController::class, 'deleteDocument'])->name('profile.delete-document');
    Route::get('/profile/download-document/{type}', [VendorController::class, 'downloadProfileDocument'])->name('profile.download-document');
    
    // Notifications
    Route::get('/notifications', [VendorController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{id}/read', [VendorController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [VendorController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
});

// ==================== COMMITTEE MEMBER ROUTES ====================
Route::middleware(['auth', 'role:committee-member'])->prefix('committee')->name('committee.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\CommitteeController::class, 'dashboard'])->name('dashboard');
    Route::get('/tenders', [\App\Http\Controllers\CommitteeController::class, 'tenders'])->name('tenders');
    Route::get('/applications', [\App\Http\Controllers\CommitteeController::class, 'applications'])->name('applications');
    Route::get('/applications/{id}', [\App\Http\Controllers\CommitteeController::class, 'showApplication'])->name('applications.show');
});

// ==================== COMMON PROFILE ROUTES ====================
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [App\Http\Controllers\ProfileController::class, 'index'])->name('index');
    Route::put('/update', [App\Http\Controllers\ProfileController::class, 'update'])->name('update');
    Route::put('/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password');
    Route::post('/avatar', [App\Http\Controllers\ProfileController::class, 'uploadAvatar'])->name('avatar');
    Route::delete('/avatar', [App\Http\Controllers\ProfileController::class, 'deleteAvatar'])->name('avatar.delete');
});

// ==================== TEST ROUTES ====================
Route::get('/force-login/{email}', function ($email) {
    $user = App\Models\User::where('email', $email)->first();
    if ($user) {
        Auth::login($user);
        session()->regenerate();
        
        if ($user->hasRole('super-admin')) {
            return redirect('/super-admin/dashboard');
        }
        if ($user->hasRole('admin')) {
            return redirect('/admin/dashboard');
        }
        if ($user->hasRole('tender-owner')) {
            return redirect('/tender-owner/dashboard');
        }
        if ($user->hasRole('vendor')) {
            return redirect('/vendor/dashboard');
        }
    }
    return "User not found: " . $email;
});

Route::get('/check-login', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return [
            'status' => 'Logged in',
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'vendor_status' => $user->vendor ? $user->vendor->status : null,
        ];
    }
    return ['status' => 'Not logged in'];
});

Route::get('/debug-session', function () {
    return [
        'session_id' => session()->getId(),
        'session_driver' => config('session.driver'),
        'auth_check' => auth()->check(),
        'user' => auth()->user() ? auth()->user()->email : null,
    ];
});

// ==================== PUBLIC TENDER API ====================
// No authentication — consumed by external website (vmrfdu.edu.in)
Route::prefix('api/public')->name('api.public.')->middleware('throttle:120,1')->group(function () {
    Route::get('/tenders',                  [\App\Http\Controllers\PublicTenderApiController::class, 'index'])->name('tenders');
    Route::get('/tenders/{id}',             [\App\Http\Controllers\PublicTenderApiController::class, 'show'])->name('tenders.show');
    Route::get('/university/tenders',       [\App\Http\Controllers\PublicTenderApiController::class, 'universityTenders'])->name('university.tenders');
    Route::options('/tenders',              fn() => response('', 204)->header('Access-Control-Allow-Origin', '*'));
    Route::options('/tenders/{id}',         fn() => response('', 204)->header('Access-Control-Allow-Origin', '*'));
    Route::options('/university/tenders',   fn() => response('', 204)->header('Access-Control-Allow-Origin', '*'));
});

// ==================== FALLBACK ====================
Route::fallback(function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            return redirect('/super-admin/dashboard');
        }
        if ($user->hasRole('admin')) {
            return redirect('/admin/dashboard');
        }
        if ($user->hasRole('tender-owner')) {
            return redirect('/tender-owner/dashboard');
        }
        if ($user->hasRole('vendor')) {
            return redirect('/vendor/dashboard');
        }
    }
    return redirect('/login');
});

// Direct file access route
Route::get('/documents/{id}/{filename}', function($id, $filename) {
    $document = App\Models\TenderDocument::findOrFail($id);
    $user = auth()->user();
    
    // Check permission
    if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
        if ($user->hasRole('tender-owner') && $document->tender->owner_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }
        if ($user->hasRole('vendor') && $document->tender->owner_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }
    }
    
    $path = storage_path('app/public/' . $document->file_path);
    
    if (!file_exists($path)) {
        abort(404, 'File not found.');
    }
    
    return response()->file($path);
})->name('documents.view');

Route::get('/test-storage', function() {
    $testPath = storage_path('app/public');
    
    return [
        'storage_path' => $testPath,
        'storage_exists' => is_dir($testPath),
        'storage_writable' => is_writable($testPath),
        'public_storage_exists' => is_dir(public_path('storage')),
        'public_storage_link' => is_link(public_path('storage')),
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    ];
}); 