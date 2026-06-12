<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Prompts\Form as AdminPromptForm;
use App\Livewire\Admin\Prompts\Index as AdminPromptIndex;
use App\Livewire\Admin\Tags\Form as AdminTagForm;
use App\Livewire\Admin\Tags\Index as AdminTagIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Home;
use App\Livewire\Prompts\Latest;
use App\Livewire\Prompts\MostViewed;
use App\Livewire\Prompts\Show as PromptShow;
use App\Livewire\Search;
use App\Livewire\Tags\Show as TagShow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public read-side
Route::get('/', Home::class)->name('home');

Route::get('/prompts/latest', Latest::class)->name('prompts.latest');
Route::get('/prompts/most-viewed', MostViewed::class)->name('prompts.most-viewed');
Route::get('/prompts/{prompt:slug}', PromptShow::class)->name('prompts.show');

Route::get('/tags/{tag:slug}', TagShow::class)->name('tags.show');

Route::get('/search', Search::class)->name('search');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

// Admin
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboard::class)->name('dashboard');

        Route::get('/prompts', AdminPromptIndex::class)->name('prompts.index');
        Route::get('/prompts/create', AdminPromptForm::class)->name('prompts.create');
        Route::get('/prompts/{prompt}/edit', AdminPromptForm::class)->name('prompts.edit');

        Route::get('/tags', AdminTagIndex::class)->name('tags.index');
        Route::get('/tags/create', AdminTagForm::class)->name('tags.create');
        Route::get('/tags/{tag}/edit', AdminTagForm::class)->name('tags.edit');
    });
