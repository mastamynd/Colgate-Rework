<?php

namespace App\Http\Controllers;

use App\Models\MapData;
use App\Models\User;
use App\Imports\MapDataRowDataImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class MapDataController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = MapData::with(['user', 'mapDataRows']);

		// Apply filters
		if ($request->filled('user_id')) {
			$query->where('user_id', $request->user_id);
		}

		if ($request->filled('type')) {
			$query->where('type', $request->type);
		}

		if ($request->filled('is_active')) {
			$query->where('is_active', $request->boolean('is_active'));
		}

		if ($request->filled('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('description', 'like', "%{$search}%");
			});
		}

		return Inertia::render('MapData/Index', [
			"mapData" => Inertia::merge(
				$query->withCount('mapDataRows')->orderBy('name')->paginate()->withQueryString()
			),
			"users" => User::orderBy('name')->get(),
			"types" => $this->getMapDataTypes(),
			"filters" => $request->only(['user_id', 'type', 'is_active', 'search'])
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$request->validate($this->validationRules());

		$mapData = MapData::create($request->all());

		// Create map data rows if provided
		if ($request->filled('map_data_rows')) {
			foreach ($request->map_data_rows as $rowData) {
				$mapData->mapDataRows()->create([
					'data' => $rowData
				]);
			}
		}

		return redirect()->route('map-data.index')->with('success', 'Map data created successfully.');
	}

	/**
	 * Display the specified resource.
	 */
	public function show(MapData $mapData)
	{
		$mapData->load(['user', 'mapDataRows']);

		return Inertia::render('MapData/Show', [
			'mapData' => $mapData
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(MapData $mapData)
	{
		$mapData->load(['user', 'mapDataRows']);

		return Inertia::render('MapData/Edit', [
			'mapData' => $mapData,
			'users' => User::orderBy('name')->get(),
			'types' => $this->getMapDataTypes()
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, MapData $mapData)
	{
		$request->validate($this->validationRules());

		$mapData->update($request->all());

		// Update map data rows if provided
		if ($request->filled('map_data_rows')) {
			// Delete existing rows
			$mapData->mapDataRows()->delete();

			// Create new rows
			foreach ($request->map_data_rows as $rowData) {
				$mapData->mapDataRows()->create([
					'data' => $rowData
				]);
			}
		}

		return redirect()->route('map-data.index')->with('success', 'Map data updated successfully.');
	}

	/**
	 * Deactivate the specified map data.
	 */
	public function deactivate(MapData $mapData)
	{
		$mapData->deactivate();

		return redirect()->route('map-data.index')->with('success', 'Map data deactivated successfully.');
	}

	/**
	 * Activate the specified map data.
	 */
	public function activate(MapData $mapData)
	{
		$mapData->activate();

		return redirect()->route('map-data.index')->with('success', 'Map data activated successfully.');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(MapData $mapData)
	{
		// Delete related map data rows first
		$mapData->mapDataRows()->delete();
		$mapData->delete();

		return redirect()->route('map-data.index')->with('success', 'Map data deleted successfully.');
	}

	/**
	 * Get available map data types
	 */
	private function getMapDataTypes(): array
	{
		return [
			'point' => 'Point',
			'line' => 'Line',
			'polygon' => 'Polygon',
			'multipoint' => 'Multi Point',
			'multiline' => 'Multi Line',
			'multipolygon' => 'Multi Polygon'
		];
	}

	/**
	 * Upload Excel file and create map data rows
	 */
	public function uploadRows(Request $request)
	{
		$request->validate([
			'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
			'map_data_id' => 'required|exists:map_data,id'
		]);

		try {
			// Get the map data
			$mapData = MapData::findOrFail($request->map_data_id);

			$import = new MapDataRowDataImport($mapData);

			try {
				Excel::import($import, $request->file('excel_file'));

				$rowsCreated = $import->getRowsCreated();
				$errors = $import->getErrors();

				dd($rowsCreated, $errors);

				// Check for any errors during import
				if (!empty($errors)) {
					return redirect()->back()->with([
						'success' => 'Excel file imported with some errors.',
						'data' => [
							'rows_created' => $rowsCreated,
							'errors' => $errors
						]
					]);
				}

				return redirect()->back()->with([
					'success' => 'Excel data uploaded successfully.',
					'data' => [
						'rows_created' => $rowsCreated
					]
				]);
			} catch (ValidationException $e) {
				// Handle validation errors
				$failures = $e->failures();
				$validationErrors = [];

				foreach ($failures as $failure) {
					$validationErrors[] = [
						'row' => $failure->row(),
						'attribute' => $failure->attribute(),
						'errors' => $failure->errors(),
						'values' => $failure->values()
					];
				}

				return redirect()->back()->withErrors([
					'excel_file' => 'Validation errors occurred during import.'. $e->getMessage()
				])->with([
					'validation_errors' => $validationErrors
				]);
			}
		} catch (\Exception $e) {
			return redirect()->back()->withErrors([
				'excel_file' => 'Error processing Excel file: ' . $e->getMessage()
			]);
		}
	}

	/**
	 * Get validation rules for map data
	 */
	private function validationRules(): array
	{
		return [
			'name' => 'required|string|max:255',
			'description' => 'nullable|string|max:1000',
			'type' => 'required|string|in:point,line,polygon,multipoint,multiline,multipolygon',
			'user_id' => 'required|exists:users,id',
			'data' => 'nullable|string',
			'map_data_rows' => 'nullable|array',
			'map_data_rows.*' => 'nullable|array'
		];
	}
}
