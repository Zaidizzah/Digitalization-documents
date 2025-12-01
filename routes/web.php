<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Call Controller and Models Classes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DocumentTypeActionController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/signout', [AuthController::class, 'signout'])->name('signout');

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('throttle:generous')->name('dashboard.index');
    Route::get('/search', [SearchController::class, 'index'])->middleware('throttle:strict')->name('search.index');

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [UserController::class, 'profile'])->middleware('throttle:generous')->name('users.profile');
        Route::put('update', [UserController::class, 'update_profile'])->middleware('throttle:moderate')->name('users.profile.update');
        Route::put('change_password', [UserController::class, 'update_password'])->middleware('throttle:moderate')->name('users.profile.change.password');
    });

    /*
    |--------------------------------------------------------------------------
    | Users Routes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'user-guides'], function () {
        Route::get('index', [SettingController::class, 'user_guide__show'])->middleware('throttle:generous')->name('userguides.show.index');
        Route::get('{path}', [SettingController::class, 'user_guide__show'])->middleware('throttle:generous')->where('path', '.*')->name('userguides.show.dynamic');
        // Route::get('get/file/{encrypted}', [SettingController::class, '__get_file_content'])->name('userguides.get.file');
    });

    Route::middleware('role:Admin')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | setting and user guide Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->middleware('throttle:generous')->name('settings.index');
            Route::put('/update', [SettingController::class, 'update'])->middleware('throttle:moderate')->name('settings.update');

            Route::get('user-guides/create', [SettingController::class, 'user_guide__create'])->middleware('throttle:generous')->name('userguides.create');
            Route::post('user-guides/store', [SettingController::class, 'user_guide__store'])->middleware('throttle:moderate')->name('userguides.store');
            Route::get('user-guides/edit/{id}', [SettingController::class, 'user_guide__edit'])->middleware('throttle:generous')->name('userguides.edit');
            Route::put('user-guides/update/{id}', [SettingController::class, 'user_guide__update'])->middleware('throttle:moderate')->name('userguides.update');
            Route::delete('user-guides/delete/{id}', [SettingController::class, 'user_guide__destroy'])->middleware('throttle:moderate')->name('userguides.destroy');
            Route::put('user-guides/activate/{id}', [SettingController::class, 'user_guide__activate'])->middleware('throttle:moderate')->name('userguides.activate');
            Route::put('user-guides/deactivate/{id}', [SettingController::class, 'user_guide__deactivate'])->middleware('throttle:moderate')->name('userguides.deactivate');
            // Index User Guide route adding in last route sequence to provide the highest priority
            Route::get('user-guides', [SettingController::class, 'user_guide__index'])->middleware('throttle:generous')->name('userguides.index');
        });

        /*
        |--------------------------------------------------------------------------
        | User Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [UserController::class, 'index'])->middleware('throttle:generous')->name('users.index');
            Route::post('store', [UserController::class, 'store'])->middleware('throttle:moderate')->name('users.store');
            Route::put('update/{id}', [UserController::class, 'update'])->middleware('throttle:moderate')->name('users.update');
            Route::delete('delete/{id}', [UserController::class, 'destroy'])->middleware('throttle:moderate')->name('users.delete');
        });

        /*
        |--------------------------------------------------------------------------
        | Document Type Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'documents'], function () {
            Route::get('create', [DocumentTypeController::class, 'create'])->middleware('throttle:generous')->name('documents.create');
            Route::post('store', [DocumentTypeController::class, 'store'])->middleware('throttle:strict')->name('documents.store');

            Route::put('update/{name}', [DocumentTypeController::class, 'update'])->middleware('throttle:strict')->name('documents.update');
            Route::delete('delete/{id}', [DocumentTypeController::class, 'destroy'])->middleware('throttle:moderate')->name('documents.delete');
            Route::put('restore/{id}', [DocumentTypeController::class, 'restore'])->middleware('throttle:moderate')->name('documents.restore');

            Route::get('{name}/structure', [DocumentTypeActionController::class, 'structure'])->middleware('throttle:moderate')->name('documents.structure');
            Route::get('{name}/settings', [DocumentTypeActionController::class, 'settings'])->middleware('throttle:generous')->name('documents.settings');
            Route::post('{name}/import', [DocumentTypeActionController::class, 'import'])->middleware('throttle:strict')->name('documents.import');

            Route::get('{name}/create', [DocumentTypeActionController::class, 'create'])->middleware('throttle:generous')->name('documents.data.create');
            Route::post('{name}/store', [DocumentTypeActionController::class, 'store'])->middleware('throttle:strict')->name('documents.data.store');
            Route::get('{name}/edit/{id}', [DocumentTypeActionController::class, 'edit'])->middleware('throttle:moderate')->name('documents.data.edit');
            Route::put('{name}/update/{id}', [DocumentTypeActionController::class, 'update'])->middleware('throttle:strict')->name('documents.data.update');
            Route::delete('{name}/delete/{id}', [DocumentTypeActionController::class, 'destroy'])->middleware('throttle:strict')->name('documents.data.delete');
            Route::delete('{name}/destroy', [DocumentTypeActionController::class, 'destroy_all'])->middleware('throttle:very_strict')->name('documents.data.delete.all');

            /*
            |--------------------------------------------------------------------------
            | Named user guide Routes
            |--------------------------------------------------------------------------
            */
            Route::get('{name}/user-guides/create', [SettingController::class, 'user_guide__create'])->middleware('throttle:moderate')->where('name', '^(?!create$).*')->name('userguides.create.named');
            Route::post('{name}/user-guides/store', [SettingController::class, 'user_guide__store'])->middleware('throttle:strict')->where('name', '^(?!store$).*')->name('userguides.store.named');
            Route::get('{name}/user-guides/edit/{id}', [SettingController::class, 'user_guide__edit'])->middleware('throttle:moderate')->where('name', '^(?!edit$).*')->name('userguides.edit.named');
            Route::put('{name}/user-guides/update/{id}', [SettingController::class, 'user_guide__update'])->middleware('throttle:strict')->where('name', '^(?!update$).*')->name('userguides.update.named');
            Route::delete('{name}/user-guides/delete/{id}', [SettingController::class, 'user_guide__destroy'])->middleware('throttle:moderate')->where('name', '^(?!delete$).*')->name('userguides.destroy.named');
            Route::put('{name}/user-guides/activate/{id}', [SettingController::class, 'user_guide__activate'])->middleware('throttle:moderate')->where('name', '^(?!activate$).*')->name('userguides.activate.named');
            Route::put('{name}/user-guides/deactivate/{id}', [SettingController::class, 'user_guide__deactivate'])->middleware('throttle:moderate')->where('name', '^(?!deactivate$).*')->name('userguides.deactivate.named');
            // Index User Guide route adding in last route sequence to provide the highest priority
            Route::get('{name}/user-guides', [SettingController::class, 'user_guide__index'])->middleware('throttle:generous')->name('userguides.index.named');

            /*
            |--------------------------------------------------------------------------
            | Route for handle the schema attributes
            |--------------------------------------------------------------------------
            */
            Route::get('{name}/schema/edit/{id?}', [DocumentTypeController::class, 'edit_schema_of_document_type'])->middleware('throttle:moderate')->name('documents.edit.schema');
            Route::delete('{name}/schema/delete/{id}', [DocumentTypeController::class, 'delete_schema_of_document_type'])->middleware('throttle:strict')->name('documents.delete.schema');
            Route::put('{name}/schema/update', [DocumentTypeController::class, 'update_schema_of_document_type'])->middleware('throttle:strict')->name('documents.update.schema');

            Route::get('{name}/schema/insert', [DocumentTypeController::class, 'insert'])->middleware('throttle:moderate')->name('documents.insert.schema.page');
            Route::post('{name}/schema/insert', [DocumentTypeController::class, 'insert_schema_of_document_type'])->middleware('throttle:strict')->name('documents.insert.schema');

            // Reorder schema route
            Route::get('{name}/schema/reorder', [DocumentTypeController::class, 'reorder'])->middleware('throttle:moderate')->name('documents.schema.reorder.page');
            Route::put('{name}/schema/reorder', [DocumentTypeController::class, 'reorder_schema_of_document_type'])->middleware('throttle:strict')->name('documents.schema.reorder');

            // Document type data
            Route::put('{name}/attach', [DocumentTypeActionController::class, 'attach'])->middleware('throttle:moderate')->name('documents.data.attach');
        });

        /*
        |--------------------------------------------------------------------------
        | Documents files routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'documents/files'], function () {
            //doument files admin only
            Route::get('/', [FileController::class, 'index'])->middleware('throttle:generous')->name('documents.files.root.index');
            Route::get('download', [FileController::class, 'download'])->middleware('throttle:moderate')->name('documents.files.root.download');
            Route::get('preview', [FileController::class, 'preview'])->middleware('throttle:moderate')->name('documents.files.root.preview');
            Route::delete('{name?}/delete/{keep?}', [FileController::class, 'destroy'])->middleware('throttle:moderate')->name('documents.files.delete');
            Route::delete('delete', [FileController::class, 'destroy'])->middleware('throttle:moderate')->name('documents.files.root.delete');
            Route::put('rename', [FileController::class, 'rename'])->middleware('throttle:moderate')->name('documents.files.rename');
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Documents routes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'documents'], function () {
        Route::get('/', [DocumentTypeController::class, 'index'])->middleware('throttle:generous')->name('documents.index');
        Route::get('{name}/browse', [DocumentTypeActionController::class, 'browse'])->middleware('throttle:moderate')->name('documents.browse');
        Route::get('{name}/export', [DocumentTypeActionController::class, 'export'])->middleware('throttle:strict')->name('documents.export');

        /*
        |--------------------------------------------------------------------------
        | Documents files routes
        |--------------------------------------------------------------------------
        */
        Route::get('{name}/files', [FileController::class, 'index'])->middleware('throttle:generous')->name('documents.files.index');
        Route::get('{name}/files/download', [FileController::class, 'download'])->middleware('throttle:moderate')->name('documents.files.download');
        // Download sample file for importing data
        Route::get('{name}/files/download-example', [FileController::class, 'download_example_file'])->middleware('throttle:strict')->name('documents.files.download.example');
        Route::get('{name}/files/preview', [FileController::class, 'preview'])->middleware('throttle:moderate')->name('documents.files.preview');
    });
});

Route::middleware(['guest'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */
    Route::redirect('/', '/signin')->middleware('throttle:generous')->name('entrance');

    Route::get('/signin', [AuthController::class, 'signin_page'])->middleware('throttle:generous')->name('signin.page');
    Route::post('/signin', [AuthController::class, 'signin'])->middleware('throttle:very_strict')->name('signin');

    Route::post('/forgot-password', [AuthController::class, 'forgot_password'])->middleware('throttle:very_strict')->name('forgot-password');

    Route::get('/signup', [AuthController::class, 'signup_page'])->middleware('throttle:generous')->name('signup.page');
    Route::post('/signup', [AuthController::class, 'signup'])->middleware('throttle:very_strict')->name('signup');
});
