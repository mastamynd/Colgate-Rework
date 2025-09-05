<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use MatanYadaev\EloquentSpatial\Objects\LineString;

class Route extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'line',
        'description',
        'is_active',
        'color'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'line' => LineString::class,
    ];

    /**
     * Scope a query to only include active routes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive routes.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Deactivate the route.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Activate the route.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Get the customers that belong to the route.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'route_id');
    }
}
