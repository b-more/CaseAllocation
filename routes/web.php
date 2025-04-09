<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InquiryFileController;
use App\Http\Controllers\InvestigatorStatsController;
use App\Http\Controllers\WelfareController;
use App\Http\Controllers\ReportController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/inquiry-file/export-pdf/{id}', [InquiryFileController::class, 'exportPdf'])
    ->middleware(['auth'])
    ->name('inquiry-file.export-pdf');

Route::get('/investigator/export-pdf/{id}', [\App\Http\Controllers\InvestigatorStatsController::class, 'exportPdf'])
    ->middleware(['auth'])
    ->name('investigator.export-pdf');

Route::get('/investigator/export-bulk-pdf', [\App\Http\Controllers\InvestigatorStatsController::class, 'exportBulkPdf'])
    ->middleware(['auth'])
    ->name('investigator.export-bulk-pdf');

Route::get('/investigator/export-all-pdf', [\App\Http\Controllers\InvestigatorStatsController::class, 'exportAllPdf'])
    ->middleware(['auth'])
    ->name('investigator.export-all-pdf');

Route::get('/welfare/history/{user}', [\App\Http\Controllers\WelfareController::class, 'history'])
    ->middleware(['auth'])
    ->name('welfare.history');

Route::get('/welfare/export-report', [\App\Http\Controllers\WelfareController::class, 'exportReport'])
    ->middleware(['auth'])
    ->name('welfare.export-report');

Route::get('/reports/generate-pdf', [ReportController::class, 'generatePdf'])
->name('reports.generate-pdf');
