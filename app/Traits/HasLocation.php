<?php

namespace App\Traits;

use App\Models\Pointable;
use MatanYadaev\EloquentSpatial\Objects\Point;

trait HasLocation
{
	public function addLocation(Point $point)
	{
		Pointable::create([
			"pointable_id" => $this->id,
			"pointable_type" => get_class($this),
			"location" => $point
		]);
	}

	public function location()
	{
		return $this->morphOne(Pointable::class, 'pointable');
	}

	public function locations()
	{
		return $this->morphMany(Pointable::class, 'pointable');
	}
}
