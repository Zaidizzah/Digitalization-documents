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

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:Admin')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Users Routes
        |--------------------------------------------------------------------------
        */
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::group(['prefix' => 'users'], function () {
            Route::get('{id}', [UserController::class, 'get__user_data'])->middleware('throttle:moderate')->name('users.data.get');
        });

        Route::group(['prefix' => 'documents'], function () {
            /*
            |--------------------------------------------------------------------------
            | Schema Routes
            |--------------------------------------------------------------------------
            */
            Route::post('schema/save', [DocumentTypeController::class, 'save_schema'])->middleware('throttle:strict')->name('documents.schema.save'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            Route::get('schema/load/', [DocumentTypeController::class, 'load_schema'])->middleware('throttle:moderate')->name('documents.schema.root.load'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            Route::get('schema/status/get', [DocumentTypeController::class, 'get__status_saved_schema'])->middleware('throttle:moderate')->name('documents.schema.status.get'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            Route::get('{name}/schema/load/{id?}', [DocumentTypeController::class, 'load_schema'])->middleware('throttle:moderate')->name('documents.schema.load'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            // Get schema attribute columns
            Route::get('{name}/schema/columns', [DocumentTypeController::class, 'get__schema_attribute_columns'])->middleware('throttle:moderate')->name('documents.schema.columns'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route

            /*
            |--------------------------------------------------------------------------
            | Files Routes
            |--------------------------------------------------------------------------
            */
            Route::post('{name}/recognize', [DocumentTypeActionController::class, 'recognize_file_client'])->middleware('throttle:very_strict')->name('documents.data.recognize'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            Route::get('files', [FileController::class, 'index'])->middleware('throttle:generous')->name('documents.files.index.get'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            Route::get('{name}/files', [FileController::class, 'index'])->middleware('throttle:generous')->where('name', '^(?!files$).*')->name('documents.files.index.get.named'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            Route::get('folders', [FileController::class, '__get_folders'])->middleware('throttle:generous')->name('documents.folders.get');
            // Get hashing API token for OCR API
            Route::get('{name}/ocr-space/get-hashing-token', [DocumentTypeActionController::class, 'get__api_hashing_token'])->middleware('throttle:moderate')->name('ocr-space.token.get');
        });

        /*
        |--------------------------------------------------------------------------
        | Settings/Userguides Routes
        |-------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'settings/user-guides'], function () {
            Route::post('upload', [SettingController::class, 'upload'])->middleware('throttle:very_strict')->name('settings.userguides.upload');
            Route::get('get', [SettingController::class, '__get_user_guide_create_edit__tree_items'])->middleware('throttle:moderate')->name('settings.userguides.get.page');
        });

        /*
        |--------------------------------------------------------------------------
        | Userguides Routes
        |-------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'user-guides'], function () {
            Route::get('get/content/{id}', [SettingController::class, '__get_user_guide_content'])->middleware('throttle:moderate')->name('userguides.get.content');
            Route::get('get/lists', [SettingController::class, '__get_user_guide_lists'])->middleware('throttle:moderate')->name('userguides.get.lists');
        });

        Route::group(['prefix' => 'documents/files'], function () {
            /*
            |--------------------------------------------------------------------------
            | Upload files Routes
            |--------------------------------------------------------------------------
            */
            Route::post('upload', [FileController::class, 'upload'])->middleware('throttle:very_strict')->name('documents.files.root.upload'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
            Route::post('{name}/upload', [FileController::class, 'upload'])->middleware('throttle:very_strict')->name('documents.files.upload'); // BOOKMARK: Implementasion done for appliying laravel sanctum authentication method to this route
        });
    });

    Route::get('content/{encryptedname}', [FileController::class, '__get_file_content'])->middleware('throttle:moderate')->name('documents.files.content');
});
