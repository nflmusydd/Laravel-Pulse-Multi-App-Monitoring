<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboard1\UserController;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Jobs\TesSlowJob;
use App\Jobs\TesSlowJob2;
use App\Jobs\TesSlowJob3;
use App\Jobs\FailingJob;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome'); 
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard1', function () {
        return view('dashboard1');
    })->name('dashboard1');

    Route::get('/users-data', [UserController::class, 'getUsers'])->name('users.data');
    Route::get('/users/all', [UserController::class, 'getAllUsers'])->name('users.all');
    

    // Trigger data cache
    Route::get('/cache1', function(){
        $value = Cache::remember('users', 5, function(){
            return User::all();
        });
        return $value;
    });
    Route::get('/cache2', function(){
        $value = Cache::remember('usersCount', 5, function(){
            return User::count();
        });
        return $value;
    });
    
});

// Slow Job (run queue to trigger queue graph)
Route::get('/tes-slow-job', function () {       
    TesSlowJob::dispatch();
    return 'Slow job telah dikirim ke queue.';
});
Route::get('/tes-slow-job2', function () {
    TesSlowJob2::dispatch();
    return '2. Slow job telah dikirim ke queue.';
});
Route::get('/tes-slow-job3', function () {
    TesSlowJob3::dispatch();
    return '3. Slow job telah dikirim ke queue.';
});

// failed Queue
Route::get('/failing-job', function () {       
    FailingJob::dispatch();
    return 'Failing job dispatched.';
});

//Exception
Route::get('/trigger-error-pulse1', function () {       
    throw new \Exception("Contoh error untuk Pulse");
});
Route::get('/trigger-error-pulse2', function () {
    $a = 5 / 0;
});

// Slow Requests & Slow Outgoing Request
Route::get('/fakelogin1', function(){      
    $users = User::All();
    $baseURL = config('app.url');

    // foreach($users as $user){
        Http::get("127.0.0.1:8000/fakelogin2/1");
    // }
    return 'Request complete!';
});
Route::get('fakelogin2/{user}', function(User $user){
    $baseURL = config('app.url');
    auth()->login($user);

    Http::get("127.0.0.1:8000");

    auth()->logout();
});
