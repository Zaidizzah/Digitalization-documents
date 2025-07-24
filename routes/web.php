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

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::group(['prefix' => 'profile'], function () {
        Route::post('change_name', [DashboardController::class, 'change_name'])->name('profile.change_name');
        Route::post('change_password', [DashboardController::class, 'change_password'])->name('profile.change_password');
    });

    Route::middleware('role:Admin')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | User Routes
        |--------------------------------------------------------------------------
        */
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::group(['prefix' => 'users'], function () {
            Route::post('store', [UserController::class, 'store'])->name('users.store');
            Route::post('update/{id}', [UserController::class, 'update'])->name('users.update');
            Route::get('delete/{id}', [UserController::class, 'destroy'])->name('users.delete');
        });

        /*
        |--------------------------------------------------------------------------
        | Document Type Routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'documents'], function () {
            Route::get('create', [DocumentTypeController::class, 'create'])->name('documents.create');
            Route::post('store', [DocumentTypeController::class, 'store'])->name('documents.store');

            Route::post('update/{name}', [DocumentTypeController::class, 'update'])->name('documents.update');
            Route::get('delete/{id}', [DocumentTypeController::class, 'destroy'])->name('documents.delete');
            Route::get('restore/{id}', [DocumentTypeController::class, 'restore'])->name('documents.restore');

            Route::get('{name}/structure', [DocumentTypeActionController::class, 'structure'])->name('documents.structure');
            Route::get('{name}/settings', [DocumentTypeActionController::class, 'settings'])->name('documents.settings');
            Route::post('{name}/import', [DocumentTypeActionController::class, 'import'])->name('documents.import');

            Route::get('{name}/create', [DocumentTypeActionController::class, 'create'])->name('documents.data.create');
            Route::post('{name}/store', [DocumentTypeActionController::class, 'store'])->name('documents.data.store');
            Route::get('{name}/edit/{id}', [DocumentTypeActionController::class, 'edit'])->name('documents.data.edit');
            Route::post('{name}/update/{id}', [DocumentTypeActionController::class, 'update'])->name('documents.data.update');
            Route::get('{name}/delete/{id}', [DocumentTypeActionController::class, 'destroy'])->name('documents.data.delete');
            Route::get('{name}/destroy', [DocumentTypeActionController::class, 'destroy_all'])->name('documents.data.delete.all');

            // Get result of file content in create action
            Route::post('{name}/recognize', [DocumentTypeActionController::class, 'recognize_file_client'])->name('documents.data.recognize');

            /*
            |--------------------------------------------------------------------------
            | Route for handle the schema attributes
            |--------------------------------------------------------------------------
            */
            Route::get('{name}/schema/edit/{id?}', [DocumentTypeController::class, 'edit_schema_of_document_type'])->name('documents.edit.schema');
            Route::get('{name}/schema/delete/{id}', [DocumentTypeController::class, 'delete_schema_of_document_type'])->name('documents.delete.schema');
            Route::post('{name}/schema/update', [DocumentTypeController::class, 'update_schema_of_document_type'])->name('documents.update.schema');

            Route::get('{name}/schema/insert/', [DocumentTypeController::class, 'insert'])->name('documents.insert.schema.page');
            Route::post('{name}/schema/insert', [DocumentTypeController::class, 'insert_schema_of_document_type'])->name('documents.insert.schema');

            Route::post('schema/save', [DocumentTypeController::class, 'save_schema'])->name('documents.schema.save');
            Route::get('schema/load/', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.root.load');
            Route::get('{name}/schema/load/{id?}', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.load');

            // Reorder schema route
            Route::get('{name}/schema/reorder', [DocumentTypeController::class, 'reorder'])->name('documents.schema.reorder.page');
            Route::post('{name}/schema/reorder', [DocumentTypeController::class, 'reorder_schema_of_document_type'])->name('documents.schema.reorder');
            Route::get('{name}/schema/columns', [DocumentTypeController::class, 'get_schema_attribute_columns'])->name('documents.schema.columns');

            // Document type data
            Route::post('{name}/attach', [DocumentTypeActionController::class, 'attach'])->name('documents.data.attach');
        });

        /*
        |--------------------------------------------------------------------------
        | Documents files routes
        |--------------------------------------------------------------------------
        */
        Route::group(['prefix' => 'documents/files'], function () {
            //doument files admin only
            Route::post('upload', [FileController::class, 'upload'])->name('documents.files.root.upload');
            Route::post('{name}/upload', [FileController::class, 'upload'])->name('documents.files.upload');
            Route::get('{name?}/delete/{keep?}', [FileController::class, 'destroy'])->name('documents.files.delete');
            Route::get('delete', [FileController::class, 'destroy'])->name('documents.files.root.delete');
            Route::post('rename', [FileController::class, 'rename'])->name('documents.files.rename');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Documents routes
    |--------------------------------------------------------------------------
    */
    Route::get('/documents', [DocumentTypeController::class, 'index'])->name('documents.index');
    Route::group(['prefix' => 'documents'], function () {
        Route::get('{name}/browse', [DocumentTypeActionController::class, 'browse'])->name('documents.browse');
        Route::get('{name}/export', [DocumentTypeActionController::class, 'export'])->name('documents.export');

        /*
        |--------------------------------------------------------------------------
        | Documents files routes
        |--------------------------------------------------------------------------
        */
        Route::get('{name}/files', [FileController::class, 'index'])->name('documents.files.index');
        Route::get('files', [FileController::class, 'index'])->name('documents.files.root.index');

        Route::get('{name}/files/download', [FileController::class, 'download'])->name('documents.files.download');
        Route::get('files/download', [FileController::class, 'download'])->name('documents.files.root.download');

        // Download sample file for importing data
        Route::get('{name}/files/download-example', [FileController::class, 'download_example_file'])->name('documents.files.download.example');

        Route::get('files/preview', [FileController::class, 'preview'])->name('documents.files.root.preview');
        Route::get('{name}/files/preview', [FileController::class, 'preview'])->name('documents.files.preview');

        Route::get('files/content/{name}', [FileController::class, 'get_file_content'])->name('documents.files.content');
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
