<?php

namespace App\Http\Controllers;

use App\Models\MapData;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MapDataRowDataImport;
use App\Jobs\MapDataToBoundaries;

class MapDataController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = MapData::with(['user']);

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

		$mapDataCollection = $query->orderBy('name')->paginate()->withQueryString();

		// return json_encode($mapDataCollection);

		return Inertia::render('MapData/Index', [
			"mapData" => Inertia::merge($mapDataCollection),
			"users" => User::orderBy('name')->get(),
			"types" => $this->getMapDataTypes(),
			"filters" => $request->only(['user_id', 'type', 'is_active', 'search'])
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		return Inertia::render('MapData/Create', [
			'users' => User::orderBy('name')->get(),
			'types' => $this->getMapDataTypes()
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$request->validate($this->validationRules());

		$mapData = MapData::create($request->all());

		return redirect()->route('map-data.index')->with('success', 'Map data created successfully. Upload an Excel file to populate the data.');
	}

	/**
	 * Display the specified resource.
	 */
	public function show(MapData $mapData)
	{
		$mapData->load(['user']);

		// Get dynamic table data
		$dynamicData = $mapData->getDynamicData();
		$columns = $mapData->data_columns ?? [];

		return Inertia::render('MapData/Show', [
			'mapData' => $mapData,
			'dynamicData' => $dynamicData,
			'columns' => $columns,
			'hasTable' => $mapData->hasDynamicTable()
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(MapData $mapData)
	{
		$mapData->load(['user']);

		return Inertia::render('MapData/Edit', [
			'mapData' => $mapData,
			'users' => User::orderBy('name')->get(),
			'types' => $this->getMapDataTypes(),
			'hasTable' => $mapData->hasDynamicTable(),
			'columns' => $mapData->data_columns ?? []
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, MapData $mapData)
	{
		$request->validate($this->validationRules());

		$mapData->update($request->only(['name', 'description', 'type', 'user_id', 'data', 'is_active']));

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
		// The delete method in the model will handle cleaning up the dynamic table
		$mapData->delete();

		return redirect()->route('map-data.index')->with('success', 'Map data and associated table deleted successfully.');
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
	 * Upload Excel file and create dynamic table
	 */
	public function uploadRows(Request $request)
	{
		$request->validate([
			'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
			'map_data_id' => 'required|exists:map_data,id'
		]);

		$tableName = $this->createTable($request->map_data_id, $request->file('excel_file'));

		if (!$tableName) {
			return redirect()->route('map-data.show', $request->map_data_id)->with('error', 'Failed to create table.');
		}

		return redirect()->route('map-data.show', $request->map_data_id)->with('success', 'Map data rows uploaded successfully.');
	}

	/**
	 * Get data from dynamic table with pagination
	 */
	public function getData(Request $request, MapData $mapData) {}

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
			'data' => 'nullable|string'
		];
	}

	/**
	 * Check if table exists and drop it otherwise and then create either way.
	 */
	public function createTable($map_data_id, $excel_file)
	{
		$mapData = MapData::find($map_data_id);
		$tableName = trim($mapData->data_table);

		if ($tableName == "" || $tableName == null) {
			$tableName = "map_data_" . $this->cleanTableName($mapData->name) . "_" . substr(md5(time()), 0, 6);

			$mapData->data_table = $tableName;
			$mapData->save();
		}

		$this->dropTableIfExists($tableName);

		$tableColumns = $this->getExcelHeaders($excel_file);
		
		if (empty($tableColumns)) {
			return false;
		}

		$finalHeaders = $this->createTableFromHeaders($tableName, $tableColumns);

		$mapData->data_columns = $finalHeaders;
		$mapData->data_table = $tableName;
		$mapData->save();

		Excel::import(new MapDataRowDataImport($mapData, $tableName, $finalHeaders), $excel_file);

		MapDataToBoundaries::dispatch($finalHeaders, $tableName);

		return $tableName;
	}

	/**
	 * Create a table with columns from headers, all as varchar(255).
	 * If headers contain latitude/lat and longitude/lng/lon, combine into a 'location' column as Point.
	 *
	 * @param string $tableName
	 * @param array $headers
	 * @return void
	 */
	public function createTableFromHeaders(string $tableName, array $headers): array
	{
		// Normalize headers for easier matching
		$normalizedHeaders = array_map(function ($header) {
			return $this->cleanTableName(strtolower(trim($header)));
		}, $headers);

		// Remove lat/lng columns if both are present
		$finalHeaders = $headers;

		Schema::create($tableName, function ($table) use (&$finalHeaders, $tableName) {
			$table->bigIncrements('id');
			$table->foreignUuid('map_data_id')->nullable();
			foreach ($finalHeaders as $header) {
				$col = trim($header);
				if ($col === '' || $col === 'id' || $col === 'map_data_id') continue;
				$table->string($col, 255)->nullable();
			}
			$table->timestamps();
		});

		return $finalHeaders;
	}

	/**
	 * Drop a table if it exists and starts with 'map_data_'
	 */
	private function dropTableIfExists(string $tableName): bool
	{
		// Check if table name starts with 'map_data_'
		if (!str_starts_with($tableName, 'map_data_')) {
			return false;
		}

		// Check if table exists and drop it
		if (Schema::hasTable($tableName)) {
			Schema::drop($tableName);
			return true;
		}

		return false;
	}

	/**
	 * Extract header column names from an Excel file
	 */
	private function getExcelHeaders($filePath): array
	{
		try {
			$data = Excel::toArray([], $filePath);

			// Get the first sheet
			if (empty($data) || empty($data[0])) {
				return [];
			}

			// Get the first row (headers)
			$headers = $data[0][0] ?? [];

			// Clean and filter headers
			$cleanHeaders = ['id', 'map_data_id'];
			foreach ($headers as $header) {
				$cleanHeader = trim($header);
				if (!empty($cleanHeader)) {
					$cleanHeaders[] = $this->cleanTableName($cleanHeader);
				}
			}

			return $cleanHeaders;
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Clean a string to make it suitable for a database table name
	 */
	private function cleanTableName(string $input): string
	{
		// Convert to lowercase
		$cleaned = strtolower($input);

		// Replace spaces and special characters with underscores
		$cleaned = preg_replace('/[^a-z0-9_]/', '_', $cleaned);

		// Remove multiple consecutive underscores
		$cleaned = preg_replace('/_+/', '_', $cleaned);

		// Remove leading/trailing underscores
		$cleaned = trim($cleaned, '_');

		// Ensure it starts with a letter or underscore (not a number)
		if (preg_match('/^[0-9]/', $cleaned)) {
			$cleaned = 'table_' . $cleaned;
		}

		// Limit length to 64 characters (MySQL limit)
		$cleaned = substr($cleaned, 0, 64);

		// Remove trailing underscore if truncation created one
		$cleaned = rtrim($cleaned, '_');

		// Ensure we have a valid name
		if (empty($cleaned)) {
			$cleaned = 'table_' . uniqid();
		}

		return $cleaned;
	}
}
