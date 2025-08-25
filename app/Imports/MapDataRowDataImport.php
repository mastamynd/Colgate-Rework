<?php

namespace App\Imports;

use App\Models\MapData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use MatanYadaev\EloquentSpatial\Objects\Point;

class MapDataRowDataImport implements ToCollection, WithValidation
{
	use Importable;

	protected $mapData;
	protected $rowsCreated = 0;
	protected $errors = [];

	public function __construct(MapData $mapData)
	{
		$this->mapData = $mapData;
	}

	/**
	 * @param Collection $rows
	 */
	public function collection(Collection $rows)
	{
		// Delete existing rows for this map data
		$this->mapData->mapDataRows()->delete();

		// Process each data row
		foreach ($rows as $index => $row) {
			try {
				// Convert row to array
				$rowData = $row->toArray();

				// Create the map data row
				$mapDataRow = $this->mapData->mapDataRows()->create([
					'data' => $rowData
				]);

				// Create location if latitude and longitude are provided
				$latitude = $rowData['latitude'] ?? null;
				$longitude = $rowData['longitude'] ?? null;

				if ($latitude && $longitude && is_numeric($latitude) && is_numeric($longitude)) {
					try {
						// Create a Point object using the spatial package
						// Note: Point constructor expects (longitude, latitude, srid)
						$point = new Point((float)$longitude, (float)$latitude, 4326);
						$mapDataRow->addLocation($point);
					} catch (\Exception $e) {
						// Log the error but continue processing other rows
						Log::warning("Failed to create location for row " . ($index + 2) . ": " . $e->getMessage());
						$this->errors[] = "Row " . ($index + 2) . ": Failed to create location - " . $e->getMessage();
					}
				}

				$this->rowsCreated++;
			} catch (\Exception $e) {
				Log::error("Failed to process row " . ($index + 2) . ": " . $e->getMessage());
				$this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
			}
		}
	}

	/**
	 * Define validation rules for the import
	 */
	public function rules(): array
	{
		return [
			'latitude' => ['required', 'numeric', 'between:-90,90'],
			'longitude' => ['required', 'numeric', 'between:-180,180'],
		];
	}

	/**
	 * Custom validation messages
	 */
	public function customValidationMessages(): array
	{
		return [
			'latitude.required' => 'Latitude column is required',
			'latitude.numeric' => 'Latitude must be a valid number',
			'latitude.between' => 'Latitude must be between -90 and 90',
			'longitude.required' => 'Longitude column is required',
			'longitude.numeric' => 'Longitude must be a valid number',
			'longitude.between' => 'Longitude must be between -180 and 180',
		];
	}

	/**
	 * Get the number of rows created
	 */
	public function getRowsCreated(): int
	{
		return $this->rowsCreated;
	}

	/**
	 * Get any errors that occurred during import
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
