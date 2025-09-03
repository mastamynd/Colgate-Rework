<?php

namespace App\Models;

use App\Traits\HasArea;
use App\Traits\HasLocation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MapDataRow extends Model
{
  use HasLocation, HasArea, HasUuids;

	protected $fillable = ['map_data_id', 'data'];

	/**
	 * The attributes that should be cast.
	 */
	protected $casts = [
		'data' => 'array',
	];

	/**
	 * Get the map data that owns the row.
	 */
	public function mapData()
	{
		return $this->belongsTo(MapData::class);
	}
}
