<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Route;
use Illuminate\Http\Request;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\LineString;

class RouteController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		return Inertia::render('Routes/Index', [
			"routes" => Inertia::merge(
				Route::orderBy('name')->paginate()
			),
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$request->validate([
			'name' => 'required|string|max:255',
			'description' => 'required|string|max:500',
		]);

		// For now, we'll create a simple point geometry as a placeholder
		// In a real implementation, this would come from map interaction
		$routeData = $request->all();

		$points = [
			new Point(0, 0),
			new Point(0, 0),
		];

		$routeData['line'] = new LineString($points);

		Route::create($routeData);

		return redirect()->route('routes.index')->with('success', 'Route created successfully.');
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, Route $route)
	{
		$request->validate([
			'name' => 'required|string|max:255',
			'description' => 'required|string|max:500',
		]);

		// Keep the existing geometry, only update name and description
		$route->update([
			'name' => $request->name,
			'description' => $request->description,
		]);

		return redirect()->route('routes.index')->with('success', 'Route updated successfully.');
	}

	/**
	 * Deactivate the specified route.
	 */
	public function deactivate(Route $route)
	{
		$route->deactivate();

		return redirect()->route('routes.index')->with('success', 'Route deactivated successfully.');
	}

	/**
	 * Activate the specified route.
	 */
	public function activate(Route $route)
	{
		$route->activate();

		return redirect()->route('routes.index')->with('success', 'Route activated successfully.');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Route $route)
	{
		$route->delete();

		return redirect()->route('routes.index')->with('success', 'Route deleted successfully.');
	}
}
