<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\DocumentTypeActionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum', 'role:Admin')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::group(['prefix' => 'documents'], function () {
        // Schema routes
        Route::post('schema/save', [DocumentTypeController::class, 'save_schema'])->name('documents.schema.save'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('schema/load/', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.root.load'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('{name}/schema/load/{id?}', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.load'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route

        // Get schema attribute columns
        Route::get('{name}/schema/columns', [DocumentTypeController::class, 'get_schema_attribute_columns'])->name('documents.schema.columns'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route

        // Get result of file content in create action
        Route::post('{name}/recognize', [DocumentTypeActionController::class, 'recognize_file_client'])->name('documents.data.recognize'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route

        // Get more files data
        Route::get('{name?}/files', [DocumentTypeActionController::class, 'index'])->name('documents.files.index.get'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route

        // Get hashing API token for OCR API
        Route::get('{name}/ocr-space/get-hashing-token', [DocumentTypeActionController::class, 'get__api_hashing_token'])->name('ocr-space.token.get');
    });

    Route::group(['prefix' => 'documents/files'], function () {
        // uploading files routes
        Route::post('upload', [FileController::class, 'upload'])->name('documents.files.root.upload'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::post('{name}/upload', [FileController::class, 'upload'])->name('documents.files.upload'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
    });
});
