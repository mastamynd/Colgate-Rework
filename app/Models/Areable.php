<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Geometry;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Areable extends Model
{
    use HasSpatial, HasUuids;

    protected $guarded = ["id"];

    protected $casts = [
        'area' => Geometry::class,
    ];
}
