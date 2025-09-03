<?php

namespace App\Imports;

use App\Models\MapData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use MatanYadaev\EloquentSpatial\Objects\Point;

class MapDataRowDataImport implements ToCollection, WithHeadingRow, WithChunkReading
{
	use Importable;

	protected $rowsCreated = 0;
	protected $errors = [];
	protected $hasLocation = false;

	public function __construct(public MapData $mapData, public string $tableName, public array $columns)
	{
		// dd($this->columns);
		$this->mapData = $mapData;
		unset($this->columns['id']);

		if(in_array('latitude', $this->columns) && in_array('longitude', $this->columns)) {
			$this->hasLocation = true;
		}
		if(in_array('lat', $this->columns) && in_array('lng', $this->columns)) {
			$this->hasLocation = true;
		}
	}

	public function collection(Collection $rows)
	{
		foreach ($rows as $row) {
			$insertData = [];

			foreach ($this->columns as $key) {
				if($key === 'id') continue;

				if($key === 'map_data_id') {
					$insertData[$key] = $this->mapData->id;
					continue;
				}

				$insertData[$key] = $row[$key] ?? null;
			}

			DB::table($this->tableName)->insert($insertData);
		}
	}

	public function chunkSize	(): int
	{
		return 1000;
	}
}
