<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Demarcation;
use App\Models\Demographic;
use App\Models\MapData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DemarcationController extends Controller
{
	public $visibleCounties = [47,22,16];
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		// Always get the Google Maps API key
		$api_key = config('app.google_maps_api_key');

		// Default: show counties for Kenya (parent_type COUNTRY, parent_code 0)
		$counties = Demarcation::where('parent_type', 'COUNTRY')
			->where('parent_code', '0')
			->whereIn('code', $this->visibleCounties)
			->select(DB::raw('code as id'), 'name')
			->orderBy('name', 'asc')
			->get();

		// If parent_type and parent_id are set and parent_type is 'constituency', use those instead of boundary filters
		if (
			$request->has('parent_type') &&
			$request->has('parent_id') &&
			strtolower($request->parent_type) === 'county' &&
			$request->parent_id != '0'
		) {
			$constituencies = Demarcation::where('parent_type', 'county')
				->where('parent_code', $request->parent_id)
				->select(DB::raw('code as id'), 'name')
				->orderBy('name', 'asc')
				->get();
		}
		// Otherwise, use the boundary_type/boundary_id for counties
		elseif ($request->boundary_type == 'county' && $request->has('boundary_id') && $request->boundary_id != '0') {
			$constituencies = Demarcation::where('parent_type', 'county')
				->where('parent_code', $request->boundary_id)
				->select(DB::raw('code as id'), 'name')
				->orderBy('name', 'asc')
				->get();
		} else {
			$constituencies = [];
		}

		// If a constituency is selected, show its wards
		if ($request->boundary_type == 'constituency' && $request->has('boundary_id')) {
			$wards = Demarcation::where('parent_type', 'constituency')
				->where('parent_code', $request->boundary_id)
				->select(DB::raw('code as id'), 'name')
				->orderBy('name', 'asc')
				->get();
		} else {
			$wards = [];
		}

		$available_map_data = MapData::get();

		$data = [
			'counties' => $counties,
			'constituencies' => $constituencies,
			'wards' => $wards,
			'api_key' => $api_key,
			'boundaries' => Inertia::defer(fn () => $this->fetchGeometry($request)),
			'available_map_data' => $available_map_data
		];

		// return response()->json($available_map_data);

		return Inertia::render('Welcome', $data);
	}

	public function fetchGeometry(Request $request)
	{
		if ($request->boundary_type == 'county' || $request->boundary_type == 'constituency') {	
			$demarcations = Demarcation::select('id', 'name', 'type', 'geometry')->where('parent_type', $request->boundary_type)
			->where('parent_code', $request->boundary_id)->get();
		}
		else if ($request->boundary_type == 'ward') {
			$demarcations = Demarcation::select('id', 'name', 'type', 'geometry')->where('type', 'ward')->where('code', $request->boundary_id)->get();
		} else {
			$demarcations = Demarcation::select('id', 'name', 'type', 'geometry')->where('parent_type', 'COUNTRY')->whereIn('code', $this->visibleCounties)->get();
		}

		return $demarcations;
	}

	public function getDemographicsHeatMap(){
		$demographics = Demographic::select('longitude', 'latitude', 'Average_Score')
			->get();
		
		return $demographics;
	}

	public function getCustomers(Request $request){
		$query = Customer::with(['salesPerson'])
		->whereIn('county_id', $this->visibleCounties);

		if($request->filled('sales_person_id')){
			$query->where('sales_person_id', $request->sales_person_id);
		}
		
		$customers = $query->get();
		return $customers;
	}

	public function getMapDataPoints(Request $request){
		if($request->has('table_name')){
			$mapData = Db::table($request->table_name)->where(function($query) use ($request){
				if($request->has('county_id')){
					$query->where('county_id', $request->county_id);
				}
				if($request->has('constituency_id')){
					$query->where('constituency_id', $request->constituency_id);
				}
				if($request->has('ward_id')){
					$query->where('ward_id', $request->ward_id);
				}
			})->get();
			return $mapData;
		}

		return [];
	}
}
