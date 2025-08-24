<?php

namespace App\Http\Controllers;

use App\Models\Demarcation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DemarcationController extends Controller
{
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

		$data = [
			'counties' => $counties,
			'constituencies' => $constituencies,
			'wards' => $wards,
			'api_key' => $api_key,
			'boundaries' => Inertia::defer(fn () => $this->fetchGeometry($request))
		];

		return Inertia::render('Welcome', $data);
	}

	public function fetchGeometry(Request $request)
	{
		if ($request->boundary_type == 'county' || $request->boundary_type == 'constituency') {	
			$demarcations = Demarcation::select('id', 'name', 'geometry')->where('parent_type', $request->boundary_type)->where('parent_code', $request->boundary_id)->get();
		}
		else if ($request->boundary_type == 'ward') {
			$demarcations = Demarcation::select('id', 'name', 'geometry')->where('type', 'ward')->where('code', $request->boundary_id)->get();
		} else {
			$demarcations = Demarcation::select('id', 'name', 'geometry')->where('parent_type', 'COUNTRY')->get();
		}

		return $demarcations;
	}
}
