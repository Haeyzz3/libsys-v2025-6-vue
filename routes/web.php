<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalResourceController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\GradSchoolStudentController;
use App\Http\Controllers\GraduateStudentController;
use App\Http\Controllers\LibraryVisitController;
use App\Http\Controllers\PeriodicalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RecordReportsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TopFiguresStatisticsController;
use App\Http\Controllers\UndergraduateStudentController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ThesisController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserReportsController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [WelcomeController::class, 'index'])->name('home');
Route::get('/logger/create', [LibraryVisitController::class, 'create'])->name('logger.create');
Route::post('/', [LibraryVisitController::class, 'store'])->name('logger.store');

$middleware = ['auth'];
if (app()->environment(['production', 'staging'])) {
    $middleware[] = 'verified';
}
Route::middleware($middleware)->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::get('/all', [UserController::class, 'all'])->name('users.all');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::group(['middleware' => ['can:viewAny, App\\Models\\User']], function () {
            //            Route::get('/import', [UserController::class, 'import'])->name('users.import');
            //            Route::post('/import', [UserController::class, 'importStore'])->name('users.import.store');
            Route::get('/admins',[AdminController::class, 'index'])->name('admins.index');
            Route::get('/admins/create', [AdminController::class, 'create'])->name('admins.create');
            Route::post('/admins', [AdminController::class, 'store'])->name('admins.store');
            Route::get('/admins/{id}/edit', [AdminController::class, 'edit'])->name('admins.edit');
            Route::delete('/admins/{id}', [AdminController::class, 'destroy'])->name('admins.destroy');
            Route::patch('/admins/{id}', [AdminController::class, 'update'])->name('admins.update');
            Route::delete('/admins/{id}', [AdminController::class, 'destroy'])->name('admins.destroy');
        });
        // faculty
        Route::get('/faculties', [FacultyController::class, 'index'])->name('faculties.index');
        Route::get('/faculties/create', [FacultyController::class, 'create'])->name('faculties.create');
        Route::post('/faculties', [FacultyController::class, 'store'])->name('faculties.store');
        Route::get('/faculties/{id}/edit', [FacultyController::class, 'edit'])->name('faculties.edit');
        Route::patch('/faculties/{id}', [FacultyController::class, 'update'])->name('faculties.update');
        Route::delete('/faculties/{id}', [FacultyController::class, 'destroy'])->name('faculties.destroy');
        // staff
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{id}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::patch('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
        // Undergraduate Students
        Route::get('/undergraduate', [UndergraduateStudentController::class, 'index'])->name('undergraduate.index');
        Route::get('/undergraduate/create', [UndergraduateStudentController::class, 'create'])->name('undergraduate.create');
        Route::post('/undergraduate', [UndergraduateStudentController::class, 'store'])->name('undergraduate.store');
        Route::get('/undergraduate/{id}/edit', [UndergraduateStudentController::class, 'edit'])->name('undergraduate.edit');
        Route::patch('/undergraduate/{id}', [UndergraduateStudentController::class, 'update'])->name('undergraduate.update');
        Route::delete('/undergraduate/{id}', [UndergraduateStudentController::class, 'destroy'])->name('undergraduate.destroy');
        // Graduate Students
        Route::get('/graduate', [GraduateStudentController::class, 'index'])->name('graduate.index');
        Route::get('/graduate/create', [GraduateStudentController::class, 'create'])->name('graduate.create');
        Route::post('/graduate', [GraduateStudentController::class, 'store'])->name('graduate.store');
        Route::get('/graduate/{id}/edit', [GraduateStudentController::class, 'edit'])->name('graduate.edit');
        Route::patch('/graduate/{id}', [GraduateStudentController::class, 'update'])->name('graduate.update');
        Route::delete('/graduate/{id}', [GraduateStudentController::class, 'destroy'])->name('graduate.destroy');
    });
    Route::prefix('records')->group(function () {
        Route::group(['middleware' => ['can:viewAny, App\\Models\\User']], function () {
        //            Route::get('/import', [BookController::class, 'import'])->name('books.import');
        //            Route::post('/import', [BookController::class, 'importStore'])->name('books.import.store');
        });
        // books
        Route::get('/', [RecordController::class, 'index'])->name('records.index');
        Route::get('/all', [RecordController::class, 'all'])->name('records.all');
        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/books/{id}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::patch('/books/{id}', [BookController::class, 'update'])->name('books.update');
        // multimedia
        Route::get('/multimedia', [DigitalResourceController::class, 'index'])->name('multimedia.index');
        Route::get('/multimedia/create', [DigitalResourceController::class, 'create'])->name('multimedia.create');
        Route::post('/multimedia', [DigitalResourceController::class, 'store'])->name('multimedia.store');
        Route::get('/periodicals', [PeriodicalController::class, 'index'])->name('periodicals.index');
        Route::get('/periodicals/create', [PeriodicalController::class, 'create'])->name('periodicals.create');
        Route::get('/theses', [ThesisController::class, 'index'])->name('theses.index');
    });
    Route::prefix('borrowings')->group(function () {
        Route::get('/', [BorrowingTransactionController::class, 'index'])->name('borrowings.index');
        Route::get('/active', [BorrowingTransactionController::class, 'indexActive'])->name('borrowings.active');
        Route::get('/overdue', [BorrowingTransactionController::class, 'indexOverdue'])->name('borrowings.overdue');
        Route::get('/returned', [BorrowingTransactionController::class, 'indexReturned'])->name('borrowings.returned');
        Route::get('/create', [BorrowingTransactionController::class, 'create'])->name('borrowings.create');
        Route::post('/borrow/inside', [BorrowingTransactionController::class, 'borrowInside'])->name('borrowings.borrow.inside');
        Route::post('/borrow', [BorrowingTransactionController::class, 'borrow'])->name('borrowings.borrow');
        Route::post('/return', [BorrowingTransactionController::class, 'returnBook'])->name('borrowings.return');
    });
    Route::prefix('reports')->group(function () {
        Route::prefix('users')->group(function () {
            Route::get('/barcodes', [UserReportsController::class, 'barcodes'])->name('reports.users.barcodes');
        });
        Route::prefix('records')->group(function () {
            Route::get('/books/qrcodes', [RecordReportsController::class, 'BookBarcodes'])->name('reports.records.books.barcodes');
        });
    });

    Route::prefix('statistics')->group(function () {
        Route::get('/topFigures', [TopFiguresStatisticsController::class, 'topFigures'])->name('statistics.topFigures');
        Route::get('/api/top-library-visits', [TopFiguresStatisticsController::class, 'getTopLibraryVisits'])->name('statistics.api.topLibraryVisits');
    });

        Route::prefix('logger')->group(function () {
        Route::get('/', [LibraryVisitController::class, 'index'])->name('logger.index');
        Route::get('/api/visit-card-stats', [LibraryVisitController::class, 'visitCardStats'])->name('logger.api.visitCardStats');
    });

    Route::get('/test', [TestController::class, 'index'])->name('test.index');

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
