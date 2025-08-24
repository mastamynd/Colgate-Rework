<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boundary extends Model
{
	protected $fillable = [
		'name',
		'code',
		'type',
		'parent_type',
		'parent_code',
		'geometry'
	];

	protected $casts = [
		'geometry' => 'array',
	];

	/**
	 * Get counties (type = 'county')
	 */
	public static function getCounties()
	{
		return self::where('type', 'county')
			->select('id', 'name', 'code', 'parent_code')
			->orderBy('name')->get();
	}

	/**
	 * Get constituencies for a specific county
	 */
	public static function getConstituencies($countyCode)
	{
		return self::where('type', 'constituency')
			->where('parent_code', $countyCode)
			->select('id', 'name', 'code', 'parent_code')
			->orderBy('name')
			->get();
	}

	/**
	 * Get wards for a specific constituency
	 */
	public static function getWards($constituencyCode)
	{
		return self::where('type', 'ward')
			->where('parent_code', $constituencyCode)
			->select('id', 'name', 'code', 'parent_code')
			->orderBy('name')
			->get();
	}

	/**
	 * Get the parent boundary
	 */
	public function parent()
	{
		return $this->belongsTo(self::class, 'parent_code', 'code')
			->where('type', $this->parent_type);
	}

	/**
	 * Get child boundaries
	 */
	public function children()
	{
		return $this->hasMany(self::class, 'parent_code', 'code')
			->where('parent_type', $this->type);
	}
}
