<?php

namespace App\Models;

use App\Traits\HasArea;
use App\Traits\HasLocation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MapData extends Model
{
  use HasFactory, HasUuids;

  /**
   * The attributes that are mass assignable.
   */
  protected $fillable = [
    'name',
    'description',
    'type',
    'user_id',
    'data',
    'data_table',
    'data_columns',
    'is_active'
  ];

  /**
   * The attributes that should be cast.
   */
  protected $casts = [
    'is_active' => 'boolean',
    'data_columns' => 'array',
  ];

	protected $appends = ['dynamic_row_count'];

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

  /**
   * Get data from the dynamic table
   */
  public function getDynamicData()
  {
    if (!$this->data_table || !Schema::hasTable($this->data_table)) {
      return collect();
    }

    return DB::table($this->data_table)
      ->where('map_data_id', $this->id)
      ->get();
  }

  /**
   * Get the count of rows in the dynamic table
   */
  public function getDynamicRowCountAttribute(): int
  {
    if (!$this->data_table || !Schema::hasTable($this->data_table)) {
      return 0;
    }

    return DB::table($this->data_table)
      ->where('map_data_id', $this->id)
      ->count();
  }

  /**
   * Check if this MapData has a dynamic table
   */
  public function hasDynamicTable(): bool
  {
    return !empty($this->data_table) && Schema::hasTable($this->data_table);
  }

  /**
   * Delete the dynamic table and its data
   */
  public function deleteDynamicTable(): void
  {
    if ($this->data_table && Schema::hasTable($this->data_table)) {
     Schema::drop($this->data_table);
    }
  }

  /**
   * Override delete to clean up dynamic table
   */
  public function delete()
  {
    $this->deleteDynamicTable();
    return parent::delete();
  }
}
