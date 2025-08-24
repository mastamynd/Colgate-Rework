<?php

namespace App\Models;

use App\Trait\HasArea;
use App\Trait\HasLocation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MapData extends Model
{
  use HasUuids;

  /**
   * The attributes that are mass assignable.
   */
  protected $fillable = [
    'name',
    'description',
    'type',
    'user_id',
    'data',
    'is_active'
  ];

  /**
   * The attributes that should be cast.
   */
  protected $casts = [
    'is_active' => 'boolean',
  ];

  /**
   * Get the map data rows for the map data.
   */
  public function mapDataRows()
  {
    return $this->hasMany(MapDataRow::class);
  }

  /**
   * Get the user that owns the map data.
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Scope a query to only include active map data.
   */
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  /**
   * Scope a query to only include inactive map data.
   */
  public function scopeInactive($query)
  {
    return $query->where('is_active', false);
  }

  /**
   * Activate the map data.
   */
  public function activate()
  {
    $this->update(['is_active' => true]);
  }

  /**
   * Deactivate the map data.
   */
  public function deactivate()
  {
    $this->update(['is_active' => false]);
  }

  /**
   * Check if the map data is active.
   */
  public function isActive(): bool
  {
    return $this->is_active;
  }

  /**
   * Check if the map data is inactive.
   */
  public function isInactive(): bool
  {
    return !$this->is_active;
  }
}
