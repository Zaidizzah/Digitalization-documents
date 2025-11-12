<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\DocumentTypeActionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;

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
    /*
    |--------------------------------------------------------------------------
    | Users Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('{id}', [UserController::class, 'get__user_data'])->name('users.data.get');
    });

    Route::group(['prefix' => 'documents'], function () {
        /*
        |--------------------------------------------------------------------------
        | Schema Routes
        |--------------------------------------------------------------------------
        */
        Route::post('schema/save', [DocumentTypeController::class, 'save_schema'])->name('documents.schema.save'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('schema/load/', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.root.load'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('schema/status/get', [DocumentTypeController::class, 'get__status_saved_schema'])->name('documents.schema.status.get'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('{name}/schema/load/{id?}', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.load'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        // Get schema attribute columns
        Route::get('{name}/schema/columns', [DocumentTypeController::class, 'get__schema_attribute_columns'])->name('documents.schema.columns'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route

        /*
        |--------------------------------------------------------------------------
        | Files Routes
        |--------------------------------------------------------------------------
        */
        Route::post('{name}/recognize', [DocumentTypeActionController::class, 'recognize_file_client'])->name('documents.data.recognize'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('files', [FileController::class, 'index'])->name('documents.files.index.get'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('{name}/files', [FileController::class, 'index'])->where('name', '^(?!files$).*')->name('documents.files.index.get.named'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::get('folders', [FileController::class, '__get_folders'])->name('documents.folders.get');
        // Get hashing API token for OCR API
        Route::get('{name}/ocr-space/get-hashing-token', [DocumentTypeActionController::class, 'get__api_hashing_token'])->name('ocr-space.token.get');
    });

    /*
    |--------------------------------------------------------------------------
    | Settings/Userguides Routes
    |-------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'settings/user-guide'], function () {
        Route::post('upload', [SettingController::class, 'upload'])->name('settings.upload');
        Route::get('get', [SettingController::class, '__get_user_guide_create_edit__tree_items'])->name('userguides.get');
    });

    /*
    |--------------------------------------------------------------------------
    | Userguides Routes
    |-------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'userguides'], function () {
        Route::get('content/{id}', [SettingController::class, '__get_user_guide_content'])->name('userguides.content');
    });

    Route::group(['prefix' => 'documents/files'], function () {
        /*
        |--------------------------------------------------------------------------
        | Upload files Routes
        |--------------------------------------------------------------------------
        */
        Route::post('upload', [FileController::class, 'upload'])->name('documents.files.root.upload'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        Route::post('{name}/upload', [FileController::class, 'upload'])->name('documents.files.upload'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
    });
});
