<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasterController;

Route::get('/', function () {
    return "Master Service is Online (Port 8004)";
});

// BUNGKUS DENGAN PREFIX API AGAR SINKRON DENGAN PANGGILAN DARI WEB_APP
Route::prefix('api')->group(function () {
    Route::get('/tables', [MasterController::class, 'getTables']);
    Route::get('/tables/{name}', [MasterController::class, 'getTableData']);
    Route::post('/tables/{name}', [MasterController::class, 'storeData']);
    Route::put('/tables/{name}/{id}', [MasterController::class, 'updateData']);
    Route::delete('/tables/{name}/{id}', [MasterController::class, 'deleteData']);
    Route::post('/execute-sql', [MasterController::class, 'executeRawSql']);
    
    // Rute Export Data
    Route::get('/export/sql/{tableName?}', [MasterController::class, 'exportSql']);
    Route::get('/export/excel/{tableName}', [MasterController::class, 'exportExcel']);
});