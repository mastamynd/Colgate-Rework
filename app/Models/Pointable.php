<?php

namespace App\Models;

use App\Traits\HasLocation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Pointable extends Model
{
    use HasSpatial, HasLocation, HasUuids;
    protected $guarded = ["id"];

    protected $casts = [
      'location' => Point::class,
    ];
}
