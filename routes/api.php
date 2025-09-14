<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CostItemController;
use App\Http\Controllers\Api\StudentBillController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StudentSavingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ParentAuthController;
use App\Http\Controllers\Api\ParentPortalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\DownloadableFileController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\ClassGroupController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TeacherPortalController; // Tambahkan di atas
use App\Http\Controllers\Api\AttendanceController; // <-- Pastikan ini di-import
use App\Http\Controllers\Api\HolidayController;


// --- RUTE PUBLIK (TIDAK PERLU LOGIN) ---
Route::post('parent/login', [ParentAuthController::class, 'login']);
Route::post('login', [AuthController::class, 'login'])->name('login');
// Rute untuk melihat pengumuman & unduhan sekarang publik
Route::get('announcements', [AnnouncementController::class, 'index']);
Route::get('downloads', [DownloadableFileController::class, 'index']);


// --- RUTE YANG DILINDUNGI OTENTIKASI ---
Route::middleware('auth:sanctum')->group(function () {
    // --- AUTH & USER MANAGEMENT ---
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::apiResource('users', UserController::class)->except(['show']);
    Route::post('users/{user}/change-password', [UserController::class, 'changePassword']);
    Route::get('payments/latest-receipt-number', [PaymentController::class, 'getLatestReceiptNumber']);
// routes/api.php
Route::get('users/creatable-roles', [UserController::class, 'getCreatableRoles']);
    // --- PARENT PORTAL (BUTUH TOKEN ORANG TUA) ---
    Route::get('parent/bills', [ParentPortalController::class, 'getBills']);
    Route::get('parent/history', [ParentPortalController::class, 'getPaymentHistory']);
    Route::get('parent/attendance-summary', [ParentPortalController::class, 'getAttendanceSummary']);
    Route::get('parent/attendance-details', [ParentPortalController::class, 'getAttendanceDetails']);
    // --- SYSADMIN & ADMIN (BUTUH TOKEN ADMIN/SYSADMIN) ---
    Route::apiResource('students', StudentController::class);
    Route::post('students/promote', [StudentController::class, 'promoteStudents']);

    Route::apiResource('cost-items', CostItemController::class);
    Route::post('cost-items/{cost_item}/toggle', [CostItemController::class, 'toggleStatus']);

    Route::apiResource('student-bills', StudentBillController::class)->only(['destroy']);
    Route::get('students/{student}/bills', [StudentBillController::class, 'index']); // <-- TAMBAHKAN INI
    Route::get('bills/unpaid', [StudentBillController::class, 'getUnpaidByCostItem']);
    Route::post('bills/assign', [StudentBillController::class, 'assignBill']);

    Route::post('payments', [PaymentController::class, 'store']);
    Route::post('payments/bulk', [PaymentController::class, 'storeBulk']);
    Route::post('payments/multi-bill', [PaymentController::class, 'storeMultiBill']);
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'generateReceipt']);
    Route::get('receipts/{receipt_number}', [PaymentController::class, 'generateMultiBillReceipt']);
    // routes/api.php


    Route::get('students/{student}/savings', [StudentSavingController::class, 'index']);
    Route::post('students/{student}/savings', [StudentSavingController::class, 'store']);
    // routes/api.php
    Route::post('students/{student}/savings/withdraw-and-pay', [StudentSavingController::class, 'withdrawAndPay']);

    Route::get('dashboard/kpi', [DashboardController::class, 'getKpiData']);
    Route::get('dashboard/income-chart', [DashboardController::class, 'getIncomeChartData']);
    Route::get('dashboard/income-composition', [DashboardController::class, 'getIncomeCompositionData']);
    Route::get('dashboard/top-arrears', [DashboardController::class, 'getTopArrears']);
    Route::get('dashboard/inventory-summary', [DashboardController::class, 'getInventorySummary']);

    Route::get('reports/details/monthly-income', [ReportController::class, 'getMonthlyIncomeDetails']);
    Route::get('reports/details/arrears', [ReportController::class, 'getArrearsDetails']);
    Route::get('reports/details/active-students', [ReportController::class, 'getActiveStudents']);
    // routes/api.php
    Route::get('reports/handover', [ReportController::class, 'getHandoverReport']);
    Route::post('reports/reconcile', [ReportController::class, 'reconcileTransactions']);
    // routes/api.php
    Route::get('reports/attendance', [ReportController::class, 'getAttendanceReport']);
    // routes/api.php
    Route::get('reports/attendance/monthly', [ReportController::class, 'getMonthlyAttendanceReport']);
    // Rute untuk membuat & menghapus pengumuman/unduhan tetap dilindungi
    Route::post('announcements', [AnnouncementController::class, 'store']);
    Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy']);
    Route::post('downloads', [DownloadableFileController::class, 'store']);
    Route::delete('downloads/{downloadableFile}', [DownloadableFileController::class, 'destroy']);

    Route::apiResource('inventory', InventoryItemController::class);
    Route::apiResource('staff', StaffController::class);
    Route::apiResource('class-groups', ClassGroupController::class);
    Route::post('class-groups/{classGroup}/assign-students', [ClassGroupController::class, 'assignStudents']);

    Route::middleware('auth:sanctum')->group(function () {
    // ... existing routes ...

    // Teacher portal routes
    Route::post('teacher/profile', [TeacherPortalController::class, 'updateProfile']);
    Route::post('teacher/change-password', [TeacherPortalController::class, 'changePassword']);
    Route::post('teacher/profile/photo', [TeacherPortalController::class, 'updatePhoto']); // <- TAMBAHKAN INI
    Route::post('attendance', [AttendanceController::class, 'storeAttendance']);
    Route::get('teacher/classes', [AttendanceController::class, 'getTeacherClasses']);
    Route::get('teacher/attendance-sheet/{classGroup}', [AttendanceController::class, 'getAttendanceSheetForClass']);
    Route::get('admin/pending-attendances', [AttendanceController::class, 'getPendingAttendanceClasses']);
    Route::get('admin/attendance-sheet/{classGroup}', [AttendanceController::class, 'getAdminAttendanceSheet']);
    // routes/api.php

// Di dalam grup middleware('auth:sanctum')
    Route::apiResource('holidays', HolidayController::class)->only(['index', 'store', 'destroy']);
    Route::get('holidays/check', [HolidayController::class, 'checkDate']);
    });
});
