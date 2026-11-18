<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MetricaController;
use App\Http\Controllers\Api\ReporteIncidenteController;
use App\Http\Controllers\Api\ZonaController;
use Illuminate\Support\Facades\Route;

// Autenticación Industrial
Route::post('/auth/login', [AuthController::class, 'login']);

// Recepción de paquetes del nodo físico ESP32-S3 y telemetría
Route::post('/iot/reporte', [ReporteIncidenteController::class, 'storeFromIot']);
Route::post('/iot/reportes', [ReporteIncidenteController::class, 'storeFromIot']); // Alias de compatibilidad
Route::get('/iot/status', [ReporteIncidenteController::class, 'iotStatus']);

// Consultas operativas y exportación de reportes
Route::get('/metricas', [MetricaController::class, 'index']);
Route::get('/reportes/exportar/csv', [ReporteIncidenteController::class, 'exportarCsv']);
Route::get('/reportes/{id}/pdf', [ReporteIncidenteController::class, 'descargarPdf']);
Route::get('/reportes', [ReporteIncidenteController::class, 'index']);
Route::get('/reportes/{id}', [ReporteIncidenteController::class, 'show']);
Route::get('/zonas', [ZonaController::class, 'index']);

// Operaciones protegidas para Supervisores HSE (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::patch('/reportes/{id}/estado', [ReporteIncidenteController::class, 'updateEstado']);
    Route::post('/iot/simular', [ReporteIncidenteController::class, 'simularIot']);

    Route::post('/zonas', [ZonaController::class, 'store']);
    Route::put('/zonas/{id}', [ZonaController::class, 'update']);
    Route::delete('/zonas/{id}', [ZonaController::class, 'destroy']);
});
