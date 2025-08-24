<?php

namespace App\Trait;

use App\Models\Areable;
use MatanYadaev\EloquentSpatial\Objects\Geometry;

trait HasArea
{
    public function addArea(Geometry $area){
        Areable::create([
            "areable_id" => $this->id,
            "areable_type" => get_class($this),
            "area" => $area
        ]);
    }

    public function area(){
        return $this->morphOne(Areable::class, 'pointable');
    }

    public function areas()
    {
        return $this->morphMany(Areable::class, 'areable');
    }
}
