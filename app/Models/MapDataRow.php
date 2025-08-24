<?php

namespace App\Models;

use App\Trait\HasArea;
use App\Trait\HasLocation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MapDataRow extends Model
{
  use HasLocation, HasArea, HasUuids;
}
