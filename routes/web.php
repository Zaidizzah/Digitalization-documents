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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [UserController::class, 'profile'])->name('users.profile');
        Route::put('change_name', [UserController::class, 'edit_profile'])->name('users.profile.change_name');
        Route::put('change_password', [UserController::class, 'edit_password'])->name('users.profile.change_password');
    });

    /*
    |--------------------------------------------------------------------------
    | Users Routes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'user-guides'], function () {
        Route::get('index.html', [SettingController::class, 'user_guide__show'])->name('userguides.show.index');
        Route::get('{path}.html', [SettingController::class, 'user_guide__show'])->where('path', '.*')->name('userguides.show.dynamic');
        Route::get('content/{encrypted}', [SettingController::class, '__get_file_content'])->name('userguides.content');
    });

    Route::middleware('role:Admin')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | setting and user guide Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('settings.index');
            Route::put('/update', [SettingController::class, 'update'])->name('settings.update');

            Route::get('user-guides/create', [SettingController::class, 'user_guide__create'])->name('userguides.create');
            Route::post('user-guides/store', [SettingController::class, 'user_guide__store'])->name('userguides.store');
            Route::get('user-guides/edit/{id}', [SettingController::class, 'user_guide__edit'])->name('userguides.edit');
            Route::put('user-guides/update/{id}', [SettingController::class, 'user_guide__update'])->name('userguides.update');
            Route::delete('user-guides/delete/{id}', [SettingController::class, 'user_guide__destroy'])->name('userguides.destroy');
            Route::put('user-guides/activate/{id}', [SettingController::class, 'user_guide__activate'])->name('userguides.activate');
            // Index User Guide route adding in last route sequence to provide the highest priority
            Route::get('user-guides', [SettingController::class, 'user_guide__index'])->name('userguides.index');
        });

        /*
        |--------------------------------------------------------------------------
        | User Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [UserController::class, 'index'])->name('users.index');
            Route::post('store', [UserController::class, 'store'])->name('users.store');
            Route::put('update/{id}', [UserController::class, 'update'])->name('users.update');
            Route::delete('delete/{id}', [UserController::class, 'destroy'])->name('users.delete');
        });

        /*
        |--------------------------------------------------------------------------
        | Document Type Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'documents'], function () {
            Route::get('create', [DocumentTypeController::class, 'create'])->name('documents.create');
            Route::post('store', [DocumentTypeController::class, 'store'])->name('documents.store');

            Route::put('update/{name}', [DocumentTypeController::class, 'update'])->name('documents.update');
            Route::delete('delete/{id}', [DocumentTypeController::class, 'destroy'])->name('documents.delete');
            Route::put('restore/{id}', [DocumentTypeController::class, 'restore'])->name('documents.restore');

            Route::get('{name}/structure', [DocumentTypeActionController::class, 'structure'])->name('documents.structure');
            Route::get('{name}/settings', [DocumentTypeActionController::class, 'settings'])->name('documents.settings');
            Route::post('{name}/import', [DocumentTypeActionController::class, 'import'])->name('documents.import');

            Route::get('{name}/create', [DocumentTypeActionController::class, 'create'])->name('documents.data.create');
            Route::post('{name}/store', [DocumentTypeActionController::class, 'store'])->name('documents.data.store');
            Route::get('{name}/edit/{id}', [DocumentTypeActionController::class, 'edit'])->name('documents.data.edit');
            Route::put('{name}/update/{id}', [DocumentTypeActionController::class, 'update'])->name('documents.data.update');
            Route::delete('{name}/delete/{id}', [DocumentTypeActionController::class, 'destroy'])->name('documents.data.delete');
            Route::delete('{name}/destroy', [DocumentTypeActionController::class, 'destroy_all'])->name('documents.data.delete.all');

            /*
            |--------------------------------------------------------------------------
            | Named user guide Routes
            |--------------------------------------------------------------------------
            */
            Route::get('{name}/user-guides/create', [SettingController::class, 'user_guide__create'])->where('name', '^(?!create$).*')->name('userguides.create.named');
            Route::post('{name}/user-guides/store', [SettingController::class, 'user_guide__store'])->where('name', '^(?!store$).*')->name('userguides.store.named');
            Route::get('{name}/user-guides/edit/{id}', [SettingController::class, 'user_guide__edit'])->where('name', '^(?!edit$).*')->name('userguides.edit.named');
            Route::put('{name}/user-guides/update/{id}', [SettingController::class, 'user_guide__update'])->where('name', '^(?!update$).*')->name('userguides.update.named');
            Route::delete('{name}/user-guides/delete/{id}', [SettingController::class, 'user_guide__destroy'])->where('name', '^(?!delete$).*')->name('userguides.destroy.named');
            Route::put('{name}/user-guides/activate/{id}', [SettingController::class, 'user_guide__activate'])->where('name', '^(?!activate$).*')->name('userguides.activate.named');
            // Index User Guide route adding in last route sequence to provide the highest priority
            Route::get('{name}/user-guides', [SettingController::class, 'user_guide__index'])->name('userguides.index.named');

            /*
            |--------------------------------------------------------------------------
            | Route for handle the schema attributes
            |--------------------------------------------------------------------------
            */
            Route::get('{name}/schema/edit/{id?}', [DocumentTypeController::class, 'edit_schema_of_document_type'])->name('documents.edit.schema');
            Route::delete('{name}/schema/delete/{id}', [DocumentTypeController::class, 'delete_schema_of_document_type'])->name('documents.delete.schema');
            Route::put('{name}/schema/update', [DocumentTypeController::class, 'update_schema_of_document_type'])->name('documents.update.schema');

            Route::get('{name}/schema/insert', [DocumentTypeController::class, 'insert'])->name('documents.insert.schema.page');
            Route::post('{name}/schema/insert', [DocumentTypeController::class, 'insert_schema_of_document_type'])->name('documents.insert.schema');

            // Reorder schema route
            Route::get('{name}/schema/reorder', [DocumentTypeController::class, 'reorder'])->name('documents.schema.reorder.page');
            Route::put('{name}/schema/reorder', [DocumentTypeController::class, 'reorder_schema_of_document_type'])->name('documents.schema.reorder');

            // Document type data
            Route::put('{name}/attach', [DocumentTypeActionController::class, 'attach'])->name('documents.data.attach');
        });

        /*
        |--------------------------------------------------------------------------
        | Documents files routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'documents/files'], function () {
            //doument files admin only
            Route::get('/', [FileController::class, 'index'])->name('documents.files.root.index');
            Route::get('download', [FileController::class, 'download'])->name('documents.files.root.download');
            Route::get('preview', [FileController::class, 'preview'])->name('documents.files.root.preview');
            Route::delete('{name?}/delete/{keep?}', [FileController::class, 'destroy'])->name('documents.files.delete');
            Route::delete('delete', [FileController::class, 'destroy'])->name('documents.files.root.delete');
            Route::put('rename', [FileController::class, 'rename'])->name('documents.files.rename');
            // Get file stream/blob content
            Route::get('content/{name}', [FileController::class, '__get_file_content'])->name('documents.files.content');
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Documents routes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'documents'], function () {
        Route::get('/', [DocumentTypeController::class, 'index'])->name('documents.index');
        Route::get('{name}/browse', [DocumentTypeActionController::class, 'browse'])->name('documents.browse');
        Route::get('{name}/export', [DocumentTypeActionController::class, 'export'])->name('documents.export');

        /*
        |--------------------------------------------------------------------------
        | Documents files routes
        |--------------------------------------------------------------------------
        */
        Route::get('{name}/files', [FileController::class, 'index'])->name('documents.files.index');
        Route::get('{name}/files/download', [FileController::class, 'download'])->name('documents.files.download');
        // Download sample file for importing data
        Route::get('{name}/files/download-example', [FileController::class, 'download_example_file'])->name('documents.files.download.example');
        Route::get('{name}/files/preview', [FileController::class, 'preview'])->name('documents.files.preview');
    });
});

Route::middleware(['guest'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */
    Route::redirect('/', '/signin');

    Route::get('/signin', [AuthController::class, 'signin_page'])->name('signin.page');
    Route::post('/signin', [AuthController::class, 'signin'])->name('signin');

    Route::post('/forgot-password', [AuthController::class, 'forgot_password'])->name('forgot-password');

    Route::get('/signup', [AuthController::class, 'signup_page'])->name('signup.page');
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
});
