<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemarcationController;
use App\Http\Controllers\MapDataController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SalesPersonController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [DemarcationController::class, 'index'])->name('demarcations.index');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
	Route::resource('permissions', PermissionController::class);
	Route::resource('roles', RoleController::class);
	Route::resource('users', UserController::class);
	Route::resource('sales-personnel', SalesPersonController::class);
	Route::patch('sales-personnel/{sales_personnel}/deactivate', [SalesPersonController::class, 'deactivate'])->name('sales-personnel.deactivate');
	Route::patch('sales-personnel/{sales_personnel}/activate', [SalesPersonController::class, 'activate'])->name('sales-personnel.activate');
	Route::resource('customers', CustomerController::class);
	Route::patch('customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])->name('customers.deactivate');
	Route::patch('customers/{customer}/activate', [CustomerController::class, 'activate'])->name('customers.activate');
	Route::get('customers/boundaries/constituencies/{countyCode}', [CustomerController::class, 'getConstituencies'])->name('customers.constituencies');
	Route::get('customers/boundaries/wards/{constituencyCode}', [CustomerController::class, 'getWards'])->name('customers.wards');
	Route::resource('routes', RouteController::class);
	Route::patch('routes/{route}/deactivate', [RouteController::class, 'deactivate'])->name('routes.deactivate');
	Route::patch('routes/{route}/activate', [RouteController::class, 'activate'])->name('routes.activate');
	Route::resource('partners', PartnerController::class);
	Route::patch('partners/{partner}/deactivate', [PartnerController::class, 'deactivate'])->name('partners.deactivate');
	Route::patch('partners/{partner}/activate', [PartnerController::class, 'activate'])->name('partners.activate');
	Route::resource('map-data', MapDataController::class);
	Route::patch('map-data/{map_data}/deactivate', [MapDataController::class, 'deactivate'])->name('map-data.deactivate');
	Route::patch('map-data/{map_data}/activate', [MapDataController::class, 'activate'])->name('map-data.activate');
	Route::post('map-data/upload-rows', [MapDataController::class, 'uploadRows'])->name('map-data.upload-rows');
});

Route::get('demarcations/fetch-geometry', [DemarcationController::class, 'fetchGeometry'])->name('demarcations.fetch-geometry');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
