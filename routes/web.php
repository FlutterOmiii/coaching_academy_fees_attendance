<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CoachController;
use App\Http\Controllers\Admin\CoachSalaryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\PersonalExpenseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TrainingSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.login'));

// Guardian-facing admission form, opened from the WhatsApp welcome message.
// The signed URL is unguessable and needs no login; it shows only this one
// student's admission document.
Route::get('admission/{student}', [StudentController::class, 'admissionFormPublic'])
    ->middleware('signed')->name('admission.view');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Every route carries the exact ability it needs. Abilities are per-verb, so
| read access can never imply write or delete: a coach holding students.view
| cannot reach students.destroy. The ability matrix lives on App\Models\Admin.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/signin', [AuthController::class, 'signin'])->name('signin');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->middleware('ability:dashboard.view')
            ->name('dashboard');

        // ---------------------------------------------------------- Students
        Route::get('students', [StudentController::class, 'index'])
            ->middleware('ability:students.view')->name('students.index');
        Route::get('students/export', [StudentController::class, 'export'])
            ->middleware('ability:students.view')->name('students.export');
        Route::get('students/create', [StudentController::class, 'create'])
            ->middleware('ability:students.create')->name('students.create');
        Route::post('students', [StudentController::class, 'store'])
            ->middleware('ability:students.create')->name('students.store');
        // Must sit above students/{student} so "birthdays" is not taken as an id.
        Route::get('students/birthdays', [StudentController::class, 'birthdays'])
            ->middleware('ability:birthdays.view')->name('students.birthdays');
        Route::get('students/{student}', [StudentController::class, 'show'])
            ->middleware('ability:students.view')->name('students.show');
        Route::get('students/{student}/admission-form', [StudentController::class, 'admissionForm'])
            ->middleware('ability:students.view')->name('students.admission');
        Route::get('students/{student}/edit', [StudentController::class, 'edit'])
            ->middleware('ability:students.edit')->name('students.edit');
        Route::put('students/{student}', [StudentController::class, 'update'])
            ->middleware('ability:students.edit')->name('students.update');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])
            ->middleware('ability:students.delete')->name('students.destroy');
        Route::patch('students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])
            ->middleware('ability:students.edit')->name('students.toggle-status');
        Route::patch('students/{student}/admission', [StudentController::class, 'updateAdmission'])
            ->middleware('ability:students.edit')->name('students.admission');
        Route::post('students/{student}/documents', [StudentController::class, 'storeDocument'])
            ->middleware('ability:students.edit')->name('students.documents.store');
        Route::delete('students/{student}/documents/{document}', [StudentController::class, 'destroyDocument'])
            ->middleware('ability:students.delete')->name('students.documents.destroy');
        Route::post('students/{student}/transfer', [StudentController::class, 'transfer'])
            ->middleware('ability:batches.edit')->name('students.transfer');

        // ----------------------------------------------------------- Coaches
        Route::get('coaches', [CoachController::class, 'index'])
            ->middleware('ability:coaches.view')->name('coaches.index');
        Route::get('coaches/create', [CoachController::class, 'create'])
            ->middleware('ability:coaches.create')->name('coaches.create');
        Route::post('coaches', [CoachController::class, 'store'])
            ->middleware('ability:coaches.create')->name('coaches.store');
        Route::get('coaches/{coach}', [CoachController::class, 'show'])
            ->middleware('ability:coaches.view')->name('coaches.show');
        Route::get('coaches/{coach}/edit', [CoachController::class, 'edit'])
            ->middleware('ability:coaches.edit')->name('coaches.edit');
        Route::put('coaches/{coach}', [CoachController::class, 'update'])
            ->middleware('ability:coaches.edit')->name('coaches.update');
        Route::delete('coaches/{coach}', [CoachController::class, 'destroy'])
            ->middleware('ability:coaches.delete')->name('coaches.destroy');
        Route::patch('coaches/{coach}/toggle-status', [CoachController::class, 'toggleStatus'])
            ->middleware('ability:coaches.edit')->name('coaches.toggle-status');
        Route::put('coaches/{coach}/availability', [CoachController::class, 'updateAvailability'])
            ->middleware('ability:coaches.edit')->name('coaches.availability');

        // ----------------------------------------------------------- Batches
        Route::get('batches', [BatchController::class, 'index'])
            ->middleware('ability:batches.view')->name('batches.index');
        Route::get('batches/create', [BatchController::class, 'create'])
            ->middleware('ability:batches.create')->name('batches.create');
        Route::post('batches', [BatchController::class, 'store'])
            ->middleware('ability:batches.create')->name('batches.store');
        Route::get('batches/{batch}', [BatchController::class, 'show'])
            ->middleware('ability:batches.view')->name('batches.show');
        Route::get('batches/{batch}/edit', [BatchController::class, 'edit'])
            ->middleware('ability:batches.edit')->name('batches.edit');
        Route::put('batches/{batch}', [BatchController::class, 'update'])
            ->middleware('ability:batches.edit')->name('batches.update');
        Route::delete('batches/{batch}', [BatchController::class, 'destroy'])
            ->middleware('ability:batches.delete')->name('batches.destroy');
        Route::patch('batches/{batch}/toggle-status', [BatchController::class, 'toggleStatus'])
            ->middleware('ability:batches.edit')->name('batches.toggle-status');
        Route::post('batches/{batch}/students', [BatchController::class, 'addStudent'])
            ->middleware('ability:batches.edit')->name('batches.students.add');
        Route::delete('batches/{batch}/students/{student}', [BatchController::class, 'removeStudent'])
            ->middleware('ability:batches.edit')->name('batches.students.remove');

        // -------------------------------------------------------- Attendance
        Route::middleware('ability:attendance.view')->group(function () {
            Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::get('attendance/daily', [AttendanceController::class, 'daily'])->name('attendance.daily');
            Route::get('attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
            Route::get('attendance/coaches', [AttendanceController::class, 'coaches'])->name('attendance.coaches');
        });
        Route::middleware('ability:attendance.manage')->group(function () {
            Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
            Route::post('attendance/coaches', [AttendanceController::class, 'storeCoaches'])
                ->name('attendance.coaches.store');
        });

        // Leave Requests module removed from the panel (2026-08-02). Controller
        // and table remain; re-add routes here to restore.

        // --------------------------------------------------- Training sessions
        Route::get('training', [TrainingSessionController::class, 'index'])
            ->middleware('ability:training.view')->name('training.index');
        Route::get('training/create', [TrainingSessionController::class, 'create'])
            ->middleware('ability:training.manage')->name('training.create');
        Route::post('training', [TrainingSessionController::class, 'store'])
            ->middleware('ability:training.manage')->name('training.store');
        Route::get('training/{session}', [TrainingSessionController::class, 'show'])
            ->middleware('ability:training.view')->name('training.show');
        Route::get('training/{session}/edit', [TrainingSessionController::class, 'edit'])
            ->middleware('ability:training.manage')->name('training.edit');
        Route::put('training/{session}', [TrainingSessionController::class, 'update'])
            ->middleware('ability:training.manage')->name('training.update');
        Route::delete('training/{session}', [TrainingSessionController::class, 'destroy'])
            ->middleware('ability:training.delete')->name('training.destroy');

        // -------------------------------------------------------------- Fees
        // Financial data. Coaches hold none of these abilities.
        Route::middleware('ability:fees.view')->group(function () {
            Route::get('fees', [FeeController::class, 'index'])->name('fees.index');
            Route::get('fees/pending', [FeeController::class, 'pending'])->name('fees.pending');
            Route::get('fees/reminders', [FeeController::class, 'reminders'])
                ->middleware('ability:fees.manage')->name('fees.reminders');
            Route::get('fees/structures', [FeeController::class, 'structures'])->name('fees.structures');
            Route::get('fees/history/{student}', [FeeController::class, 'history'])->name('fees.history');
            Route::get('fees/invoices', [FeeController::class, 'invoices'])->name('fees.invoices');
            Route::get('fees/invoices/create', [FeeController::class, 'createInvoice'])->name('fees.invoices.create');
            Route::get('fees/invoices/{invoice}', [FeeController::class, 'showInvoice'])->name('fees.invoices.show');
            Route::get('fees/receipts/{payment}', [FeeController::class, 'receipt'])->name('fees.receipt');
        });

        Route::middleware('ability:fees.manage')->group(function () {
            Route::post('fees/structures', [FeeController::class, 'storeStructure'])->name('fees.structures.store');
            Route::put('fees/structures/{structure}', [FeeController::class, 'updateStructure'])
                ->name('fees.structures.update');
            Route::put('fees/settings', [FeeController::class, 'updateSettings'])->name('fees.settings');
            Route::post('fees/invoices', [FeeController::class, 'storeInvoice'])->name('fees.invoices.store');
            Route::post('fees/invoices/generate', [FeeController::class, 'generateMonthly'])
                ->name('fees.invoices.generate');
            Route::post('fees/invoices/{invoice}/pay', [FeeController::class, 'collect'])->name('fees.collect');
            Route::post('fees/invoices/{invoice}/remind', [FeeController::class, 'remind'])->name('fees.remind');

            // One-click collection straight from the student row.
            Route::post('fees/collect/{student}', [FeeController::class, 'collectForStudent'])
                ->name('fees.collect-student');
            Route::post('fees/remind-all', [FeeController::class, 'remindAll'])->name('fees.remind-all');
            Route::post('fees/remind-selected', [FeeController::class, 'remindSelected'])
                ->name('fees.remind-selected');
        });

        Route::middleware('ability:fees.delete')->group(function () {
            Route::delete('fees/structures/{structure}', [FeeController::class, 'destroyStructure'])
                ->name('fees.structures.destroy');
            Route::delete('fees/invoices/{invoice}', [FeeController::class, 'destroyInvoice'])
                ->name('fees.invoices.destroy');
        });

        // ---------------------------------------------------------- Expenses
        // Business-expense tracker. Financial data — coaches hold no abilities.
        Route::middleware('ability:expenses.view')->group(function () {
            Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('expenses/list', [ExpenseController::class, 'list'])->name('expenses.list');
            Route::get('expenses/categories', [ExpenseController::class, 'categories'])->name('expenses.categories');

            // Coach salaries live inside the expense book (payments are Expenses
            // tagged with coach_id + salary_month), so they share its abilities.
            Route::get('expenses/salaries', [CoachSalaryController::class, 'index'])
                ->name('expenses.salaries');
            Route::get('expenses/salaries/{coach}', [CoachSalaryController::class, 'history'])
                ->name('expenses.salaries.history');
        });

        Route::middleware('ability:expenses.manage')->group(function () {
            Route::post('expenses/salaries', [CoachSalaryController::class, 'store'])
                ->name('expenses.salaries.store');
            Route::put('expenses/salaries/{coach}/default', [CoachSalaryController::class, 'updateDefault'])
                ->name('expenses.salaries.default');
            Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
            Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');

            Route::post('expenses/categories', [ExpenseController::class, 'storeCategory'])
                ->name('expenses.categories.store');
            Route::post('expenses/categories/quick', [ExpenseController::class, 'quickCategory'])
                ->name('expenses.categories.quick');
            Route::put('expenses/categories/{category}', [ExpenseController::class, 'updateCategory'])
                ->name('expenses.categories.update');
        });

        Route::middleware('ability:expenses.delete')->group(function () {
            Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
            Route::delete('expenses/categories/{category}', [ExpenseController::class, 'destroyCategory'])
                ->name('expenses.categories.destroy');
        });

        // ------------------------------------------------------ My Expenses
        // The owner's private ledger with its own analytics page. personal.manage
        // is owner-only (no other role holds it) and fully separate from the books.
        Route::middleware('ability:personal.manage')->group(function () {
            Route::get('personal-expenses', [PersonalExpenseController::class, 'index'])
                ->name('personal.index');
            Route::post('personal-expenses/categories/quick', [PersonalExpenseController::class, 'quickCategory'])
                ->name('personal.categories.quick');
            Route::post('personal-expenses', [PersonalExpenseController::class, 'store'])
                ->name('personal.store');
            Route::put('personal-expenses/{personalExpense}', [PersonalExpenseController::class, 'update'])
                ->name('personal.update');
            Route::delete('personal-expenses/{personalExpense}', [PersonalExpenseController::class, 'destroy'])
                ->name('personal.destroy');
        });

        // Matches, Tournaments, Teams and Performance modules removed from the
        // panel (2026-08-02). Controllers, models and tables remain intact;
        // re-add their routes here to restore.

        // ----------------------------------------------------------- Settings
        // Academy branding (name, logo, currency, WhatsApp code) + own profile.
        Route::middleware('ability:settings.manage')->group(function () {
            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
            Route::put('settings/profile', [SettingController::class, 'updateProfile'])
                ->name('settings.profile');
        });

        // ------------------------------------------------------------ Reports
        Route::middleware('ability:reports.view')->group(function () {
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{type}', [ReportController::class, 'generate'])->name('reports.generate');
        });

        // ----------------------------------------------------------- Calendar
        Route::get('calendar', [CalendarController::class, 'index'])
            ->middleware('ability:calendar.view')->name('calendar.index');
        Route::post('calendar', [CalendarController::class, 'store'])
            ->middleware('ability:calendar.manage')->name('calendar.store');
        Route::put('calendar/{event}', [CalendarController::class, 'update'])
            ->middleware('ability:calendar.manage')->name('calendar.update');
        Route::delete('calendar/{event}', [CalendarController::class, 'destroy'])
            ->middleware('ability:calendar.delete')->name('calendar.destroy');
    });
});
