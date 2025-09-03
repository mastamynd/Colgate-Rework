<?php

namespace App\Models;

use App\Traits\HasLocation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
	use HasLocation, HasUuids;

	protected $fillable = [
		'name',
		'phone',
		'email',
		'address',
		'county_id',
		'constituency_id',
		'ward_id',
		'sales_person_id',
		'route_id',
		'is_active'
	];

	protected $casts = [
		'is_active' => 'boolean',
	];

	/**
	 * Scope a query to only include active customers.
	 */
	public function scopeActive(Builder $query): Builder
	{
		return $query->where('is_active', true);
	}

	/**
	 * Scope a query to only include inactive customers.
	 */
	public function scopeInactive(Builder $query): Builder
	{
		return $query->where('is_active', false);
	}

	/**
	 * Deactivate the customer.
	 */
	public function deactivate(): bool
	{
		return $this->update(['is_active' => false]);
	}

	/**
	 * Activate the customer.
	 */
	public function activate(): bool
	{
		return $this->update(['is_active' => true]);
	}

	/**
	 * Get the sales person that owns the customer.
	 */
	public function salesPerson()
	{
		return $this->belongsTo(SalesPerson::class, 'sales_person_id');
	}

	/**
	 * Get the route that the customer belongs to.
	 */
	public function route()
	{
		return $this->belongsTo(Route::class, 'route_id');
	}

	/**
	 * Get the county boundary for the customer.
	 */
	public function county()
	{
		return $this->belongsTo(Boundary::class, 'county_id')
			->where('type', 'county');
	}

	/**
	 * Get the constituency boundary for the customer.
	 */
	public function constituency()
	{
		return $this->belongsTo(Boundary::class, 'constituency_id')
			->where('type', 'constituency');
	}

	/**
	 * Get the ward boundary for the customer.
	 */
	public function ward()
	{
		return $this->belongsTo(Boundary::class, 'ward_id')
			->where('type', 'ward');
	}
}
