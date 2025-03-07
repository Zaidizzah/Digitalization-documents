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

Route::middleware(['auth'])->group(function () {
    Route::get('/signout', [AuthController::class, 'signout'])->name('signout');

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::middleware(['auth', 'role:Admin'])->group(function () {
        /*
        |--------------------------------------------------------------------------
        | User Routes
        |--------------------------------------------------------------------------
        */
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/update/{id}', [UserController::class, 'update'])->name('users.update');
        Route::get('/users/delete/{id}', [UserController::class, 'destroy'])->name('users.delete');

        /*
        |--------------------------------------------------------------------------
        | Document Type Routes
        |--------------------------------------------------------------------------
        */
        Route::get('/documents', [DocumentTypeController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [DocumentTypeController::class, 'create'])->name('documents.create');
        Route::post('/documents/store', [DocumentTypeController::class, 'store'])->name('documents.store');

        Route::post('/documents/update/{name}', [DocumentTypeController::class, 'update'])->name('documents.update');
        Route::get('/documents/delete/{name}', [DocumentTypeController::class, 'destroy'])->name('documents.delete');

        Route::get('/documents/{name}/browse', [DocumentTypeActionController::class, 'browse'])->name('documents.browse');
        Route::get('/documents/{name}/structure', [DocumentTypeActionController::class, 'structure'])->name('documents.structure');
        Route::get('/documents/{name}/settings', [DocumentTypeActionController::class, 'settings'])->name('documents.settings');

        Route::get('/documents/{name}/create', [DocumentTypeActionController::class, 'create'])->name('documents.data.create');
        Route::post('/documents/{name}/store', [DocumentTypeActionController::class, 'store'])->name('documents.data.store');
        Route::get('/documents/{name}/edit/{id}', [DocumentTypeActionController::class, 'edit'])->name('documents.data.edit');
        Route::post('/documents/{name}/update/{id}', [DocumentTypeActionController::class, 'update'])->name('documents.data.update');
        Route::get('/documents/{name}/delete/{id}', [DocumentTypeActionController::class, 'destroy'])->name('documents.data.delete');
        Route::get('/documents/{name}/destroy', [DocumentTypeActionController::class, 'destroy_all'])->name('documents.data.delete.all');

        /*
        |--------------------------------------------------------------------------
        | Route for handle the schema attributes
        |--------------------------------------------------------------------------
        */
        Route::get('/documents/{name}/schema/edit/{id?}', [DocumentTypeController::class, 'edit_schema_of_document_type'])->name('documents.edit.schema');
        Route::get('/documents/{name}/schema/delete/{id}', [DocumentTypeController::class, 'delete_schema_of_document_type'])->name('documents.delete.schema');
        Route::post('/documents/{name}/schema/update', [DocumentTypeController::class, 'update_schema_of_document_type'])->name('documents.update.schema');

        Route::get('/documents/{name}/schema/insert/', [DocumentTypeController::class, 'insert'])->name('documents.insert.schema.page');
        Route::post('/documents/{name}/schema/insert', [DocumentTypeController::class, 'insert_schema_of_document_type'])->name('documents.insert.schema');

        Route::post('/documents/schema/save', [DocumentTypeController::class, 'save_schema'])->name('documents.schema.save');
        Route::get('/documents/schema/load/', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.root.load');
        Route::get('/documents/{name}/schema/load/{id?}', [DocumentTypeController::class, 'load_schema'])->name('documents.schema.load');
    });

    /*
    |--------------------------------------------------------------------------
    | Documents files routes
    |--------------------------------------------------------------------------
    */
    Route::get('/documents/{name}/files', [FileController::class, 'index'])->name('documents.files.index');
    Route::get('/documents/files', [FileController::class, 'index'])->name('documents.files.root.index');

    Route::post('/documents/files/upload', [FileController::class, 'upload'])->name('documents.files.root.upload');
    Route::post('/documents/files/{name}/upload', [FileController::class, 'upload'])->name('documents.files.upload');
    Route::get('/documents/{name}/files/download', [FileController::class, 'download'])->name('documents.files.download');
    Route::get('/documents/files/download', [FileController::class, 'download'])->name('documents.files.root.download');
    Route::get('/documents/files/delete', [FileController::class, 'destroy'])->name('documents.files.delete');
    Route::post('/documents/files/rename', [FileController::class, 'rename'])->name('documents.files.rename');

    Route::get('/documents/files/preview', [FileController::class, 'preview'])->name('documents.files.root.preview');
    Route::get('/documents/{name}/files/preview', [FileController::class, 'preview'])->name('documents.files.preview');

    Route::get('/documents/files/content/{name}', [FileController::class, 'get_file_content'])->name('documents.files.content');
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
