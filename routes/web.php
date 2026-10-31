<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeletedContentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhitelistController;
use App\Http\Controllers\Channels\ChannelController;
use App\Http\Controllers\Channels\MessageController;
use App\Http\Controllers\Channels\MuteController;
use App\Http\Controllers\Clubs\ClubController;
use App\Http\Controllers\Clubs\ClubMembershipController;
use App\Http\Controllers\Clubs\EventController;
use App\Http\Controllers\Exams\ExamController;
use App\Http\Controllers\Exams\MaterialController;
use App\Http\Controllers\Moderation\ReportController as ModerationReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Questions\AnswerController;
use App\Http\Controllers\Questions\QuestionController;
use App\Http\Controllers\Questions\VoteController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/styleguide', function (Request $request) {
        abort_unless(app()->environment('local') || $request->user()->isAdmin(), 403);

        return Inertia::render('Styleguide');
    })->name('styleguide');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Exams & materials
    Route::resource('exams', ExamController::class);
    Route::post('/exams/{exam}/materials', [MaterialController::class, 'store'])
        ->middleware('throttle:uploads')->name('exams.materials.store');
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

    // Clubs & events (teachers: read-only, enforced by ClubPolicy/EventPolicy)
    Route::resource('clubs', ClubController::class);
    Route::post('/clubs/{club}/membership', [ClubMembershipController::class, 'store'])->name('clubs.join');
    Route::delete('/clubs/{club}/membership', [ClubMembershipController::class, 'destroy'])->name('clubs.leave');
    Route::resource('events', EventController::class);
    Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');
    Route::delete('/events/{event}/rsvp', [EventController::class, 'cancelRsvp'])->name('events.rsvp.cancel');

    // Q&A, channels and reports: student spaces, closed to teachers.
    Route::middleware('no-teachers')->group(function () {
        Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
        Route::get('/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
        Route::get('/channels/{channel}/messages', [MessageController::class, 'index'])->name('channels.messages.index');
        Route::post('/channels/{channel}/messages', [MessageController::class, 'store'])
            ->middleware('throttle:messages')->name('channels.messages.store');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
        Route::post('/channels/{channel}/mutes', [MuteController::class, 'store'])->name('channels.mutes.store');
        Route::delete('/mutes/{channelMute}', [MuteController::class, 'destroy'])->name('mutes.destroy');

        Route::post('/questions', [QuestionController::class, 'store'])->middleware('throttle:questions')->name('questions.store');
        Route::resource('questions', QuestionController::class)->except('store');
        Route::post('/questions/{question}/answers', [AnswerController::class, 'store'])
            ->middleware('throttle:answers')->name('questions.answers.store');
        Route::post('/questions/{question}/vote', [VoteController::class, 'question'])->middleware('throttle:votes')->name('questions.vote');
        Route::patch('/answers/{answer}', [AnswerController::class, 'update'])->name('answers.update');
        Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
        Route::post('/answers/{answer}/accept', [AnswerController::class, 'accept'])->name('answers.accept');
        Route::post('/answers/{answer}/vote', [VoteController::class, 'answer'])->middleware('throttle:votes')->name('answers.vote');
        Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:reports')->name('reports.store');
    });
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    Route::get('/whitelist', [WhitelistController::class, 'index'])->name('whitelist.index');
    Route::post('/whitelist', [WhitelistController::class, 'store'])->name('whitelist.store');
    Route::delete('/whitelist/{allowedStudent}', [WhitelistController::class, 'destroy'])->name('whitelist.destroy');
    Route::get('/deleted', [DeletedContentController::class, 'index'])->name('deleted.index');
    Route::post('/deleted/{type}/{id}/restore', [DeletedContentController::class, 'restore'])
        ->whereNumber('id')->name('deleted.restore');
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/students/import', [StudentImportController::class, 'create'])->name('students.import');
    Route::post('/students/import', [StudentImportController::class, 'store'])->name('students.import.store');
});

Route::middleware(['auth', 'verified', 'role:admin,moderator'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/reports', [ModerationReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/{report}/dismiss', [ModerationReportController::class, 'dismiss'])->name('reports.dismiss');
    Route::post('/reports/{report}/resolve', [ModerationReportController::class, 'resolve'])->name('reports.resolve');
});

require __DIR__.'/auth.php';
