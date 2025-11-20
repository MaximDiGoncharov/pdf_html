<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfGeneratorController;

Route::get('/', [PdfGeneratorController::class, 'index'])->name('index');
Route::post('/upload-csv', [PdfGeneratorController::class, 'uploadCsv'])->name('upload.csv');
Route::post('/get-template-fields', [PdfGeneratorController::class, 'getTemplateFields'])->name('template.fields');
Route::post('/map-fields', [PdfGeneratorController::class, 'mapFields'])->name('map.fields');
Route::post('/generate-pdf', [PdfGeneratorController::class, 'generatePdf'])->name('generate.pdf');
Route::get('/download-pdf/{filename}', [PdfGeneratorController::class, 'downloadPdf'])->name('download.pdf');

