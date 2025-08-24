<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesPerson;
use App\Models\Route;
use App\Models\Boundary;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = Customer::with(['salesPerson', 'route', 'county', 'constituency', 'ward']);

		// Apply filters
		if ($request->filled('sales_person_id')) {
			$query->where('sales_person_id', $request->sales_person_id);
		}

		if ($request->filled('route_id')) {
			$query->where('route_id', $request->route_id);
		}

		if ($request->filled('county_id')) {
			$query->where('county_id', $request->county_id);
		}

		if ($request->filled('constituency_id')) {
			$query->where('constituency_id', $request->constituency_id);
		}

		if ($request->filled('ward_id')) {
			$query->where('ward_id', $request->ward_id);
		}

		if ($request->filled('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
				  ->orWhere('phone', 'like', "%{$search}%")
				  ->orWhere('email', 'like', "%{$search}%");
			});
		}

		return Inertia::render('Customers/Index', [
			"customers" => Inertia::merge(
				$query->orderBy('name')->paginate()->withQueryString()
			),
			"salesPersonnel" => SalesPerson::active()->orderBy('name')->get(),
			"routes" => Route::active()->orderBy('name')->get(),
			"counties" => Boundary::getCounties(),
			"filters" => $request->only(['sales_person_id', 'route_id', 'county_id', 'constituency_id', 'ward_id', 'search'])
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$request->validate($this->validationRules());

		$data = $this->convertBoundaryCodesToIds($request, $request->all());

		Customer::create($data);

		return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, Customer $customer)
	{
		$request->validate($this->validationRules());

		$data = $this->convertBoundaryCodesToIds($request, $request->all());

		$customer->update($data);

		return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
	}

	/**
	 * Deactivate the specified customer.
	 */
	public function deactivate(Customer $customer)
	{
		$customer->deactivate();

		return redirect()->route('customers.index')->with('success', 'Customer deactivated successfully.');
	}

	/**
	 * Activate the specified customer.
	 */
	public function activate(Customer $customer)
	{
		$customer->activate();

		return redirect()->route('customers.index')->with('success', 'Customer activated successfully.');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Customer $customer)
	{
		$customer->delete();

		return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
	}

	/**
	 * Get constituencies by county code
	 */
	public function getConstituencies($countyCode)
	{
		return response()->json(Boundary::getConstituencies($countyCode));
	}

	/**
	 * Get wards by constituency code
	 */
	public function getWards($constituencyCode)
	{
		return response()->json(Boundary::getWards($constituencyCode));
	}

	private function validationRules(): array
	{
		return [
			'name' => 'required|string|max:255',
			'phone' => 'nullable|string|max:255',
			'email' => 'nullable|email|max:255',
			'address' => 'nullable|string|max:500',
			'county_code' => 'nullable|string|max:255',
			'constituency_code' => 'nullable|string|max:255',
			'ward_code' => 'nullable|string|max:255',
			'sales_person_id' => 'nullable|exists:sales_people,id',
			'route_id' => 'nullable|exists:routes,id',
		];
	}

	private function convertBoundaryCodesToIds(Request $request, array $data): array
	{
		// Convert boundary codes to IDs
		if ($request->filled('county_code')) {
			$county = Boundary::where('code', $request->county_code)->where('type', 'county')->first();
			$data['county_id'] = $county ? $county->id : null;
			unset($data['county_code']);
		}

		if ($request->filled('constituency_code')) {
			$constituency = Boundary::where('code', $request->constituency_code)->where('type', 'constituency')->first();
			$data['constituency_id'] = $constituency ? $constituency->id : null;
			unset($data['constituency_code']);
		}

		if ($request->filled('ward_code')) {
			$ward = Boundary::where('code', $request->ward_code)->where('type', 'ward')->first();
			$data['ward_id'] = $ward ? $ward->id : null;
			unset($data['ward_code']);
		}

		return $data;
	}
}
