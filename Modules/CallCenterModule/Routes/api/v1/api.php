<?php

use Illuminate\Support\Facades\Route;
use Modules\CallCenterModule\Http\Controllers\Api\V1\AgentController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CallAiAnalysisController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CallController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CallRecordingController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CustomerBookingController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CustomerComplaintController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CustomerController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CustomerNoteController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\CustomerTimelineController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\TaskController;
use Modules\CallCenterModule\Http\Controllers\Api\V1\VoicemailController;

Route::get('customers/search', [CustomerController::class, 'search']);
Route::get('customers/by-phone/{phone}', [CustomerController::class, 'byPhone'])->where('phone', '.*');
Route::get('customers/by-ref/{customerId}', [CustomerController::class, 'byRef']);
Route::post('customers', [CustomerController::class, 'store']);
Route::get('customers/{id}', [CustomerController::class, 'show'])->whereNumber('id');
Route::patch('customers/{id}', [CustomerController::class, 'update'])->whereNumber('id');
Route::patch('customers/{id}/ai-summary', [CustomerController::class, 'updateAiSummary'])->whereNumber('id');

Route::get('customers/{id}/bookings', [CustomerBookingController::class, 'index'])->whereNumber('id');
Route::get('customers/{id}/orders', [CustomerBookingController::class, 'orders'])->whereNumber('id');
Route::get('customers/{id}/complaints', [CustomerComplaintController::class, 'index'])->whereNumber('id');
Route::get('customers/{id}/summary', [CustomerBookingController::class, 'summary'])->whereNumber('id');
Route::get('customers/{id}/timeline', [CustomerTimelineController::class, 'index'])->whereNumber('id');

Route::get('customers/{id}/notes', [CustomerNoteController::class, 'index'])->whereNumber('id');
Route::post('customers/{id}/notes', [CustomerNoteController::class, 'store'])->whereNumber('id');
Route::patch('notes/{id}', [CustomerNoteController::class, 'update'])->whereNumber('id');

Route::post('calls', [CallController::class, 'store']);
Route::patch('calls/by-external-id/{externalId}', [CallController::class, 'updateByExternalId']);
Route::post('calls/by-external-id/{externalId}/recordings', [CallRecordingController::class, 'storeByExternalId']);
Route::post('calls/by-external-id/{externalId}/ai-analysis', [CallAiAnalysisController::class, 'storeByExternalId']);
Route::patch('calls/{id}', [CallController::class, 'update'])->whereNumber('id');
Route::post('calls/{id}/recordings', [CallRecordingController::class, 'store'])->whereNumber('id');
Route::post('calls/{id}/ai-analysis', [CallAiAnalysisController::class, 'store'])->whereNumber('id');
Route::get('customers/{id}/calls', [CallController::class, 'indexForCustomer'])->whereNumber('id');

Route::post('voicemails', [VoicemailController::class, 'store']);
Route::patch('voicemails/{id}', [VoicemailController::class, 'update'])->whereNumber('id');
Route::get('voicemails', [VoicemailController::class, 'index']);

Route::post('tasks', [TaskController::class, 'store']);
Route::patch('tasks/{id}', [TaskController::class, 'update'])->whereNumber('id');
Route::get('tasks', [TaskController::class, 'index']);

Route::get('agents/by-external-id/{externalId}', [AgentController::class, 'showByExternalId']);
Route::get('agents/{id}', [AgentController::class, 'show'])->whereNumber('id');
