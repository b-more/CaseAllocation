<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InquiryFileController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/inquiry-file/export-pdf/{id}', [InquiryFileController::class, 'exportPdf'])
    ->middleware(['auth'])
    ->name('inquiry-file.export-pdf');
