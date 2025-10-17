<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentNavigationController;
use App\Http\Controllers\Api\JournalPreviewController;
use App\Http\Controllers\Api\DocumentAnalyticsController;
use App\Http\Controllers\DocumentRelationshipController;
use App\Http\Controllers\UnitOfMeasureController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Document Navigation API Routes
Route::middleware(['web', 'auth'])->group(function () {
    // Get complete navigation data for a document
    Route::get('/documents/{documentType}/{documentId}/navigation', [DocumentNavigationController::class, 'getNavigationData'])
        ->where(['documentType' => '[a-z-]+', 'documentId' => '[0-9]+']);

    // Get base documents for a document
    Route::get('/documents/{documentType}/{documentId}/base', [DocumentNavigationController::class, 'getBaseDocuments'])
        ->where(['documentType' => '[a-z-]+', 'documentId' => '[0-9]+']);

    // Get target documents for a document
    Route::get('/documents/{documentType}/{documentId}/targets', [DocumentNavigationController::class, 'getTargetDocuments'])
        ->where(['documentType' => '[a-z-]+', 'documentId' => '[0-9]+']);

    // Get journal preview for a document action
    Route::post('/documents/{documentType}/{documentId}/journal-preview', [JournalPreviewController::class, 'getJournalPreview'])
        ->where(['documentType' => '[a-z-]+', 'documentId' => '[0-9]+']);

    // Get journal preview for GRPO from form data (before saving)
    Route::post('/journal-preview/grpo', [JournalPreviewController::class, 'grpoPreview']);

    // Get relationship map for a document
    Route::get('/documents/{documentType}/{documentId}/relationship-map', [DocumentRelationshipController::class, 'getRelationshipMap'])
        ->where(['documentType' => '[a-z-]+', 'documentId' => '[0-9]+']);

    // Analytics routes
    Route::post('/analytics/document-navigation', [DocumentAnalyticsController::class, 'trackNavigation']);
    Route::get('/analytics/documents/{documentType}/{documentId}', [DocumentAnalyticsController::class, 'getDocumentAnalytics'])
        ->where(['documentType' => '[a-z-]+', 'documentId' => '[0-9]+']);
    Route::get('/analytics/system', [DocumentAnalyticsController::class, 'getSystemAnalytics']);
    Route::get('/analytics/performance', [DocumentAnalyticsController::class, 'getPerformanceMetrics']);
    Route::post('/analytics/report', [DocumentAnalyticsController::class, 'generateReport']);
    Route::post('/analytics/export', [DocumentAnalyticsController::class, 'exportData']);

    // Unit Conversion API Routes
    Route::get('/inventory/{id}/units', [UnitOfMeasureController::class, 'getItemUnits'])
        ->where(['id' => '[0-9]+']);
    Route::get('/units/by-type', [UnitOfMeasureController::class, 'getUnitsByType']);
    Route::get('/units/conversion-factor', [UnitOfMeasureController::class, 'getConversionFactor']);
    Route::get('/units/conversion-preview', [UnitOfMeasureController::class, 'getConversionPreview']);
    Route::post('/units/validate-conversion', [UnitOfMeasureController::class, 'validateConversion']);
});
