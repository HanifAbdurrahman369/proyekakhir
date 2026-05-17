<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasterController;

Route::get('/tables', [MasterController::class, 'getTables']);
Route::get('/tables/{name}', [MasterController::class, 'getTableData']);
Route::post('/tables/{name}', [MasterController::class, 'storeData']);
Route::put('/tables/{name}/{id}', [MasterController::class, 'updateData']);
Route::delete('/tables/{name}/{id}', [MasterController::class, 'deleteData']);
Route::post('/execute-sql', [MasterController::class, 'executeRawSql']);
// Route Export Data
Route::get('/export/sql/{tableName?}', [MasterController::class, 'exportSql']);
Route::get('/export/excel/{tableName}', [MasterController::class, 'exportExcel']);