<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerKd extends Model
{
  protected $primaryKey = 'code';
  public $incrementing = false;
  protected $keyType = 'string';
  
  protected $fillable = [
    'code',
    'name',
    'color',
  ];

	public function customers()
	{
		return $this->hasMany(Customer::class, 'customer_kd_code', 'code');
	}
}
