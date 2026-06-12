<?php

use App\Livewire\Home;
use App\Livewire\Prompts\Latest;
use App\Livewire\Prompts\MostViewed;
use App\Livewire\Prompts\Show as PromptShow;
use App\Livewire\Search;
use App\Livewire\Tags\Show as TagShow;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::get('/prompts/latest', Latest::class)->name('prompts.latest');
Route::get('/prompts/most-viewed', MostViewed::class)->name('prompts.most-viewed');
Route::get('/prompts/{prompt:slug}', PromptShow::class)->name('prompts.show');

Route::get('/tags/{tag:slug}', TagShow::class)->name('tags.show');

Route::get('/search', Search::class)->name('search');
