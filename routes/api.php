<?php

use App\Http\Controllers\Api\EnumController;
use App\Http\Controllers\Api\FileController;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::post('clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('route:cache');
    Artisan::call('view:clear');

});
Route::get('clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('route:cache');
    Artisan::call('view:clear');

});

Route::post('reload', function () {
    Artisan::call('migrate:refresh --seed');
});

Route::middleware(['jwt.cookies'])->group(function () {


    Route::get('enums/{enum}', [EnumController::class, 'index']);
    Route::get('report_template_files', [FileController::class, 'report_template_files']);

    Route::get('/cookie-test', function () {
        return response()->json(['cookie' => request()->cookie('token')]);
    });
});
