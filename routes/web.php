<?php

// use App\Http\Controllers\Auth\AuthController;
// use App\Http\Controllers\DashboardController;
// use Illuminate\Support\Facades\Route;

// /*
// |--------------------------------------------------------------------------
// | GUEST ROUTES — pwedeng puntahan kahit hindi naka-login
// |--------------------------------------------------------------------------
// */

// // Ipapakita nito yung auth.blade.php (login + signup, sliding panel)
// Route::get('/', [AuthController::class, 'showAuth'])->name('auth');

// Route::post('/login', [AuthController::class, 'login'])->name('login');
// Route::post('/register', [AuthController::class, 'register'])->name('register');

// // Placeholder muna para hindi mag-error yung route('password.request')
// // na tinatawag sa auth.blade.php. Palitan na lang ito pagka-gawa na natin
// // ng aktwal na Forgot Password feature.
// Route::get('/forgot-password', function () {
//     return 'Forgot Password page — TODO, gagawin pa natin ito.';
// })->name('password.request');


// /*
// |--------------------------------------------------------------------------
// | PROTECTED ROUTES — kailangan naka-login (auth middleware)
// |--------------------------------------------------------------------------
// */

// Route::middleware('auth')->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//     Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

//     // Susunod pa natin gagawin ang mga ito (CRUD controllers gamit
//     // Route::resource — awtomatiko nitong gagawin lahat ng index/create/
//     // store/edit/update/destroy routes para sa bawat module)
//     // Route::resource('passwords', PasswordController::class);
//     // Route::resource('notes', NoteController::class);
//     // Route::resource('tasks', TaskController::class);
//     // Route::resource('folders', FolderController::class);
// });






use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GUEST ROUTES — pwedeng puntahan kahit hindi naka-login
|--------------------------------------------------------------------------
*/

Route::get('/', [AuthController::class, 'showAuth'])->name('auth');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::get('/forgot-password', function () {
    return 'Forgot Password page — TODO, gagawin pa natin ito.';
})->name('password.request');


/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES — kailangan naka-login (auth middleware)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Susunod pa natin gagawin ang mga ito
    // Route::resource('passwords', PasswordController::class);
    // Route::resource('notes', NoteController::class);
    // Route::resource('tasks', TaskController::class);
    // Route::resource('folders', FolderController::class);
    // Route::post('favorites/{type}/{id}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
});
